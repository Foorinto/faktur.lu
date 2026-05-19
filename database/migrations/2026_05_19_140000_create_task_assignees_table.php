<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('task_assignees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['task_id', 'employee_id'], 'uniq_task_employee');
            $table->unique(['task_id', 'user_id'], 'uniq_task_user');
            $table->index('employee_id');
            $table->index('user_id');
        });

        // Backfill existing single-assignee rows into the new pivot
        DB::table('tasks')
            ->whereNotNull('assigned_to_employee_id')
            ->orderBy('id')
            ->chunkById(500, function ($tasks) {
                $rows = [];
                $now = now();
                foreach ($tasks as $t) {
                    $rows[] = [
                        'task_id' => $t->id,
                        'employee_id' => $t->assigned_to_employee_id,
                        'user_id' => null,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if ($rows) DB::table('task_assignees')->insertOrIgnore($rows);
            });

        DB::table('tasks')
            ->whereNotNull('assigned_to_user_id')
            ->orderBy('id')
            ->chunkById(500, function ($tasks) {
                $rows = [];
                $now = now();
                foreach ($tasks as $t) {
                    $rows[] = [
                        'task_id' => $t->id,
                        'employee_id' => null,
                        'user_id' => $t->assigned_to_user_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                if ($rows) DB::table('task_assignees')->insertOrIgnore($rows);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_assignees');
    }
};
