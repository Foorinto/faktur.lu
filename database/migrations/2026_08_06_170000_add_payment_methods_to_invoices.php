<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Moyens de paiement propres à une facture (FEAT-098).
 *
 * Le réglage d'entreprise fixe déjà des moyens par défaut, mais le besoin
 * d'origine était de pouvoir en changer au cas par cas :
 *
 *     « Payconiq pour certains cas ou cash pour les clients privés »
 *
 * Un réglage global ne répond pas à « pour certains cas ».
 *
 * `null` signifie « rien de précisé » et non « aucun moyen » : la facture suit
 * alors le réglage d'entreprise, lui-même replié sur le virement. Toutes les
 * factures existantes reçoivent donc `null` et se rendent exactement comme
 * avant.
 *
 * La colonne appartient à la facture, pas à un instantané : l'immuabilité des
 * documents finalisés la protège déjà.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->json('payment_methods')->nullable()->after('footer_message');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('payment_methods');
        });
    }
};
