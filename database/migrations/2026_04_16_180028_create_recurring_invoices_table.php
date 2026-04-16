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
        Schema::create('recurring_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->string('frequency'); // weekly, monthly, quarterly, yearly
            $table->date('next_invoice_date');
            $table->date('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('auto_finalize')->default(false);
            $table->boolean('auto_send')->default(false);
            $table->integer('payment_delay_days')->default(30);
            $table->text('notes')->nullable();
            $table->string('vat_mention')->nullable();
            $table->string('custom_vat_mention')->nullable();
            $table->text('footer_message')->nullable();
            $table->string('currency', 3)->default('EUR');
            $table->unsignedInteger('invoices_generated')->default(0);
            $table->foreignId('last_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->timestamps();

            $table->index(['is_active', 'next_invoice_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_invoices');
    }
};
