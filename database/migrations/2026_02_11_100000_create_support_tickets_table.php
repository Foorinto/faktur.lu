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
        if (Schema::hasTable('support_tickets')) {
            return;
        }

        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // TKT-2026-0001
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('assigned_admin_id')->nullable();
            $table->string('subject');
            $table->string('category'); // general, technical, billing, suggestion, other
            $table->string('status')->default('new'); // new, open, in_progress, waiting, resolved, closed
            $table->string('priority')->default('normal'); // low, normal, high, urgent
            $table->timestamp('first_response_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'priority']);
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
