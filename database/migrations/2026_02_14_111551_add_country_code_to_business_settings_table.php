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
        if (Schema::hasColumn('business_settings', 'country_code')) {
            return;
        }

        Schema::table('business_settings', function (Blueprint $table) {
            // Country code (ISO 3166-1 alpha-2): LU, FR, BE, DE
            $table->string('country_code', 2)->default('LU')->after('user_id');

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
            $table->dropColumn(['country_code', 'activity_type']);
        });
    }
};
