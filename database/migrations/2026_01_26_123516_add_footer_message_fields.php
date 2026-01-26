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
        // Add default footer message to business settings
        Schema::table('business_settings', function (Blueprint $table) {
            $table->text('default_invoice_footer')->nullable()->after('default_hourly_rate');
        });

        // Add footer message override to invoices
        Schema::table('invoices', function (Blueprint $table) {
            $table->text('footer_message')->nullable()->after('notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn('default_invoice_footer');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('footer_message');
        });
    }
};
