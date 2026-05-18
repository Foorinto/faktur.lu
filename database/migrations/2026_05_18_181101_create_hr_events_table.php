<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('title', 200);
            $table->enum('type', ['meeting', 'training', 'team_building', 'deadline', 'other'])->default('meeting');
            $table->string('color', 7)->nullable();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at');
            $table->boolean('is_all_day')->default(false);
            $table->enum('location_type', ['room', 'address', 'video', 'none'])->default('none');
            $table->foreignId('room_id')->nullable()->constrained('rooms')->nullOnDelete();
            $table->string('address', 500)->nullable();
            $table->string('video_url', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('recurrence_rule', 200)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'starts_at', 'ends_at']);
            $table->index(['room_id', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_events');
    }
};
