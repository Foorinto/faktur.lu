<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * La racine ne rend aucune page : elle redirige vers la version localisée,
     * toutes les routes publiques étant préfixées par {locale}.
     */
    public function test_the_root_redirects_to_a_localized_homepage(): void
    {
        $this->get('/')->assertRedirect();
    }

    public function test_the_localized_homepage_is_served(): void
    {
        $this->get('/fr')->assertOk();
    }
}
