<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manifestations d'intérêt recueillies sur les pages sectorielles.
 *
 * Table distincte de `newsletter_subscribers`, bien que celle-ci porte déjà une
 * colonne `source` qui aurait pu servir. La raison est juridique : quelqu'un qui
 * répond « je facture sur Excel » n'a pas consenti à recevoir une lettre
 * d'information. Mélanger les deux finalités exposerait à écrire à des gens qui
 * ne l'ont pas demandé — d'où une case à cocher séparée et facultative.
 *
 * L'adresse email est facultative : quelqu'un peut vouloir décrire sa situation
 * sans laisser de contact, et cette réponse-là compte autant pour la mesure.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sector_leads', function (Blueprint $table) {
            $table->id();
            $table->string('sector', 30);
            $table->string('email')->nullable();
            $table->text('message')->nullable();
            $table->string('locale', 5)->default('fr');
            // Consentement explicite et distinct de la réponse elle-même.
            $table->boolean('wants_newsletter')->default(false);
            $table->timestamps();

            // La lecture se fait toujours par secteur, du plus récent au plus
            // ancien : c'est la seule question qu'on posera à cette table.
            $table->index(['sector', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sector_leads');
    }
};
