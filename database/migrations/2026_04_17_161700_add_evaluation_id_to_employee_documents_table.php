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
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->foreignId('evaluation_id')->nullable()->after('employee_id')->constrained('evaluations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employee_documents', function (Blueprint $table) {
            $table->dropForeign(['evaluation_id']);
            $table->dropColumn('evaluation_id');
        });
    }
};
