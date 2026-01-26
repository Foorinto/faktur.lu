<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add title column with default empty string (required for SQLite)
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('title', 255)->default('')->after('invoice_id');
        });

        // Migrate existing data: copy first line of description to title
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: use substr and instr
            DB::table('invoice_items')->whereNotNull('description')->update([
                'title' => DB::raw("CASE WHEN instr(description, char(10)) > 0 THEN substr(description, 1, instr(description, char(10)) - 1) ELSE description END"),
            ]);
        } else {
            // MySQL
            DB::table('invoice_items')->whereNotNull('description')->update([
                'title' => DB::raw("SUBSTRING_INDEX(description, '\n', 1)"),
            ]);
        }

        // Remove default and make description nullable
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('title', 255)->default(null)->change();
            $table->text('description')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Merge title back into description
        if (DB::getDriverName() === 'sqlite') {
            DB::table('invoice_items')->update([
                'description' => DB::raw("title || COALESCE(char(10) || description, '')"),
            ]);
        } else {
            DB::table('invoice_items')->update([
                'description' => DB::raw("CONCAT(title, COALESCE(CONCAT('\n', description), ''))"),
            ]);
        }

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('title');
            $table->string('description', 500)->nullable(false)->change();
        });
    }
};
