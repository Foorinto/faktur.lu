<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Toute URL déclarée au sitemap doit exister.
 *
 * Le sitemap construit ses adresses depuis `config/localized_routes.php`, une
 * table de slugs par langue. Rien ne garantissait qu'un slug déclaré
 * corresponde à une route : le portugais annonçait `/pt/contacto` quand la
 * seule route servie est `/pt/contact`. L'URL partait donc aux moteurs, qui la
 * comptaient en « Introuvable » — treize dans Search Console.
 *
 * Une page absente du sitemap n'est pas indexée ; une page au sitemap qui
 * renvoie 404 est pire : elle consomme du budget d'exploration pour rien.
 */
class SitemapUrlsResolveTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_static_url_in_the_sitemap_is_reachable(): void
    {
        $xml = $this->get('/sitemap-pages.xml')->assertOk()->getContent();

        preg_match_all('#<loc>\s*(.*?)\s*</loc>#i', $xml, $trouvees);

        $introuvables = [];

        foreach ($trouvees[1] as $url) {
            $chemin = '/'.ltrim((string) parse_url(html_entity_decode($url), PHP_URL_PATH), '/');

            if (! $this->cheminExiste($chemin)) {
                $introuvables[] = $url;
            }
        }

        $this->assertSame([], $introuvables, sprintf(
            "%d URL du sitemap ne correspondent à aucune route :\n  - %s\n",
            count($introuvables),
            implode("\n  - ", array_slice($introuvables, 0, 15))
        ));
    }

    /** Le chemin est-il servi par une route GET ? */
    private function cheminExiste(string $chemin): bool
    {
        $requete = \Illuminate\Http\Request::create($chemin, 'GET');

        try {
            Route::getRoutes()->match($requete);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }
}
