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
        if (Schema::hasTable('invoice_emails')) {
            return;
        }

        Schema::create('invoice_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'initial', 'reminder_1', 'reminder_2', 'reminder_3', 'manual'
            $table->string('recipient_email');
            $table->string('subject');
            $table->text('body')->nullable();
            $table->string('status')->default('sent'); // 'sent', 'failed'
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index(['invoice_id', 'type']);
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_emails');
    }
};
