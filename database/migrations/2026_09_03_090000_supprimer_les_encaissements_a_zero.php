<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Nettoyage : les encaissements fantômes à zéro euro.
 *
 * Signalés par un client le 2026-09-02 :
 *
 *     "J'ai également sur mes dernières factures, dans les encaissements une
 *      erreur : il y a le montant global par virement et une ligne a 0 EUR non
 *      renseigné : il y en a 6 d'après mon tableau de bord"
 *
 * Origine : une facture entièrement réglée par un acompte au stade du
 * brouillon ressortait « finalisée » sans que son statut de règlement soit
 * recalculé. Le bouton « Marquer comme payée » restait actif, et le clic
 * créait un encaissement du reste dû, c'est-à-dire zéro, sans moyen de
 * paiement. Corrigé dans FinalizeInvoiceAction et InvoiceController.
 *
 * Ces lignes n'ont jamais rien représenté. Elles faussent en revanche la
 * ventilation par moyen de paiement, où elles apparaissent sous « Non
 * renseigné », et se retrouvent donc dans le livre de recettes.
 *
 * ⚠️ La suppression ne vise QUE le montant exactement nul. La saisie manuelle
 * impose un minimum de 0,01 euro : aucun encaissement légitime ne peut valoir
 * zéro, et le seul chemin qui en produisait est celui que l'on vient de
 * fermer.
 */
return new class extends Migration
{
    public function up(): void
    {
        $supprimes = DB::table('invoice_payments')->where('amount', 0)->delete();

        if ($supprimes > 0) {
            info("Encaissements à zéro supprimés : {$supprimes}");
        }
    }

    /**
     * Rien à défaire. Ces lignes ne portaient aucune information : ni montant,
     * ni moyen de paiement. Les recréer n'aurait aucun sens.
     */
    public function down(): void {}
};
