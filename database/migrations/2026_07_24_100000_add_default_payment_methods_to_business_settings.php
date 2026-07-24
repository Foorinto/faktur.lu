<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FEAT-098: moyens de paiement personnalisables affichés sur la facture.
     * Défaut = null → rendu « Virement » (comportement actuel préservé).
     */
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->json('default_payment_methods')->nullable()->after('payment_qrcode_path');
        });
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn('default_payment_methods');
        });
    }
};
