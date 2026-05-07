<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Change unique constraint on invoice number and quote reference
     * from global to per-user.
     *
     * Reason: each taxpayer (user) has their own continuous sequential
     * numbering as required by Article 61 LIVA. The previous global
     * constraint caused new users to receive non-sequential numbers
     * (e.g. F-2026-006 as their first invoice).
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique(['number']);
            $table->unique(['user_id', 'number'], 'invoices_user_number_unique');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique(['reference']);
            $table->unique(['user_id', 'reference'], 'quotes_user_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_user_number_unique');
            $table->unique('number');
        });

        Schema::table('quotes', function (Blueprint $table) {
            $table->dropUnique('quotes_user_reference_unique');
            $table->unique('reference');
        });
    }
};
