<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comptes nécessaires à l'export des achats (FEAT-107).
 *
 * `AccountingExportService` ne construisait des écritures qu'à partir des
 * factures de vente. Exporter les dépenses demande quatre comptes de plus, que
 * le paramétrage ne prévoyait pas.
 *
 * Les valeurs par défaut viennent du plan comptable normalisé 2020 et ont été
 * relevées dans le classeur officiel de la CNC, non déduites de mémoire :
 *
 *   44111  Fournisseurs (dettes sur achats, durée résiduelle ≤ 1 an)
 *   421611 TVA en amont
 *   421811 TVA étrangères
 *   6188   Autres charges externes diverses
 *
 * Elles restent modifiables : le rattachement définitif appartient à la
 * fiduciaire, qui connaît l'activité.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->string('purchase_journal', 8)->default('AC')->after('sales_journal');
            $table->string('suppliers_account', 10)->default('44111')->after('clients_account');

            // TVA payée en amont, récupérable sur la déclaration luxembourgeoise.
            $table->string('vat_deductible_account', 10)->default('421611')->after('vat_collected_accounts');

            // La TVA facturée par un fournisseur étranger ne se déduit pas ici :
            // elle se récupère par une procédure distincte, d'où un compte à part.
            $table->string('vat_foreign_account', 10)->default('421811')->after('vat_deductible_account');

            // Utilisé quand la catégorie de dépense ne porte pas de compte.
            $table->string('default_expense_account', 10)->default('6188')->after('sales_account');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->dropColumn([
                'purchase_journal',
                'suppliers_account',
                'vat_deductible_account',
                'vat_foreign_account',
                'default_expense_account',
            ]);
        });
    }
};
