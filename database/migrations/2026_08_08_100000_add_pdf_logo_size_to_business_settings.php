<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Taille du logo sur les documents PDF.
 *
 * Le logo était plafonné à 120 x 60 px. Un utilisateur l'a trouvé « minuscule
 * et presque illisible », d'autant plus depuis le réglage de taille du texte.
 *
 * Une première version le faisait suivre l'échelle du texte. C'était une
 * approximation commode mais fausse : la lisibilité d'un logo dépend du logo
 * lui-même, de sa forme et de son niveau de détail, pas de la taille des
 * caractères autour. D'où un réglage distinct.
 *
 * `normal` par défaut, ce qui laisse tous les comptes existants au rendu
 * qu'ils connaissent.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->string('pdf_logo_size', 10)->nullable()->default('normal')->after('pdf_text_size');
        });
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn('pdf_logo_size');
        });
    }
};
