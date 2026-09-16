<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Charges fixes récurrentes (FEAT-117).
 *
 * Une charge fixe n'est pas une donnée de prévision posée à côté des dépenses :
 * c'est un modèle qui **fabrique** des dépenses ordinaires. Le loyer généré est
 * une dépense comme une autre, comptée une seule fois partout — dans le
 * récapitulatif fiscal, dans l'export comptable et dans la trésorerie.
 *
 * Le rythme reprend celui des factures récurrentes, au champ près, pour qu'il
 * n'y ait qu'un seul mécanisme de récurrence à comprendre et à corriger.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // --- Le rythme, calqué sur recurring_invoices ---
            $table->string('label')->nullable(); // « Loyer du bureau »
            $table->string('frequency'); // weekly, monthly, quarterly, yearly
            $table->date('next_expense_date');
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('expenses_generated')->default(0);
            $table->foreignId('last_expense_id')->nullable()->constrained('expenses')->nullOnDelete();

            // --- Le modèle de dépense à faire naître ---
            $table->string('provider_name');
            $table->string('supplier_country', 2)->default('LU');
            $table->string('category');

            // Le montant est conservé tel qu'il a été saisi, en HT ou en TTC.
            // La dépense générée refait elle-même le calcul de TVA : une seule
            // arithmétique, celle qui est déjà éprouvée.
            $table->string('amount_input_mode')->default('ht');
            $table->decimal('amount', 12, 4);

            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->string('vat_regime')->nullable();
            $table->decimal('reverse_charge_vat_rate', 5, 2)->nullable();
            $table->boolean('is_deductible')->default(true);
            $table->string('payment_method')->nullable();
            $table->text('description')->nullable();

            $table->timestamps();

            $table->index(['is_active', 'next_expense_date']);
            $table->index(['user_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_expenses');
    }
};
