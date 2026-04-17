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
        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('retention_guarantee_rate', 5, 2)->nullable()->after('total_ttc');
            $table->decimal('retention_guarantee_amount', 12, 4)->nullable()->after('retention_guarantee_rate');
            $table->date('retention_release_date')->nullable()->after('retention_guarantee_amount');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['retention_guarantee_rate', 'retention_guarantee_amount', 'retention_release_date']);
        });
    }
};
