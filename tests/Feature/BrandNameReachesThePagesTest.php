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
     * ⚠️ AUCUN gabarit ne doit plus contenir le nom, sous aucune forme.
     *
     * Ma version précédente ne cherchait que dans les attributs. Elle a laissé
     * passer le pied de page, les en-têtes de trois espaces, les titres de
     * blog, le DPA et les modèles PDF. Trois fois j'ai annoncé « c'est fait »
     * en vérifiant trop étroitement.
     *
     * La règle est maintenant simple et sans exception : la chaîne
     * « faktur.lu » ne doit apparaître dans aucun fichier .vue ni .blade.php.
     *
     * Les clés de traduction contenant « faktur » ne sont pas visées : elles
     * ne portent jamais le « .lu ». Le vocabulaire allemand non plus.
     */
    public function test_no_template_contains_the_brand_at_all(): void
    {
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

                if (preg_match_all('/[Ff]aktur\.lu/', $contenu, $trouves)) {
                    $coupables[] = str_replace(base_path().'/', '', $fichier->getPathname())
                        .' ('.count($trouves[0]).')';
                }
            }
        }

        $this->assertSame(
            [],
            $coupables,
            "Le nom est encore écrit en dur dans :\n".implode("\n", $coupables)
        );
    }
}
