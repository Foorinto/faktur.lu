<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamp('added_at')->useCurrent();
            $table->timestamps();

            $table->unique(['project_id', 'employee_id']);
            $table->index(['employee_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_employees');
    }
};
