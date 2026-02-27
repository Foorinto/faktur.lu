<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('onboarding_template_items', function (Blueprint $table) {
            $table->dropColumn('due_offset_days');
        });
    }

    public function down(): void
    {
        Schema::table('onboarding_template_items', function (Blueprint $table) {
            $table->integer('due_offset_days')->nullable()->after('title');
        });
    }
};
