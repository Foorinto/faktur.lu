<?php

namespace Tests\Feature;

use App\Http\Controllers\SectorPageController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le sélecteur de langue ne doit jamais mener nulle part.
 *
 * Il fonctionne en échangeant le préfixe de langue du chemin d'où vient le
 * lecteur, en traduisant au passage les slugs qu'il connaît. Tout ce qu'il ne
 * connaît pas passe tel quel — et si la traduction littérale ne correspond à
 * aucune route, le lecteur reçoit un 404 pour avoir cliqué sur un drapeau.
 *
 * C'est arrivé deux fois. Les pages « alternative à », françaises seulement,
 * renvoyaient un 404 depuis leur publication. Les pages sectorielles ont
 * reproduit le défaut le 2026-08-21, parce que leur chemin est un motif —
 * `…-{metier}-…` — et non un slug fixe que la table pouvait contenir.
 *
 * Aucun test ne couvrait ce sélecteur, ce qui explique la durée du premier cas.
 */
class LocaleSwitchTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<int, string> */
    private function langues(): array
    {
        return array_keys(SectorPageController::URL_PATTERNS);
    }

    private function basculer(string $depuis, string $vers): string
    {
        return $this->get("/switch-locale/{$vers}", ['Referer' => config('app.url').$depuis])
            ->headers->get('Location') ?? '';
    }

    /**
     * Toutes les bascules entre pages sectorielles mènent à la bonne page.
     *
     * Les slugs diffèrent d'une langue à l'autre : « krankenpfleger » et
     * « infirmier » désignent la même page. Un simple échange de préfixe donne
     * une adresse qui n'existe pas.
     */
    public function test_switching_between_sector_pages_lands_on_the_same_trade(): void
    {
        foreach (SectorPageController::pageKeys() as $metier) {
            foreach ($this->langues() as $depuis) {
                foreach ($this->langues() as $vers) {
                    $attendu = SectorPageController::pathFor($metier, $vers);
                    $obtenu = $this->basculer(SectorPageController::pathFor($metier, $depuis), $vers);

                    $this->assertStringEndsWith($attendu, $obtenu,
                        "{$metier} : {$depuis} → {$vers} devrait mener à {$attendu}."
                    );
                }
            }
        }
    }

    /**
     * Et la page d'arrivée répond vraiment.
     *
     * Une redirection vers une adresse inexistante est un 404 de plus, pas une
     * correction : c'est exactement ce que faisait l'ancien comportement.
     */
    public function test_the_page_reached_after_switching_actually_answers(): void
    {
        foreach ($this->langues() as $vers) {
            $chemin = $this->basculer('/de/rechnungssoftware-krankenpfleger-luxemburg', $vers);

            $this->get($chemin)->assertOk();
        }
    }

    /**
     * Une page qui n'existe que dans une langue renvoie à l'accueil.
     *
     * Les comparatifs « alternative à » sont français seulement. Leur chemin
     * sous un préfixe allemand ne correspond à aucune route. L'accueil est une
     * mauvaise réponse — le lecteur perd sa page — mais une impasse en est une
     * pire.
     */
    public function test_a_page_that_exists_in_one_language_only_falls_back_to_the_home_page(): void
    {
        $obtenu = $this->basculer('/fr/alternative-sage-luxembourg', 'de');

        $this->assertStringEndsWith('/de', $obtenu,
            'Une page sans équivalent doit renvoyer à l\'accueil, pas à un 404.'
        );

        $this->get($obtenu)->assertOk();
    }

    /**
     * Ce qui fonctionnait doit continuer de fonctionner.
     *
     * Le filet ne doit pas se substituer à la table des slugs : renvoyer tout
     * le monde à l'accueil « réparerait » aussi les 404, en cassant tout le
     * reste sans que rien ne le signale.
     */
    public function test_known_localized_slugs_keep_their_translation(): void
    {
        $cas = [
            ['/fr/a-propos', 'de', '/de/ueber-uns'],
            ['/fr/outils/calculateur-tva', 'pt', '/pt/ferramentas/calculadora-iva'],
            ['/fr/mentions-legales', 'de', '/de/impressum'],
            ['/fr/tarifs', 'en', '/en/pricing'],
            ['/fr/blog', 'en', '/en/blog'],
            ['/fr', 'pt', '/pt'],
        ];

        foreach ($cas as [$depuis, $vers, $attendu]) {
            $this->assertStringEndsWith($attendu, $this->basculer($depuis, $vers),
                "{$depuis} → {$vers} devrait mener à {$attendu}."
            );
        }
    }

    /**
     * Le filet suppose qu'aucune route ne correspond à tout.
     *
     * `Route::fallback` ferait correspondre n'importe quel chemin : le filet
     * cesserait de protéger quoi que ce soit, en silence, et ce fichier
     * continuerait de passer au vert sur les cas qu'il énumère.
     */
    public function test_no_catch_all_route_defeats_the_safety_net(): void
    {
        $fourreTout = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn ($route) => $route->isFallback)
            ->map(fn ($route) => $route->uri())
            ->values()
            ->all();

        $this->assertSame([], $fourreTout,
            'Une route fourre-tout existe : le filet du sélecteur de langue ne détecte plus rien.'
        );
    }
}
