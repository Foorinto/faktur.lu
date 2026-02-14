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
        // Add country_code if not exists
        if (!Schema::hasColumn('business_settings', 'country_code')) {
            Schema::table('business_settings', function (Blueprint $table) {
                $table->string('country_code', 2)->default('LU')->after('user_id');
            });
        }

        // Add activity_type if not exists
        if (!Schema::hasColumn('business_settings', 'activity_type')) {
            Schema::table('business_settings', function (Blueprint $table) {
                $table->string('activity_type', 20)->nullable()->after('vat_regime');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn(['country_code', 'activity_type']);
        });
    }
};
