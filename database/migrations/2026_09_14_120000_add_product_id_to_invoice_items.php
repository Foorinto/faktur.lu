<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lien d'une ligne de facture vers le produit du catalogue (FEAT-116, lot 1).
 *
 * Préalable bloquant à la gestion de stock : sans ce lien, une ligne de facture
 * est du texte libre et l'on ne peut pas décrémenter le stock à l'émission sans
 * deviner. La colonne est nullable — une ligne saisie à la main n'a pas de
 * produit — et se met à null si le produit est supprimé (nullOnDelete), pour ne
 * jamais faire disparaître une facture émise avec son produit.
 *
 * AUCUNE reprise des lignes existantes : elles restent sans produit. Le stock
 * démarrera sur un inventaire d'ouverture, jamais sur des ventes passées déjà
 * reflétées sur les étagères.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('invoice_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
