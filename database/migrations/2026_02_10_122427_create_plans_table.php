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
        if (Schema::hasTable('plans')) {
            return;
        }

        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // starter, pro
            $table->string('display_name'); // Starter, Pro
            $table->text('description')->nullable();
            $table->integer('price_monthly')->default(0); // in cents
            $table->integer('price_yearly')->default(0); // in cents
            $table->string('stripe_price_id_monthly')->nullable();
            $table->string('stripe_price_id_yearly')->nullable();
            $table->json('limits'); // max_clients, max_invoices_per_month, etc.
            $table->json('features'); // faia_export, pdf_archive, email_reminders, etc.
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
