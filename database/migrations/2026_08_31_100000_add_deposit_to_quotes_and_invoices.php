<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Acompte demandé, sur le devis et sur la facture qui en découle.
 *
 * Demande d'un client payant, coiffure-esthétique : sa cliente règle un acompte
 * à la signature du devis, et c'est ce versement qui fixe le rendez-vous. Le
 * devis — sa « confirmation de commande » — doit donc annoncer le montant
 * attendu, noir sur blanc.
 *
 * ⚠️ C'est une DEMANDE, pas un encaissement. Rien ici ne touche aux totaux :
 * l'acompte ne réduit ni la base taxable, ni la TVA, ni le montant du devis.
 * Le versement réel, lui, s'enregistre dans `invoice_payments` une fois la
 * facture émise, et c'est lui seul qui compte en comptabilité.
 *
 * `deposit_type` vaut 'percent' ou 'amount' : l'acompte est en principe un
 * pourcentage, mais « il peut être variable et parfois juste un montant fixe ».
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['quotes', 'invoices'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->string('deposit_type', 10)->nullable();
                // 12,4 comme les autres montants du dépôt : un pourcentage y
                // tient aussi bien qu'une somme.
                $blueprint->decimal('deposit_value', 12, 4)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['quotes', 'invoices'] as $table) {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->dropColumn(['deposit_type', 'deposit_value']);
            });
        }
    }
};
