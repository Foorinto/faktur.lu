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
        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->decimal('quantity', 12, 4);
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 12, 4);
            $table->decimal('vat_rate', 5, 2)->default(17.00);
            $table->decimal('total_ht', 12, 4)->default(0);
            $table->decimal('total_vat', 12, 4)->default(0);
            $table->decimal('total_ttc', 12, 4)->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('quote_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quote_items');
    }
};
