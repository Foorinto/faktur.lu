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
        if (!Schema::hasColumn('users', 'locale')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('locale', 5)->default('fr')->after('email');
            });
        }

        if (!Schema::hasColumn('clients', 'locale')) {
            Schema::table('clients', function (Blueprint $table) {
                $table->string('locale', 5)->default('fr')->after('country');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
