<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FEAT-094 — Remises globales (sur le total du document), en lignes séparées
 * avec libellé, en % ou montant fixe. Coexistent avec la remise par ligne.
 *
 * Une table par type de document (facture, devis, facture récurrente).
 * Additif : aucun document existant impacté (pas de remise = comportement actuel).
 */
return new class extends Migration
{
    private array $map = [
        'invoice_discounts' => ['invoices', 'invoice_id'],
        'quote_discounts' => ['quotes', 'quote_id'],
        'recurring_invoice_discounts' => ['recurring_invoices', 'recurring_invoice_id'],
    ];

    public function up(): void
    {
        foreach ($this->map as $table => [$parentTable, $fk]) {
            Schema::create($table, function (Blueprint $t) use ($parentTable, $fk) {
                $t->id();
                $t->foreignId($fk)->constrained($parentTable)->cascadeOnDelete();
                $t->string('label')->nullable();               // ex: "Remise première commande"
                $t->string('type', 10)->default('percent');    // 'percent' | 'amount'
                $t->decimal('value', 12, 4)->default(0);
                $t->integer('sort_order')->default(0);
                $t->timestamps();

                $t->index($fk);
            });
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->map) as $table) {
            Schema::dropIfExists($table);
        }
    }
};
