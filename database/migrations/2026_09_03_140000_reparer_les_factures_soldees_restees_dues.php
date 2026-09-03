<?php

use App\Models\Invoice;
use Illuminate\Database\Migrations\Migration;

/**
 * Reprise : les factures entièrement réglées mais restées « dues ».
 *
 * La migration précédente a supprimé les encaissements fantômes à zéro, et le
 * correctif empêche de nouvelles factures d'entrer dans cet état. Ni l'une ni
 * l'autre ne répare celles qui y sont DÉJÀ.
 *
 * Or ces factures ne sont pas seulement mal affichées. Les relances
 * automatiques visent les statuts « finalisée » et « envoyée » : une facture
 * intégralement encaissée mais restée « envoyée » déclenche donc une relance
 * chez un client qui a déjà payé, dès l'échéance dépassée. Vérifié le
 * 2026-09-03 sur la requête exacte du job d'envoi.
 *
 * ⚠️ La reprise passe par `refreshPaymentStatus()`, la logique de production,
 * plutôt que par une requête SQL équivalente. Deux définitions du « soldé »
 * finiraient par diverger, et c'est exactement ce genre d'écart qui a produit
 * le défaut d'origine.
 *
 * Seules les factures FINALISÉES ou ENVOYÉES sont examinées. Une facture déjà
 * payée, annulée ou au brouillon n'est pas touchée : la reprise ne fait que
 * combler un retard, elle ne remet rien en cause.
 */
return new class extends Migration
{
    public function up(): void
    {
        $reparees = 0;

        Invoice::withoutGlobalScopes()
            ->whereIn('status', [Invoice::STATUS_FINALIZED, Invoice::STATUS_SENT])
            ->where('total_ttc', '>', 0)
            ->with('payments')
            ->chunkById(200, function ($factures) use (&$reparees) {
                foreach ($factures as $facture) {
                    if ($facture->amountDue() > 0) {
                        continue;
                    }

                    $facture->refreshPaymentStatus();
                    $reparees++;
                }
            });

        if ($reparees > 0) {
            info("Factures soldées repassées en payées : {$reparees}");
        }
    }

    /**
     * Rien à défaire. Ces factures SONT payées : les redéclarer dues
     * recréerait le défaut, et relancerait des clients à jour.
     */
    public function down(): void {}
};
