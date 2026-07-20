<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catalogue of reusable products/services (FEAT-095).
     * Each row is owned by a user (multi-tenant) and can be inserted into an
     * invoice/quote/recurring line to pre-fill designation, price, VAT and unit.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('designation');
            $table->text('description')->nullable();
            $table->string('reference')->nullable(); // free-text SKU / internal ref
            $table->decimal('unit_price_ht', 12, 4)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(17);
            $table->string('unit')->default('piece');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index('user_id');
            $table->index(['user_id', 'designation']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
