<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Les montants à l'écran doivent se lire comme ceux des documents.
 *
 * Les PDF, qui font foi et sont archivés dix ans, écrivent
 * `number_format($x, 2, ',', ' ')` : espace pour les milliers, virgule pour
 * les décimales. « 29 906,59 € ». Aucune lecture ambiguë.
 *
 * Huit composants utilisaient `Intl.NumberFormat('fr-LU')`, qui met un POINT :
 * « 29.907 € ». C'est la convention luxembourgeoise, elle n'est pas fautive,
 * mais elle contredit les documents et surtout elle se lit comme un nombre à
 * décimales. Le propriétaire du produit s'y est trompé sur son propre tableau
 * de bord (2026-09-02) : « ce chiffre correspond a quoi ? c'est 29.907 € ou
 * 29907 € ? ». Un utilisateur qui hésite sur un ordre de grandeur de mille ne
 * fait pas confiance à l'écran.
 *
 * Ce test empêche le retour en arrière, et l'apparition d'une troisième
 * convention.
 */
class MontantsLisiblesTest extends TestCase
{
    /**
     * Locales dont le séparateur de milliers est un point, donc confusables
     * avec une virgule décimale par un lecteur francophone.
     */
    private const AMBIGUËS = ['fr-LU', 'de-DE', 'de-LU', 'de-AT', 'it-IT', 'es-ES', 'nl-NL', 'pt-PT', 'lb-LU'];

    public function test_aucun_composant_ne_formate_les_montants_avec_un_point(): void
    {
        $racine = dirname(__DIR__, 2).'/resources/js';

        $fichiers = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($racine));
        $coupables = [];

        foreach ($fichiers as $fichier) {
            if (! $fichier->isFile() || ! in_array($fichier->getExtension(), ['vue', 'js'])) {
                continue;
            }

            $contenu = file_get_contents($fichier->getPathname());

            foreach (self::AMBIGUËS as $locale) {
                if (preg_match("/NumberFormat\\(['\"]".preg_quote($locale, '/')."['\"]/", $contenu)) {
                    $coupables[] = str_replace($racine.'/', '', $fichier->getPathname()).' → '.$locale;
                }
            }
        }

        sort($coupables);

        $this->assertSame([], $coupables, sprintf(
            "Ces fichiers formatent des montants avec un point pour les milliers :\n%s\n\n".
            "« 29.907 € » se lit comme 29,907 et contredit les PDF, qui écrivent « 29 906,59 € ».\n".
            "Utiliser 'fr-FR', comme les 38 autres appels du dépôt.",
            implode("\n", $coupables)
        ));
    }
}
