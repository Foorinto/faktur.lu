<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Trois champs qui répondent à la même situation : un achat à l'étranger.
     *
     * - `amount_input_mode` : une facture Amazon ne porte qu'un TTC. Mémoriser
     *   le mode de saisie évite de rouvrir la dépense dans l'autre unité que
     *   celle où elle a été saisie.
     * - `supplier_country` : le taux applicable est celui du pays du
     *   fournisseur, pas celui du Luxembourg.
     * - `vat_regime` : c'est lui qui décide si la TVA payée est déductible.
     *   Sans ce champ, la TVA allemande d'un achat Amazon.de remontait dans la
     *   TVA déductible luxembourgeoise du récapitulatif fiscal.
     *
     * Les valeurs par défaut reproduisent exactement le comportement d'avant :
     * les dépenses existantes restent des achats nationaux saisis en HT.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('amount_input_mode', 3)->default('ht')->after('amount_ttc');
            $table->string('supplier_country', 2)->default('LU')->after('provider_name');
            $table->string('vat_regime', 30)->default('national')->after('vat_rate');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropColumn(['amount_input_mode', 'supplier_country', 'vat_regime']);
        });
    }
};
