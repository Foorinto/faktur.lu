<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_report_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('original_name');
            $table->timestamps();
        });

        // Migrate existing receipt_path data
        $expenses = DB::table('expense_reports')->whereNotNull('receipt_path')->get();
        foreach ($expenses as $expense) {
            DB::table('expense_receipts')->insert([
                'expense_report_id' => $expense->id,
                'file_path' => $expense->receipt_path,
                'original_name' => basename($expense->receipt_path),
                'created_at' => $expense->created_at,
                'updated_at' => $expense->updated_at,
            ]);
        }

        Schema::table('expense_reports', function (Blueprint $table) {
            $table->dropColumn('receipt_path');
        });
    }

    public function down(): void
    {
        Schema::table('expense_reports', function (Blueprint $table) {
            $table->string('receipt_path')->nullable()->after('amount_ttc');
        });

        Schema::dropIfExists('expense_receipts');
    }
};
