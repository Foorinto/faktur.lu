<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Les ADRESSES publiques qui contiennent le nom de la marque.
 *
 * Trouvées lors d'un balayage des 97 pages françaises, après trois passages
 * qui les avaient manquées : je cherchais dans le code et les gabarits, pas
 * dans la configuration des routes.
 *
 * `/fr/pourquoi-faktur-lu` et ses quatre traductions sont des URL publiques,
 * visibles dans la barre d'adresse et indexées. Elles suivent désormais le
 * nom, ce qui veut dire qu'elles CHANGERONT le jour de la bascule : cinq
 * redirections 301 sont prévues dans la procédure.
 */
class BrandNameInUrlsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ⚠️ Trouvé en préparant le déploiement, le 2026-09-02.
     *
     * Le cookie de session en production s'appelle `fakturlu-session`, or
     * `Str::slug` écrase la casse : impossible de savoir depuis l'extérieur si
     * `APP_NAME` vaut « faktur.lu » ou « Faktur.lu ». C'est aussi un nom
     * d'affichage, une majuscule y est parfaitement légitime.
     *
     * Sans normalisation, une majuscule dans le fichier d'environnement de
     * production suffisait à transformer `/fr/pourquoi-faktur-lu`, qui répond
     * 200 et qui est indexée, en 404, dans les cinq langues, au premier
     * déploiement. Une URL publique ne peut pas dépendre de ça.
     */
    public function test_the_slugs_ignore_the_capitals_of_app_name(): void
    {
        foreach (['faktur.lu', 'Faktur.lu', 'FAKTUR.LU', '  Faktur.LU  '] as $ecriture) {
            putenv("APP_NAME={$ecriture}");
            $_ENV['APP_NAME'] = $ecriture;

            $adresses = require config_path('localized_routes.php');

            $this->assertSame(
                'pourquoi-faktur-lu',
                $adresses['why_faktur']['fr'],
                "APP_NAME=\"{$ecriture}\" ne doit pas changer l'adresse publique"
            );
        }
    }

    public function test_the_why_page_slugs_follow_the_brand(): void
    {
        // ⚠️ Comparé à la FORME attendue, pas à une valeur figée : sinon le
        // test échoue dès qu'on bascule APP_NAME pour essayer. C'est l'erreur
        // que j'avais déjà faite sur l'adresse d'expédition.
        $marque = str_replace('.', '-', mb_strtolower(config('marque.nom')));

        $prefixes = ['fr' => 'pourquoi', 'de' => 'warum', 'en' => 'why', 'lb' => 'firwat', 'pt' => 'porque'];

        foreach ($prefixes as $langue => $prefixe) {
            $this->assertSame(
                "{$prefixe}-{$marque}",
                config("localized_routes.why_faktur.{$langue}"),
                "Adresse de la page « Pourquoi » en {$langue}"
            );
        }
    }

    /**
     * Le point du domaine devient un tiret : une adresse ne contient pas de
     * point au milieu d'un segment.
     */
    public function test_the_dot_becomes_a_hyphen(): void
    {
        $this->assertStringNotContainsString('.', config('localized_routes.why_faktur.fr'));
        $this->assertStringContainsString(
            str_replace('.', '-', config('marque.nom')),
            config('localized_routes.why_faktur.fr')
        );
    }

    /**
     * Les noms alternatifs déclarés aux moteurs viennent de la configuration.
     * Le jour du changement, ils deviendront les anciens noms, ce qui est
     * exactement l'usage d'`alternateName`.
     */
    public function test_the_alternate_names_come_from_the_configuration(): void
    {
        config(['marque.noms_alternatifs' => ['Ancien.lu']]);

        $donnees = json_encode(\App\Support\HomepageStructuredData::build('https://x.lu', 'fr'));

        $this->assertStringContainsString('Ancien.lu', $donnees);
        $this->assertStringNotContainsString('"Faktur"', $donnees);
    }

    /**
     * Plus aucun fichier de configuration n'écrit le nom en dur, hors celui
     * qui le définit et les commentaires.
     */
    public function test_no_configuration_file_hardcodes_the_brand(): void
    {
        $coupables = [];

        foreach (glob(config_path('*.php')) as $fichier) {
            if (basename($fichier) === 'marque.php') {
                continue;
            }

            foreach (file($fichier) as $numero => $ligne) {
                // Le « | » est le style d'en-tête des fichiers de
                // configuration de Laravel. Il manquait ici, et le garde-fou
                // signalait un commentaire comme s'il s'agissait d'une valeur.
                // Aucune ligne de PHP valide ne commence par « | ».
                if (preg_match('/^\s*(\*|\/\/|\/\*|\|)/', $ligne)) {
                    continue;
                }

                // Seule forme admise : le défaut de `env('APP_NAME', …)`,
                // qui n'est pas un nom en dur mais une valeur de repli.
                $ligne = preg_replace("/env\\('APP_NAME',\\s*'[^']*'\\)/", '', $ligne);

                if (preg_match('/[\'"][^\'"]*faktur\.lu/i', $ligne)) {
                    $coupables[] = basename($fichier).':'.($numero + 1);
                }
            }
        }

        $this->assertSame([], $coupables, "Nom en dur dans :\n".implode("\n", $coupables));
    }
}
