<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('color', 7)->nullable();
            $table->string('icon', 30)->nullable();
            $table->decimal('max_amount', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('expense_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_category_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->text('description')->nullable();
            $table->string('vendor', 255);
            $table->decimal('amount_ht', 12, 4);
            $table->decimal('vat_rate', 5, 2)->default(17.00);
            $table->decimal('amount_vat', 12, 4);
            $table->decimal('amount_ttc', 12, 4);
            $table->string('receipt_path')->nullable();
            $table->string('payment_method', 20)->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('approved_by')->references('id')->on('employees')->nullOnDelete();
            $table->index(['user_id', 'status']);
            $table->index(['employee_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_reports');
        Schema::dropIfExists('expense_categories');
    }
};
