<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lien d'une ligne de devis vers le produit du catalogue (FEAT-116, suite de revue).
 *
 * Sans lui, un produit devisé puis facturé arrivait sur la facture en texte
 * libre : la conversion devis→facture perdait le lien, et le stock ne bougeait
 * pas à l'émission. Même contrat que sur invoice_items : nullable (une ligne
 * saisie à la main n'a pas de produit), nullOnDelete (le devis survit à la
 * suppression du produit), aucune reprise des lignes existantes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('quote_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quote_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });
    }
};
