<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La TVA que l'acheteur se facture à lui-même.
     *
     * En autoliquidation, le fournisseur étranger facture hors taxe : c'est
     * l'acheteur qui déclare la TVA, à la fois comme due et comme déductible.
     * Elle n'a donc jamais été payée à personne, et ne peut pas se ranger dans
     * `amount_vat` — celui-là dit ce que porte la facture du fournisseur, et
     * vaut zéro ici. Confondre les deux gonflerait le TTC d'un montant qui
     * n'a pas été débité.
     *
     * D'où deux colonnes à part, nulles pour tout autre régime.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->decimal('reverse_charge_vat_rate', 5, 2)->nullable()->after('vat_regime');
            $table->decimal('reverse_charge_vat', 12, 4)->default(0)->after('reverse_charge_vat_rate');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['reverse_charge_vat_rate', 'reverse_charge_vat']);
        });
    }
};
