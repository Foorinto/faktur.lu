<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le second facteur par e-mail et son obligation pour les comptes exposés
 * (FEAT-124, étape 4 du plan de sécurité).
 *
 * Sur `users` : l'activation du code par e-mail, le code en cours (haché, avec
 * son expiration et ses essais), et l'échéance posée aux comptes exposés qui
 * n'ont encore aucun second facteur. `trusted_devices` porte les appareils
 * mémorisés : l'empreinte du jeton que le navigateur garde en cookie.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('email_otp_enabled_at')->nullable();
            $table->string('email_otp_code', 64)->nullable();
            $table->timestamp('email_otp_expires_at')->nullable();
            $table->unsignedTinyInteger('email_otp_attempts')->default(0);
            $table->timestamp('email_otp_sent_at')->nullable();
            $table->timestamp('two_factor_deadline_at')->nullable();
            $table->timestamp('two_factor_reminded_at')->nullable();
        });

        Schema::create('trusted_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 64)->unique();
            $table->string('user_agent', 255)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('last_used_at')->nullable();
            // DATETIME, pas TIMESTAMP : sur MariaDB, un TIMESTAMP NOT NULL sans
            // défaut peut recevoir un ON UPDATE CURRENT_TIMESTAMP implicite, qui
            // remettrait l'expiration à « maintenant » à chaque mise à jour du
            // dernier usage. Invisible sur SQLite.
            $table->dateTime('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trusted_devices');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'email_otp_enabled_at',
                'email_otp_code',
                'email_otp_expires_at',
                'email_otp_attempts',
                'email_otp_sent_at',
                'two_factor_deadline_at',
                'two_factor_reminded_at',
            ]);
        });
    }
};
