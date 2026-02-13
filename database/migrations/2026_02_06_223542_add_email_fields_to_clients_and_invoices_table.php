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
        if (!Schema::hasColumn('clients', 'exclude_from_reminders')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->boolean('exclude_from_reminders')->default(false);
            });
        }

        if (!Schema::hasColumn('invoices', 'exclude_from_reminders')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->boolean('exclude_from_reminders')->default(false);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('exclude_from_reminders');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('exclude_from_reminders');
        });
    }
};
