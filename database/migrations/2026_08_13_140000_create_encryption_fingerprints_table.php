<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Empreinte de la clé d'application ayant chiffré les données.
     *
     * `APP_KEY` déchiffre les IBAN, la configuration de messagerie et les
     * données personnelles des salariés. Si elle change — régénération
     * malencontreuse, `.env` écrasé, restauration partielle — rien ne casse
     * bruyamment : les lectures échouent une à une, et les écritures suivantes
     * se font sous la nouvelle clé. On se retrouve alors avec deux jeux de
     * données qu'aucune clé unique ne peut plus lire.
     *
     * L'empreinte est un SHA-256 de la clé, jamais la clé : la table n'aide
     * personne à déchiffrer quoi que ce soit, elle sert seulement à constater
     * qu'on n'est plus devant la même.
     */
    public function up(): void
    {
        Schema::create('encryption_fingerprints', function (Blueprint $table) {
            $table->id();
            $table->string('fingerprint', 64);
            $table->timestamp('recorded_at');
            $table->string('note', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('encryption_fingerprints');
    }
};
