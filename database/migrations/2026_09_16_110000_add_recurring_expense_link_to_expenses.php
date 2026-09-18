<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lien de la dépense vers la charge fixe qui l'a fait naître (FEAT-117).
 *
 * Sans cette trace, impossible de distinguer une dépense générée d'une dépense
 * saisie à la main — et c'est précisément ce que la prévision de trésorerie
 * doit savoir pour ne pas compter le loyer deux fois : une fois dans la moyenne
 * des dépenses passées, une fois dans les charges annoncées.
 *
 * `nullOnDelete` : supprimer la charge fixe n'efface jamais les dépenses déjà
 * engagées, ce sont des écritures comptables. Seul le lien disparaît.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('recurring_expense_id')->nullable()->after('user_id')
                ->constrained('recurring_expenses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recurring_expense_id');
        });
    }
};
