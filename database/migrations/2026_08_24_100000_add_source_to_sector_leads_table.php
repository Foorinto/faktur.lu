<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * D'où vient la personne qui a répondu.
 *
 * Matomo compte les visites par canal ; cette colonne retient le canal jusqu'à
 * la réponse. Les deux ensemble donnent le taux de conversion par canal, seul
 * chiffre permettant de décider s'il faut recommencer un canal ou l'abandonner.
 *
 * Nullable : les réponses déjà reçues n'ont pas de source, et une visite
 * directe n'en a pas non plus. Un `null` dit « on ne sait pas », ce qui est
 * l'exacte vérité — plutôt qu'un « direct » qui prétendrait le contraire.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sector_leads', function (Blueprint $table) {
            // 60 caractères : un `utm_source` raisonnable tient largement, et
            // la limite écarte les paramètres fabriqués pour saturer la table.
            $table->string('source', 60)->nullable()->after('sector');
        });
    }

    public function down(): void
    {
        Schema::table('sector_leads', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
