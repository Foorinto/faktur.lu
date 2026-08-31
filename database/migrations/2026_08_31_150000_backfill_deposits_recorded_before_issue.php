<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reprise : les acomptes existants continuent de figurer sur la facture.
 *
 * La migration précédente a marqué tous les encaissements « connus après
 * l'émission », ce qui était exact — la saisie exigeait alors une facture
 * finalisée. Conséquence : les factures déjà annotées auraient perdu leur bloc
 * d'un déploiement à l'autre, sous les yeux du client qui les avait saisies.
 *
 * On rattrape donc ceux qui sont manifestement des ACOMPTES : versés avant la
 * date d'émission de la facture. C'est l'intention même de la fonctionnalité,
 * et l'information reste vraie — l'argent a bien été reçu avant que la facture
 * n'existe.
 *
 * ⚠️ Entorse assumée au critère « saisi avant émission », et limitée aux
 * données antérieures : à partir d'ici, c'est le moment de la saisie qui
 * décide, inscrit à la création de l'encaissement.
 *
 * Les règlements postérieurs à l'émission ne sont pas touchés : ils n'ont
 * jamais figuré sur un document envoyé.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('invoice_payments')
            ->join('invoices', 'invoices.id', '=', 'invoice_payments.invoice_id')
            // Un brouillon n'a pas de date d'émission ; la comparaison avec
            // NULL est fausse, il est donc écarté sans clause supplémentaire.
            ->whereColumn('invoice_payments.paid_at', '<', 'invoices.issued_at')
            ->select('invoice_payments.id')
            ->orderBy('invoice_payments.id')
            ->chunk(500, function ($lignes) {
                DB::table('invoice_payments')
                    ->whereIn('id', collect($lignes)->pluck('id'))
                    ->update(['recorded_before_issue' => true]);
            });
    }

    /**
     * Rien à défaire : la colonne entière disparaît avec la migration qui l'a
     * créée, et distinguer après coup ce que cette reprise a marqué de ce qui
     * l'a été normalement n'aurait pas de sens.
     */
    public function down(): void {}
};
