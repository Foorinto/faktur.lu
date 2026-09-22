<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gel du compte par son titulaire, et mémoire de l'ancienne adresse email
 * (FEAT-122, étape 2 du plan de sécurité).
 *
 * `security_locked_at` : posé par le lien « ce n'était pas moi » d'une alerte
 * de sécurité. Tant qu'il est là, la connexion est refusée ; seule une
 * réinitialisation du mot de passe l'efface.
 *
 * `previous_email` et `email_changed_at` : quand l'IBAN change peu après
 * l'adresse email, l'ancienne adresse reçoit l'alerte aussi. C'est le seul
 * canal qui reste au titulaire si le changement d'adresse n'était pas de
 * son fait.
 *
 * Purement additive : trois colonnes vides, un compte existant se comporte
 * exactement comme avant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('security_locked_at')->nullable()->after('remember_token');
            $table->string('previous_email')->nullable()->after('security_locked_at');
            $table->timestamp('email_changed_at')->nullable()->after('previous_email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['security_locked_at', 'previous_email', 'email_changed_at']);
        });
    }
};
