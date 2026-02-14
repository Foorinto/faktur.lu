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
        if (Schema::hasColumn('business_settings', 'activity_type')) {
            return;
        }

        Schema::table('business_settings', function (Blueprint $table) {
            // Activity type for countries with different thresholds (e.g., France)
            // services, goods, mixed
            $table->string('activity_type', 20)->nullable()->after('vat_regime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn('activity_type');
        });
    }
};
