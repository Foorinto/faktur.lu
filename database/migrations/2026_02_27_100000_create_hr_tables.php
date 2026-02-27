<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Departments
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->foreignId('parent_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->string('color', 7)->default('#6366f1');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'name']);
        });

        // Employees
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Identity
            $table->string('photo_path')->nullable();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email_pro')->nullable();
            $table->string('email_perso')->nullable();
            $table->string('phone', 30)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('nationality', 2)->nullable();

            // Address
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 2)->default('LU');

            // Contract
            $table->string('contract_type'); // CDI, CDD, Stage, Freelance
            $table->date('contract_start');
            $table->date('contract_end')->nullable();
            $table->date('trial_end_date')->nullable();

            // Position
            $table->string('job_title', 100)->nullable();
            $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('manager_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('work_location', 100)->nullable();

            // Salary
            $table->decimal('salary_gross', 10, 2)->nullable();
            $table->string('salary_currency', 3)->default('EUR');
            $table->json('benefits')->nullable();

            // Banking
            $table->string('bank_iban', 34)->nullable();

            // Emergency contact
            $table->json('emergency_contact')->nullable();

            // Status
            $table->string('status')->default('active');
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'department_id']);
            $table->index(['user_id', 'contract_type']);
            $table->index(['user_id', 'last_name', 'first_name']);
        });

        // Leave types
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('color', 7)->default('#3b82f6');
            $table->integer('default_days_per_year')->default(26);
            $table->boolean('requires_justification')->default(false);
            $table->boolean('is_paid')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'name']);
        });

        // Leave balances
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->integer('year');
            $table->decimal('initial_days', 5, 1)->default(0);
            $table->decimal('used_days', 5, 1)->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'year']);
        });

        // Leave requests
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days_count', 5, 1);
            $table->text('reason')->nullable();
            $table->string('justification_path')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('approved_by')->references('id')->on('employees')->nullOnDelete();
            $table->index(['user_id', 'status']);
            $table->index(['employee_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('departments');
    }
};
