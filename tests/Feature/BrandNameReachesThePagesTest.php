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
     * ⚠️ Ce qui NE doit pas encore suivre : la prose des traductions. Ce test
     * documente la limite plutôt que de la laisser deviner. Il tombera le jour
     * où le remplacement mécanique sera fait, et ce sera le signal que l'étape
     * est terminée.
     */
    public function test_prose_still_carries_the_old_name(): void
    {
        $this->assertStringContainsString(
            'faktur.lu',
            file_get_contents(resource_path('lang/fr/app.php')),
            "Si ce test échoue, la prose a été traitée : retirez-le."
        );
    }
}
