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
        Schema::create('accounting_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('sales_account', 20)->default('702000');
            $table->json('vat_collected_accounts')->nullable(); // {"17":"461100","14":"461400","8":"461800","3":"461300"}
            $table->string('clients_account', 20)->default('411000');
            $table->string('bank_account', 20)->default('512000');
            $table->string('sales_journal', 10)->default('VE');
            $table->string('client_prefix', 10)->default('C');
            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_settings');
    }
};
