<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Les deux champs RH chiffrés en tableau passent de JSON à texte.
 *
 * Depuis le chiffrement des données RH (2026_08_13), `emergency_contact` et
 * `benefits` reçoivent un texte chiffré, qui n'est pas du JSON. La migration
 * d'août avait élargi les colonnes de texte, pas ces deux-là. MySQL et
 * MariaDB n'acceptent que du JSON valide dans une colonne JSON : enregistrer
 * un contact d'urgence ou des avantages échouait en production. SQLite, en
 * test, ne contrôle rien, d'où un défaut resté invisible (constaté le
 * 26/09/2026).
 *
 * Les valeurs présentes sont conservées telles quelles : un JSON reste un
 * texte valide.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->text('emergency_contact')->nullable()->change();
            $table->text('benefits')->nullable()->change();
        });
    }

    /**
     * Pas de retour au JSON : les valeurs chiffrées n'y rentreraient pas, et
     * le retour arrière échouerait sur la première ligne renseignée.
     */
    public function down(): void {}
};
