<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Le portail comptable n'est plus inclus dans le plan Gratuit (réservé à
     * Essentiel+). Pour ne casser aucun client existant, tous les comptes créés
     * avant cette bascule conservent l'accès via ce drapeau (grandfathering).
     * Les nouveaux comptes l'ont à false par défaut.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('accounting_portal_grandfathered')->default(false);
        });

        // Grandfather : tous les utilisateurs existant à la date de la bascule.
        DB::table('users')->update(['accounting_portal_grandfathered' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('accounting_portal_grandfathered');
        });
    }
};
