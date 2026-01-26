<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add default VAT mention to business settings
        Schema::table('business_settings', function (Blueprint $table) {
            $table->string('default_vat_mention', 50)->nullable()->after('default_invoice_footer');
            $table->text('default_custom_vat_mention')->nullable()->after('default_vat_mention');
        });

        // Add VAT mention override to invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('vat_mention', 50)->nullable()->after('footer_message');
            $table->text('custom_vat_mention')->nullable()->after('vat_mention');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn(['default_vat_mention', 'default_custom_vat_mention']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['vat_mention', 'custom_vat_mention']);
        });
    }
};
