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
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');                    // Nom commercial
            $table->string('legal_name');                      // Nom légal complet
            $table->text('address');                           // Adresse ligne 1
            $table->string('postal_code', 10);
            $table->string('city');
            $table->string('country_code', 2)->default('LU');
            $table->string('vat_number', 11)->nullable();      // LU12345678
            $table->string('matricule', 11);                   // 11 chiffres obligatoires
            $table->string('iban', 34);
            $table->string('bic', 11);
            $table->enum('vat_regime', ['assujetti', 'franchise'])->default('franchise');
            $table->string('phone')->nullable();
            $table->string('email');
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_settings');
    }
};
