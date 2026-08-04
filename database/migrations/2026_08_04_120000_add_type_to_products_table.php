<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Distinction produit / prestation au catalogue.
 *
 * Demandée par un utilisateur qui tient deux familles d'articles séparées.
 * Jusqu'ici, `products` ne portait aucun champ permettant de les distinguer :
 * seule l'unité (heure/jour pour une prestation, pièce pour un produit) le
 * laissait deviner.
 *
 * ⚠️ La colonne est NULLABLE et cette migration ne contient aucun UPDATE.
 * Un utilisateur se sert déjà du catalogue en production ; ses articles
 * restent à `null` et s'affichent « non classé ». Les reclasser d'office
 * reviendrait à décider à sa place, et à modifier des données qu'on nous a
 * demandé de ne pas toucher.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('type', 20)->nullable()->after('reference');
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'type']);
            $table->dropColumn('type');
        });
    }
};
