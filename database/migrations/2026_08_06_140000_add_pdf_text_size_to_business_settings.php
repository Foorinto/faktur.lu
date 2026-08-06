<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Taille du texte des documents PDF (FEAT-109).
 *
 * Le gabarit de facture est réglé à 8 pt, avec des mentions à 7 pt et une à
 * 6 pt. C'est en dessous de ce qui se lit couramment sur une facture, et
 * l'interlignage serré aggrave l'effet. Un utilisateur l'a signalé ; le constat
 * est mesurable, pas affaire de goût.
 *
 * On stocke un **choix** (`normal`, `large`, `xlarge`) et non un facteur
 * numérique : la valeur atterrit sur un document légal, et laisser saisir un
 * coefficient libre ouvrirait la porte à des rendus cassés sans rien apporter.
 * La correspondance vit dans `BusinessSettings::PDF_TEXT_SIZES`.
 *
 * `normal` par défaut : aucun compte existant ne voit son rendu changer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->string('pdf_text_size', 10)->nullable()->default('normal')->after('default_pdf_color');
        });
    }

    public function down(): void
    {
        Schema::table('business_settings', function (Blueprint $table) {
            $table->dropColumn('pdf_text_size');
        });
    }
};
