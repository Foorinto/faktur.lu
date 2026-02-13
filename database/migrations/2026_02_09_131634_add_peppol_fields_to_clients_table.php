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
        if (Schema::hasColumn('clients', 'peppol_endpoint_id')) {
            return;
        }

        Schema::table('clients', function (Blueprint $table) {
            $table->string('peppol_endpoint_id', 50)->nullable()->after('vat_number');
            $table->string('peppol_endpoint_scheme', 4)->nullable()->after('peppol_endpoint_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['peppol_endpoint_id', 'peppol_endpoint_scheme']);
        });
    }
};
