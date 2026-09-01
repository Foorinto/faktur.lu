<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * L'identité de la marque atteint les pages.
 *
 * Le nom vient d'une propriété partagée par Inertia, pas de `VITE_APP_NAME`
 * figé dans le bundle : le jour du changement de dénomination, une variable
 * d'environnement suffira pour ces valeurs, sans recompiler.
 */
class BrandNameReachesThePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_page_receives_the_brand(): void
    {
        $this->get('/fr')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('marque.nom', config('marque.nom'))
                ->where('marque.domaine', config('marque.domaine'))
                ->has('marque.url')
            );
    }

    public function test_the_brand_follows_the_configuration(): void
    {
        config(['marque.nom' => 'kolux.lu', 'marque.domaine' => 'kolux.lu']);

        $this->get('/fr')
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('marque.nom', 'kolux.lu')
            );
    }

    /**
     * Les gabarits Blade lisent la configuration en direct. Le titre de
     * l'application sur iOS en est un cas visible.
     */
    public function test_the_html_shell_follows_the_configuration(): void
    {
        config(['marque.nom' => 'kolux.lu']);

        $this->get('/fr')
            ->assertSee('apple-mobile-web-app-title" content="kolux.lu', false);
    }


    /**
     * Aucun gabarit ne doit plus écrire le nom comme une VALEUR.
     *
     * Ce test manquait, et c'est pour cela que j'ai livré deux fois « c'est
     * fait » alors qu'il restait des métadonnées de partage, un attribut alt et
     * une dizaine de fils d'Ariane. Vérifier fichier par fichier ne dit rien de
     * ce qu'on n'a pas pensé à regarder.
     *
     * Il ne vise QUE les formes où le nom est une valeur d'attribut ou de
     * champ. La prose des traductions reste hors de portée, volontairement.
     */
    public function test_no_template_hardcodes_the_brand_as_a_value(): void
    {
        $motifs = [
            'content="faktur.lu"',
            'content="@fakturlu"',
            'alt="faktur.lu"',
            "'name': 'faktur.lu'",
            '"name": "faktur.lu"',
            "name: 'faktur.lu'",
        ];

        $coupables = [];

        foreach (['resources/js', 'resources/views'] as $racine) {
            $fichiers = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator(base_path($racine))
            );

            foreach ($fichiers as $fichier) {
                if (! $fichier->isFile() || ! preg_match('/\.(vue|blade\.php)$/', $fichier->getFilename())) {
                    continue;
                }

                $contenu = file_get_contents($fichier->getPathname());

                foreach ($motifs as $motif) {
                    if (str_contains($contenu, $motif)) {
                        $coupables[] = str_replace(base_path().'/', '', $fichier->getPathname()).' : '.$motif;
                    }
                }
            }
        }

        $this->assertSame([], $coupables, "Le nom est écrit en dur ici :\n".implode("\n", $coupables));
    }
}
