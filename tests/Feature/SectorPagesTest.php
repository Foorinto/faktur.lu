<?php

namespace Tests\Feature;

use App\Http\Controllers\SectorPageController;
use App\Models\SectorLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pages sectorielles et recueil des manifestations d'intérêt.
 *
 * Ces pages sont des instruments de mesure : elles doivent dire si quelqu'un
 * cherche une solution de facturation pour son métier au Luxembourg, et ce qui
 * lui coûte du temps. Personne ici ne peut trancher autrement, faute de contact
 * dans ces secteurs.
 *
 * D'où deux exigences que les tests fixent. Les pages doivent exister là où le
 * sitemap les annonce — la leçon de `/pt/contacto`, onze pages introuvables. Et
 * elles doivent dire ce qui n'existe PAS : laisser espérer un pack métier ferait
 * venir des gens déçus, et fausserait la mesure elle-même.
 */
class SectorPagesTest extends TestCase
{
    use RefreshDatabase;

    private function reponse(array $surcharges = []): array
    {
        return array_merge([
            'sector' => 'health',
            'email' => 'infirmiere@exemple.lu',
            'message' => 'Recopier les mêmes actes chaque semaine.',
            'homepage_url' => '',
            'form_loaded_at' => microtime(true) - 30,
        ], $surcharges);
    }

    public function test_every_sector_page_is_reachable(): void
    {
        foreach (SectorPageController::pageSlugs() as $slug) {
            $this->get("/fr/logiciel-facturation-{$slug}-luxembourg")
                ->assertOk();
        }
    }

    /**
     * Un slug à tiret casse la route sans que rien ne l'explique.
     *
     * L'URL est `logiciel-facturation-{metier}-luxembourg`, et Symfony compile
     * le paramètre en `[^/]++`, possessif : il ne revient jamais en arrière. Un
     * slug contenant un tiret avale donc le `-luxembourg` final. « artisan »
     * passait, « agence-immobiliere » renvoyait 404.
     */
    public function test_no_published_slug_contains_a_hyphen(): void
    {
        foreach (SectorPageController::pageSlugs() as $slug) {
            $this->assertStringNotContainsString('-', $slug,
                "Le slug « {$slug} » contient un tiret : la route ne correspondra plus.");
        }
    }

    /**
     * Chaque page doit être reliée depuis le site, pas seulement déclarée au
     * sitemap.
     *
     * Les cinq pages « alternative à », publiées de la même façon depuis des
     * mois et jamais reliées à rien, n'ont pas reçu UNE SEULE impression sur la
     * période mesurée. Google découvre par les liens autant que par les
     * sitemaps : une page orpheline reste « détectée, actuellement non
     * indexée », et l'expérience qu'elle devait servir ne mesure alors rien.
     */
    public function test_every_page_is_linked_from_the_public_site(): void
    {
        $layout = file_get_contents(resource_path('js/Layouts/MarketingLayout.vue'));

        preg_match("/const metiersPublies = \[(.*?)\];/s", $layout, $trouve);

        $this->assertNotEmpty($trouve, 'La liste du pied de page est introuvable dans MarketingLayout.');

        preg_match_all("/'([a-z]+)'/", $trouve[1], $liens);

        $manquants = array_diff(SectorPageController::pageSlugs(), $liens[1]);

        $this->assertSame([], array_values($manquants), sprintf(
            "Ces pages ne sont reliées depuis nulle part : elles resteront « détectée, non indexée ».\n  - %s\n",
            implode("\n  - ", $manquants)
        ));
    }

    /** Et l'inverse : un lien vers une page qui n'existe plus serait un 404. */
    public function test_the_footer_links_no_page_that_does_not_exist(): void
    {
        $layout = file_get_contents(resource_path('js/Layouts/MarketingLayout.vue'));
        preg_match("/const metiersPublies = \[(.*?)\];/s", $layout, $trouve);
        preg_match_all("/'([a-z]+)'/", $trouve[1], $liens);

        $fantomes = array_diff($liens[1], SectorPageController::pageSlugs());

        $this->assertSame([], array_values($fantomes),
            'Le pied de page renvoie vers une page sectorielle inexistante : '.implode(', ', $fantomes));
    }

    public function test_an_unknown_trade_is_not_found(): void
    {
        $this->get('/fr/logiciel-facturation-astrologue-luxembourg')->assertNotFound();
    }

    /**
     * Chaque page annonce un secteur connu du sélecteur d'inscription : c'est
     * ce lien qui permettra de rapprocher les réponses des comptes créés.
     */
    public function test_each_page_targets_a_known_sector(): void
    {
        foreach (SectorPageController::pageSlugs() as $slug) {
            $props = $this->get("/fr/logiciel-facturation-{$slug}-luxembourg")
                ->viewData('page')['props'];

            $this->assertContains($props['sector'], User::BUSINESS_SECTORS,
                "La page {$slug} vise un secteur inconnu du sélecteur.");
        }
    }

    public function test_an_answer_is_recorded(): void
    {
        $this->post(route('sector-lead.store'), $this->reponse())
            ->assertRedirect();

        $lead = SectorLead::firstOrFail();

        $this->assertSame('health', $lead->sector);
        $this->assertSame('infirmiere@exemple.lu', $lead->email);
        $this->assertFalse($lead->wants_newsletter, "Le consentement newsletter doit être explicite.");
    }

    /**
     * L'email est exigé.
     *
     * Il était facultatif au motif qu'une réponse anonyme comptait autant pour
     * la mesure. C'était faux : le volume se mesure déjà par les impressions de
     * recherche. Ce que ce formulaire apporte de spécifique, c'est un contact
     * dans un secteur où nous n'en avons aucun, et de quoi prévenir la personne
     * le jour où le pack qu'elle a réclamé existe.
     */
    public function test_an_answer_without_an_email_is_refused(): void
    {
        $this->post(route('sector-lead.store'), $this->reponse(['email' => null]))
            ->assertSessionHasErrors('email');

        $this->assertSame(0, SectorLead::count());
    }

    public function test_an_unknown_sector_is_refused(): void
    {
        $this->post(route('sector-lead.store'), $this->reponse(['sector' => 'astrologie']))
            ->assertSessionHasErrors('sector');

        $this->assertSame(0, SectorLead::count());
    }

    /**
     * Le sitemap ne doit jamais annoncer une page qui n'existe pas : la liste
     * vient du contrôleur, pas d'une seconde énumération.
     */
    public function test_the_sitemap_lists_exactly_the_published_pages(): void
    {
        $xml = $this->get('/sitemap-pages.xml')->assertOk()->getContent();

        foreach (SectorPageController::pageSlugs() as $slug) {
            $this->assertStringContainsString("/fr/logiciel-facturation-{$slug}-luxembourg", $xml);
        }
    }

    /**
     * Chaque page dit ce qui n'existe pas encore. Sans cette mention, une page
     * métier laisse espérer un outil taillé pour le métier.
     */
    public function test_each_page_states_what_does_not_exist_yet(): void
    {
        foreach (SectorPageController::pageSlugs() as $slug) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Lang::has("app.sector_pages.{$slug}.honesty", 'fr'),
                "La page {$slug} doit dire ce qui n'existe pas encore."
            );
        }
    }
}
