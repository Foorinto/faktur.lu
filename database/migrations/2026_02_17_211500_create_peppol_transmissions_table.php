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
        Schema::create('peppol_transmissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('pending'); // pending, processing, sent, delivered, failed
            $table->string('document_id')->nullable(); // ID returned by Access Point
            $table->string('recipient_id')->nullable(); // Peppol ID of recipient
            $table->string('recipient_scheme', 10)->nullable(); // Peppol scheme (e.g., 0208 for BE)
            $table->text('error_message')->nullable();
            $table->json('response_data')->nullable(); // Full AP response
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['invoice_id', 'status']);
            $table->index('document_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peppol_transmissions');
    }
};
