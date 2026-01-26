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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('provider_name');
            $table->string('category'); // hardware, software, hosting, office, travel, training, professional_services, telecommunications, other
            $table->decimal('amount_ht', 12, 4);
            $table->decimal('vat_rate', 5, 2)->default(17.00);
            $table->decimal('amount_vat', 12, 4)->default(0);
            $table->decimal('amount_ttc', 12, 4)->default(0);
            $table->text('description')->nullable();
            $table->boolean('is_deductible')->default(true);
            $table->string('payment_method')->nullable(); // cash, card, transfer, check
            $table->string('reference')->nullable(); // Invoice/receipt reference
            $table->timestamps();
            $table->softDeletes();

            $table->index(['date', 'category']);
            $table->index('provider_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
