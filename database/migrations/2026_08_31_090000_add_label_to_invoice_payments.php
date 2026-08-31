<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Libellé de l'encaissement tel qu'il paraîtra sur la facture.
 *
 * Le PDF déduisait le mot à écrire de la date : versé avant l'émission, donc
 * « acompte ». La déduction est juste la plupart du temps et fausse le reste
 * du temps — et surtout elle décide à la place de l'utilisateur d'un texte que
 * son client va lire sur un document commercial.
 *
 * Nullable, et c'est le point : laissé vide, le libellé automatique s'applique
 * et rien ne change pour qui ne s'en préoccupe pas. Rempli, il gagne.
 *
 * ⚠️ Distinct de `reference`, qui porte la référence bancaire et part dans
 * l'export comptable. Celui-ci ne sert qu'à l'affichage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->string('label', 100)->nullable()->after('method');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }
};
