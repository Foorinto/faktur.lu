<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une ligne de dépense peut alimenter le stock (FEAT-116, lien avec FEAT-115).
 *
 * Un achat de marchandises est une dépense ; désigner le produit reçu et la
 * quantité permet de générer une entrée de stock au prix réellement payé
 * (coût unitaire = HT de la ligne / quantité). Les deux colonnes sont nullable :
 * une ligne de dépense ordinaire (loyer, honoraires…) n'alimente aucun stock.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_lines', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->after('expense_id')
                ->constrained()->nullOnDelete();
            $table->decimal('stock_quantity', 12, 4)->nullable()->after('product_id');
        });
    }

    public function down(): void
    {
        Schema::table('expense_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
            $table->dropColumn('stock_quantity');
        });
    }
};
