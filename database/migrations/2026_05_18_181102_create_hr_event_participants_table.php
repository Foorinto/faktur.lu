<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_event_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hr_event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->cascadeOnDelete();
            $table->string('external_email', 255)->nullable();
            $table->enum('response', ['pending', 'accepted', 'declined'])->default('pending');
            $table->timestamps();

            $table->unique(['hr_event_id', 'employee_id'], 'uniq_event_employee');
            $table->unique(['hr_event_id', 'external_email'], 'uniq_event_external');
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_event_participants');
    }
};
