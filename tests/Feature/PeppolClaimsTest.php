<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

/**
 * Ce que le site affirme sur Peppol doit rester vrai.
 *
 * faktur.lu GÉNÈRE aujourd'hui des factures au format Peppol BIS 3.0 et UBL
 * 2.1, conformes au mandat B2G. Il ne TRANSMET pas encore : la liaison à un
 * Access Point certifié attend l'obligation B2B.
 *
 * La distinction n'est pas cosmétique. Cinq endroits du site présentaient
 * l'Access Point comme acquis, dont la ligne d'un comparatif nommant un
 * concurrent. Affirmer dans une publicité comparative une capacité que sa
 * propre FAQ dit inachevée est le genre de contradiction interne par laquelle
 * commence une mise en demeure.
 *
 * Ce test interdit qu'une formulation non qualifiée revienne.
 */
class PeppolClaimsTest extends TestCase
{
    private const LOCALES = ['fr', 'en', 'de', 'lb', 'pt'];

    /**
     * Formulations acceptables autour d'« Access Point » : elles annoncent une
     * capacité à venir, ou définissent le terme.
     *
     * @var array<int, string>
     */
    private const QUALIFICATIFS = [
        'à venir', 'a venir', 'suit', 'arrive', 'finalisation', 'prêt', 'prett',
        'coming', 'follows', 'ready',
        'kënnt', 'kommt', 'folgt', 'Vorbereitung', 'Virbereedung', 'Kuerz', 'geschwënn', 'bereit',
        'em breve', 'segue', 'chega', 'finaliz', 'pronta',
        // Entrées de glossaire : elles nomment le concept, elles ne le promettent pas.
        "'name' =>", "'alternate' =>",
    ];

    public function test_no_locale_claims_an_access_point_as_delivered(): void
    {
        $fautives = [];

        foreach (self::LOCALES as $locale) {
            $lignes = file(base_path("resources/lang/{$locale}/app.php"));

            foreach ($lignes as $numero => $ligne) {
                if (! str_contains($ligne, 'Access Point')) {
                    continue;
                }

                if ($this->estQualifiee($ligne)) {
                    continue;
                }

                $fautives[] = sprintf('%s:%d  %s', $locale, $numero + 1, trim(mb_substr($ligne, 0, 100)));
            }
        }

        $this->assertSame([], $fautives, sprintf(
            "Ces formulations présentent l'Access Point comme acquis, alors que la transmission n'existe pas encore :\n  - %s\n",
            implode("\n  - ", $fautives)
        ));
    }

    /** La capacité réelle doit rester annoncée : le correctif ne doit pas tout effacer. */
    public function test_the_format_generation_is_still_advertised(): void
    {
        foreach (self::LOCALES as $locale) {
            $contenu = file_get_contents(base_path("resources/lang/{$locale}/app.php"));

            $this->assertStringContainsString('Peppol BIS 3.0', $contenu,
                "La génération au format Peppol BIS 3.0 doit rester annoncée en {$locale} : elle est vraie.");
        }
    }

    private function estQualifiee(string $ligne): bool
    {
        foreach (self::QUALIFICATIFS as $mot) {
            if (mb_stripos($ligne, $mot) !== false) {
                return true;
            }
        }

        return false;
    }
}
