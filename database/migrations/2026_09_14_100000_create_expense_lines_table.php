<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ventilation d'une dépense sur plusieurs catégories (FEAT-115).
 *
 * La table `expenses` était plate : une catégorie, un taux, un montant. Une
 * facture qui mêle achat de marchandises et prestation de service n'entrait
 * donc que sur un seul compte comptable. `expense_lines` porte désormais la
 * décomposition ; `expenses` conserve ses colonnes agrégées (recalculées depuis
 * les lignes) parce que quatorze consommateurs lisent encore `expenses.category`.
 *
 * `user_id` est dénormalisé sur la ligne : la ventilation par ligne du
 * récapitulatif fiscal doit rester isolée par compte sans jointure, comme le
 * reste du produit (trait BelongsToUser).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->text('description')->nullable();
            $table->decimal('amount_ht', 12, 4);
            $table->decimal('vat_rate', 5, 2)->default(17.00);
            $table->decimal('amount_vat', 12, 4)->default(0);
            $table->decimal('amount_ttc', 12, 4)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['expense_id', 'sort_order']);
            $table->index(['user_id', 'category']);
        });

        // Reprise : une ligne par dépense existante, reprenant sa catégorie et
        // ses montants. Aucune dépense ne doit rester sans ligne. On passe par
        // le query builder (pas le modèle) pour ne déclencher aucun événement
        // `saving` — les montants agrégés de `expenses` sont déjà bons, la
        // reprise ne fait que les recopier sur la ligne. Les dépenses
        // soft-deleted sont incluses : une restauration doit retrouver sa ligne.
        // Défensif : on ne reprend que les dépenses qui n'ont pas déjà de
        // ligne. La reprise devient ainsi rejouable — une reprise interrompue
        // puis relancée ne créera jamais de doublon, et le total du
        // récapitulatif fiscal ne peut pas doubler.
        DB::table('expenses')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('expense_lines')
                    ->whereColumn('expense_lines.expense_id', 'expenses.id');
            })
            ->orderBy('id')
            ->chunkById(500, function ($expenses) {
            $now = now();
            $rows = [];

            foreach ($expenses as $expense) {
                $rows[] = [
                    'user_id' => $expense->user_id,
                    'expense_id' => $expense->id,
                    'category' => $expense->category,
                    'description' => $expense->description,
                    'amount_ht' => $expense->amount_ht,
                    'vat_rate' => $expense->vat_rate,
                    'amount_vat' => $expense->amount_vat,
                    'amount_ttc' => $expense->amount_ttc,
                    'sort_order' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($rows !== []) {
                DB::table('expense_lines')->insert($rows);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_lines');
    }
};
