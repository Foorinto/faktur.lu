<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Compte de produits par article.
     *
     * Un utilisateur qui refacture des frais de déplacement voyait tout partir
     * sur le compte de ventes générique, ce qui gonflait son chiffre d'affaires
     * d'un montant qui n'en est pas un. Il lui faut pouvoir dire, article par
     * article, que celui-ci relève du 708 et non du 706.
     *
     * Le compte est porté DEUX FOIS, et ce n'est pas une redondance :
     *
     *  - sur l'article, c'est la valeur par défaut, modifiable à tout moment ;
     *  - sur la ligne de facture, c'est ce qui a été retenu au moment de
     *    l'émission. Une facture finalisée est immuable : reclasser un article
     *    l'an prochain ne doit pas réécrire les écritures d'une facture déjà
     *    déclarée. C'est la même raison qui fait recopier le libellé et le prix
     *    plutôt que de pointer vers l'article.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('pcn_account', 10)->nullable()->after('vat_rate');
        });

        Schema::table('invoice_items', function (Blueprint $table) {
            $table->string('pcn_account', 10)->nullable()->after('vat_rate');
        });

        Schema::table('recurring_invoice_items', function (Blueprint $table) {
            $table->string('pcn_account', 10)->nullable()->after('vat_rate');
        });
    }

    public function down(): void
    {
        Schema::table('products', fn (Blueprint $t) => $t->dropColumn('pcn_account'));
        Schema::table('invoice_items', fn (Blueprint $t) => $t->dropColumn('pcn_account'));
        Schema::table('recurring_invoice_items', fn (Blueprint $t) => $t->dropColumn('pcn_account'));
    }
};
