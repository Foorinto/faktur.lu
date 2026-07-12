<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FEAT-094 — Remise par ligne (% ou montant fixe).
 *
 * Ajoute discount_type + discount_value sur les 3 tables de lignes
 * (facture, devis, facture récurrente). Défauts NEUTRES (type 'percent',
 * value 0) => aucune ligne existante impactée : une remise ne s'applique
 * que si discount_value > 0.
 */
return new class extends Migration
{
    private array $tables = ['invoice_items', 'quote_items', 'recurring_invoice_items'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                // 'percent' | 'amount' — significatif seulement si discount_value > 0
                $t->string('discount_type', 10)->default('percent')->after('unit_price');
                $t->decimal('discount_value', 12, 4)->default(0)->after('discount_type');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn(['discount_type', 'discount_value']);
            });
        }
    }
};
