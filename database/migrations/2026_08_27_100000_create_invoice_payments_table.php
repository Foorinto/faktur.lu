<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Encaissements d'une facture (FEAT-114).
 *
 * Demande d'un client payant : savoir combien il a encaissé en espèces, en
 * virement, par carte. Sa précision du 2026-08-27 a décidé de la forme :
 *
 *     « parfois par différent moyen par facture : j'ai des fois un paiement
 *       en espèces pour une partie et le reste par virement »
 *
 * Un champ `payment_method` sur la facture ne sait pas représenter « 300 € en
 * espèces le 3, 700 € par virement le 17 ». D'où une table.
 *
 * ⚠️ Le statut de la facture reste la source de vérité pour « payée ».
 * Treize fichiers en dépendent — exports comptables, FAIA, portail comptable,
 * archivage PDF, alertes de franchise. On ne déplace pas cette vérité : on la
 * DÉRIVE des encaissements et on continue d'écrire `status`. Un paiement
 * partiel ne crée donc pas de sixième statut ; il se lit dans le reste dû.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();

            // Montant encaissé, pas le total de la facture : plusieurs lignes
            // peuvent se succéder jusqu'à couvrir le dû.
            $table->decimal('amount', 12, 2);
            $table->date('paid_at');

            // Nullable, et c'est délibéré : les factures déjà payées avant
            // cette table n'ont jamais porté cette information. La reprise
            // ci-dessous les marque « non renseigné » plutôt que d'inventer un
            // virement — ce serait fabriquer une donnée comptable dans des
            // documents conservés dix ans.
            $table->string('method', 20)->nullable();
            $table->string('reference')->nullable();

            $table->timestamps();

            // La question posée à cette table est toujours « quels
            // encaissements pour cette facture, du plus récent au plus ancien ».
            $table->index(['invoice_id', 'paid_at']);
            // Et, pour le récapitulatif : « combien par moyen sur une période ».
            $table->index(['method', 'paid_at']);
        });

        // --- Reprise des factures déjà payées --------------------------------
        //
        // Une ligne par facture au statut « payée », du montant total, à la
        // date connue. `method` reste nul : on ne sait pas, et le dire est plus
        // utile que de le supposer.
        DB::table('invoices')
            ->where('status', 'paid')
            ->orderBy('id')
            ->chunkById(500, function ($factures) {
                $lignes = [];

                foreach ($factures as $facture) {
                    $lignes[] = [
                        'invoice_id' => $facture->id,
                        'amount' => $facture->total_ttc,
                        'paid_at' => $facture->paid_at
                            ? substr((string) $facture->paid_at, 0, 10)
                            : substr((string) $facture->updated_at, 0, 10),
                        'method' => null,
                        'reference' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if ($lignes !== []) {
                    DB::table('invoice_payments')->insert($lignes);
                }
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
    }
};
