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
        Schema::table('business_settings', function (Blueprint $table) {
            // Numéro RCS Luxembourg (ex: A12345, B98765)
            $table->string('rcs_number', 20)->nullable()->after('matricule');

            // Numéro d'autorisation d'établissement (Ministère de l'Économie)
            $table->string('establishment_authorization', 50)->nullable()->after('rcs_number');
        });

        // Extend matricule to 13 characters (was 11)
        Schema::table('business_settings', function (Blueprint $table) {
            $table->string('matricule', 13)->change();
        });

        // Extend vat_number to support all EU formats (was 11)
        Schema::table('business_settings', function (Blueprint $table) {
            $table->string('vat_number', 20)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn(['rcs_number', 'establishment_authorization']);
        });

        Schema::table('business_settings', function (Blueprint $table) {
            $table->string('matricule', 11)->change();
            $table->string('vat_number', 11)->nullable()->change();
        });
    }
};
