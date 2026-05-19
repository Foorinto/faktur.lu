<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('idx_tasks_proj_emp');
            $table->dropIndex('idx_tasks_proj_user');
            $table->dropConstrainedForeignId('assigned_to_employee_id');
            $table->dropConstrainedForeignId('assigned_to_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('assigned_to_employee_id')->nullable()->after('project_id')->constrained('employees')->nullOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->after('assigned_to_employee_id')->constrained('users')->nullOnDelete();
            $table->index(['project_id', 'assigned_to_employee_id'], 'idx_tasks_proj_emp');
            $table->index(['project_id', 'assigned_to_user_id'], 'idx_tasks_proj_user');
        });
    }
};
