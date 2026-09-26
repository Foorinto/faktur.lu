<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des protections anti-abus (FEAT-138), pour le tableau de bord
 * d'administration : adresses jetables ou réservées refusées, noms de
 * marques signalés ou refusés, plafond d'envois de l'essai atteint.
 *
 * Ni adresse e-mail complète ni adresse IP : le domaine ou la marque en
 * cause suffit à lire une tendance. Purgé après 90 jours par
 * `monitoring:cleanup`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abuse_events', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->string('detail', 191)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['type', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abuse_events');
    }
};
