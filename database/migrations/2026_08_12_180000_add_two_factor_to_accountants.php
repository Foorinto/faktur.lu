<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Double facteur sur le portail comptable.
     *
     * Un comptable accède aux données de plusieurs entreprises à la fois : sur
     * ce compte-là, le mot de passe seul protège davantage de monde qu'ailleurs,
     * et le compromettre en compromet plusieurs d'un coup. Le second facteur y
     * devient obligatoire, quand il reste optionnel sur un compte utilisateur
     * qui n'expose que ses propres données.
     *
     * Colonnes reprises telles quelles de Fortify : le trait
     * TwoFactorAuthenticatable est agnostique du guard, il fonctionne sur ce
     * modèle sans adaptation.
     */
    public function up(): void
    {
        Schema::table('accountants', function (Blueprint $table) {
            $table->text('two_factor_secret')->nullable()->after('password');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');
        });
    }

    public function down(): void
    {
        Schema::table('accountants', function (Blueprint $table) {
            $table->dropColumn(['two_factor_secret', 'two_factor_recovery_codes', 'two_factor_confirmed_at']);
        });
    }
};
