<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * L'encaissement était-il connu au moment d'émettre la facture ?
 *
 * Seuls ceux saisis AVANT la finalisation figurent sur le document : une
 * facture envoyée ne doit plus changer d'aspect. Le critère était d'abord
 * calculé en comparant `created_at` à `finalized_at` — mais les horodatages
 * sont stockés à la seconde, et deux évènements de la même seconde sont
 * indiscernables. La comparaison laissait alors passer un règlement saisi
 * juste après l'émission.
 *
 * La réponse est donc INSCRITE à la création, plutôt que déduite d'une
 * horloge : au moment où l'encaissement est enregistré, on sait si la facture
 * était encore un brouillon.
 *
 * ⚠️ Reprise à `false` : jusqu'ici la saisie exigeait une facture finalisée,
 * donc aucun encaissement existant n'a été connu avant l'émission. Les PDF
 * régénérés cesseront d'afficher ces lignes — ce qui les remet en accord avec
 * les documents effectivement envoyés aux clients.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->boolean('recorded_before_issue')->default(false)->after('label');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->dropColumn('recorded_before_issue');
        });
    }
};
