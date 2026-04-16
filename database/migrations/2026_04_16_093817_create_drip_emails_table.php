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
        Schema::create('drip_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('email_key'); // e.g. 'welcome', 'first_invoice', 'features', etc.
            $table->string('status')->default('sent'); // sent, failed
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'email_key']);
            $table->index('sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drip_emails');
    }
};
