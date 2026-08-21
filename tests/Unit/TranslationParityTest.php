<?php

namespace Tests\Unit;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Toute clé de traduction française doit exister dans les quatre autres langues.
 *
 * Le test voisin, `TranslationKeysTest`, ne couvre pas ce cas : il scanne les
 * fichiers Vue à la recherche de `t('…')` avec une expression qui n'accepte ni
 * point ni tiret. Une clé rendue côté serveur — le titre d'une page de
 * fonctionnalité, par exemple — lui échappe par construction.
 *
 * Ce qu'on ne voyait donc pas, découvert le 2026-08-18 : la page
 * `/features/numerotation-personnalisable` n'était traduite qu'en français, et
 * affichait QUATORZE clés brutes aux visiteurs anglophones, germanophones,
 * luxembourgophones et lusophones — titre de la page compris. Vingt-deux clés
 * d'interface manquaient par ailleurs en allemand et en luxembourgeois, dont
 * les messages de fin d'essai.
 *
 * Une traduction manquante ne lève aucune erreur : Laravel affiche la clé. Rien
 * ne le signale, sauf à ouvrir la page dans chaque langue.
 */
class TranslationParityTest extends TestCase
{
    private const REFERENCE = 'fr';

    private const AUTRES = ['en', 'de', 'lb', 'pt'];

    /**
     * Préfixes légitimement absents des autres langues.
     *
     * Les pages « alternative à » ne sont publiées qu'en français : les traduire
     * serait du travail éditorial, pas une correction de bug. Toute autre
     * exception doit être justifiée ici, faute de quoi ce test ne protège plus
     * rien.
     *
     * @var array<int, string>
     */
    private const EXCEPTIONS = [
        // Pages « alternative à » : publiées en français seulement.
        'alternatives.',
        // Pages sectorielles : mêmes raisons, et ce sont des instruments de
        // mesure avant d'être du contenu. Les traduire avant de savoir si le
        // marché existe reviendrait à investir sur une supposition.
        'sector_pages.',
    ];

    /** Aplatit le tableau de traductions en clés pointées. */
    private function clesPlates(array $traductions, string $prefixe = ''): array
    {
        $plat = [];

        foreach ($traductions as $cle => $valeur) {
            $complete = $prefixe === '' ? (string) $cle : "{$prefixe}.{$cle}";

            if (is_array($valeur)) {
                $plat += $this->clesPlates($valeur, $complete);

                continue;
            }

            $plat[$complete] = true;
        }

        return $plat;
    }

    private function cles(string $locale): array
    {
        return $this->clesPlates(require base_path("resources/lang/{$locale}/app.php"));
    }

    public static function autresLangues(): array
    {
        return array_combine(self::AUTRES, array_map(fn ($l) => [$l], self::AUTRES));
    }

    #[DataProvider('autresLangues')]
    public function test_a_locale_carries_every_french_key(string $locale): void
    {
        $manquantes = array_filter(
            array_keys(array_diff_key($this->cles(self::REFERENCE), $this->cles($locale))),
            fn (string $cle) => ! $this->estExemptee($cle)
        );

        sort($manquantes);

        $this->assertSame([], $manquantes, sprintf(
            "%d clé(s) absente(s) de resources/lang/%s/app.php. Laravel affichera le nom de la clé à la place du texte :\n  - %s\n",
            count($manquantes),
            $locale,
            implode("\n  - ", array_slice($manquantes, 0, 30))
        ));
    }

    /**
     * L'inverse compte aussi : une clé qui n'existe QUE dans une autre langue
     * est du code mort, ou le signe que le français a régressé.
     */
    #[DataProvider('autresLangues')]
    public function test_a_locale_carries_no_orphan_key(string $locale): void
    {
        $orphelines = array_keys(array_diff_key($this->cles($locale), $this->cles(self::REFERENCE)));

        sort($orphelines);

        $this->assertSame([], $orphelines, sprintf(
            "Clé(s) présente(s) en %s mais absente(s) du français :\n  - %s\n",
            $locale,
            implode("\n  - ", array_slice($orphelines, 0, 30))
        ));
    }

    private function estExemptee(string $cle): bool
    {
        foreach (self::EXCEPTIONS as $prefixe) {
            if (str_starts_with($cle, $prefixe)) {
                return true;
            }
        }

        return false;
    }
}
