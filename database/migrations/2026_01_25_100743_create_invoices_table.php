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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('number', 20)->nullable()->unique(); // null = draft
            $table->enum('status', ['draft', 'finalized', 'sent', 'paid', 'cancelled'])->default('draft');
            $table->enum('type', ['invoice', 'credit_note'])->default('invoice');
            $table->foreignId('credit_note_for')->nullable()->constrained('invoices')->nullOnDelete();

            // Snapshots JSON pour immutabilité
            $table->json('seller_snapshot')->nullable(); // BusinessSettings au moment T
            $table->json('buyer_snapshot')->nullable();  // Client au moment T

            // Montants avec précision
            $table->decimal('total_ht', 12, 4)->default(0);
            $table->decimal('total_vat', 12, 4)->default(0);
            $table->decimal('total_ttc', 12, 4)->default(0);

            // Dates
            $table->date('issued_at')->nullable();
            $table->date('due_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Notes et références
            $table->text('notes')->nullable();
            $table->string('payment_reference')->nullable();
            $table->string('currency', 3)->default('EUR');

            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'issued_at']);
            $table->index('client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
