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
        if (Schema::hasColumn('clients', 'default_vat_rate')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            // Default VAT rate for this client (nullable = use automatic calculation)
            $table->decimal('default_vat_rate', 5, 2)->nullable()->after('default_hourly_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('default_vat_rate');
        });
    }
};
