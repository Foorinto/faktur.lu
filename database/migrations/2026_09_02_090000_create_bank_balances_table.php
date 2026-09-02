<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Le relevé de solde bancaire, point de départ de la prévision.
 *
 * Sans lui, le graphique de trésorerie partait de zéro : il ignorait ce qui se
 * trouvait réellement sur le compte, et affichait un solde négatif dès le
 * premier jour. Demandé par un client, qui a proposé lui-même le remède :
 * « soit d'indiquer le solde bancaire en début d'activité, soit en cours de
 * route car les frais et entrées feront varier le solde de toute façon ».
 *
 * D'où un HISTORIQUE et non une valeur unique. On saisit un relevé, on le
 * ressaisit un mois plus tard sans effacer le précédent, et la prévision
 * repart du plus récent. C'est aussi ce qui permet de corriger une dérive :
 * la prévision entre deux relevés reste une estimation, le relevé suivant
 * remet les compteurs à la réalité.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // La date du relevé, pas celle de la saisie : on relève souvent
            // aujourd'hui un solde arrêté hier.
            $table->date('balance_date');

            // Signé : un compte peut être à découvert, et refuser de le saisir
            // rendrait la prévision fausse pour ceux qui en ont le plus besoin.
            $table->decimal('amount', 12, 2);

            $table->string('label')->nullable();

            $table->timestamps();

            // La lecture est toujours la même : le relevé le plus récent de cet
            // utilisateur, à une date donnée.
            $table->index(['user_id', 'balance_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_balances');
    }
};
