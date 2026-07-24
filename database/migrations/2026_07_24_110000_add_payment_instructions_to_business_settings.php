<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FEAT-098: champ libre d'instructions de paiement affiché sur la facture
     * (ex. « Chèque à l'ordre de X », lien de paiement carte, etc.).
     */
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->text('payment_instructions')->nullable()->after('default_payment_methods');
        });
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn('payment_instructions');
        });
    }
};
