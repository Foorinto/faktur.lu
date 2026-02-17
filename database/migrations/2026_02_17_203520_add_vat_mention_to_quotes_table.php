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
        Schema::table('quotes', function (Blueprint $table) {
            $table->string('vat_mention', 50)->nullable()->after('notes');
            $table->text('custom_vat_mention')->nullable()->after('vat_mention');
            $table->text('footer_message')->nullable()->after('custom_vat_mention');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->dropColumn(['vat_mention', 'custom_vat_mention', 'footer_message']);
        });
    }
};
