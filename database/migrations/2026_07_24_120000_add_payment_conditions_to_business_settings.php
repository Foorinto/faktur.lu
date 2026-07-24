<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FEAT-099: conditions de paiement éditables / masquables sur la facture.
     * Défauts = comportement actuel (affiché, « 3 fois le taux légal », 40 €, « Aucun »).
     */
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->boolean('show_payment_conditions')->default(true)->after('payment_instructions');
            $table->string('late_penalty_text')->nullable()->after('show_payment_conditions');
            $table->decimal('recovery_fee_amount', 8, 2)->nullable()->after('late_penalty_text');
            $table->string('discount_terms')->nullable()->after('recovery_fee_amount');
        });
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn(['show_payment_conditions', 'late_penalty_text', 'recovery_fee_amount', 'discount_terms']);
        });
    }
};
