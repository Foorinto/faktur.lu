<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Comptes à vérifier (FEAT-138).
 *
 * Posé quand un nom imite une marque (« Vinted Support ») à l'inscription,
 * ou quand un compte tente d'en prendre une comme nom d'entreprise. Rien
 * n'est bloqué par ce drapeau : il alimente le filtre « À vérifier » de
 * l'administration et l'alerte envoyée au premier document du compte.
 *
 * Purement additive : un compte existant reste non signalé.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('flagged_for_review')->default(false)->after('is_active');
            $table->string('flagged_reason')->nullable()->after('flagged_for_review');
            $table->timestamp('flagged_at')->nullable()->after('flagged_reason');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['flagged_for_review', 'flagged_reason', 'flagged_at']);
        });
    }
};
