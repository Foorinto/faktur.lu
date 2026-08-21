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

    /** Une réponse sans email compte autant : elle dit ce qui prend du temps. */
    public function test_an_answer_without_an_email_is_kept(): void
    {
        $this->post(route('sector-lead.store'), $this->reponse(['email' => null]))
            ->assertRedirect();

        $this->assertSame(1, SectorLead::count());
        $this->assertNull(SectorLead::first()->email);
    }

    /** Un formulaire entièrement vide est un clic distrait, pas une réponse. */
    public function test_an_empty_form_records_nothing(): void
    {
        $this->post(route('sector-lead.store'), $this->reponse(['email' => null, 'message' => null]))
            ->assertRedirect();

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
