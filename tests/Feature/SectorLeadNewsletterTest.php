<?php

namespace Tests\Feature;

use App\Mail\NewsletterConfirmation;
use App\Models\NewsletterSubscriber;
use App\Models\SectorLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * La case « je souhaite aussi recevoir les actualités » abonne réellement.
 *
 * Elle ne le faisait pas : elle posait un booléen sur la réponse, et
 * l'administration affichait « newsletter acceptée » pour des gens qui
 * n'auraient jamais rien reçu. Un consentement recueilli et non honoré ne vaut
 * pas mieux qu'un consentement non demandé — et il est pire, parce qu'il donne
 * l'illusion d'une liste qui n'existe pas.
 */
class SectorLeadNewsletterTest extends TestCase
{
    use RefreshDatabase;

    private function repondre(array $remplacements = []): \Illuminate\Testing\TestResponse
    {
        return $this->post('/secteur/interet', array_merge([
            'sector' => 'health',
            'email' => 'infirmier@exemple.lu',
            'message' => 'Recopier les mêmes actes.',
            'wants_newsletter' => true,
            'form_loaded_at' => now()->subSeconds(30)->timestamp,
        ], $remplacements));
    }

    public function test_ticking_the_box_creates_a_subscriber(): void
    {
        Mail::fake();

        $this->repondre()->assertRedirect();

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'infirmier@exemple.lu',
            // L'origine est tracée dans la liste : ces abonnés viennent d'une
            // page métier, pas du pied de page.
            'source' => 'secteur-health',
        ]);

        // `assertQueued` et non `assertSent` : NewsletterConfirmation
        // implémente ShouldQueue, elle part par la file d'attente.
        Mail::assertQueued(NewsletterConfirmation::class);
    }

    /**
     * Double opt-in, comme le formulaire du pied de page.
     *
     * Deux portes vers la même liste avec deux niveaux de consentement seraient
     * ingérables le jour d'un contrôle.
     */
    public function test_the_subscriber_is_not_confirmed_yet(): void
    {
        Mail::fake();

        $this->repondre();

        $abonne = NewsletterSubscriber::where('email', 'infirmier@exemple.lu')->first();

        $this->assertNotNull($abonne);
        $this->assertNull($abonne->confirmed_at, 'L’abonnement doit attendre la confirmation par courriel.');
        $this->assertNotNull($abonne->confirm_token);
    }

    public function test_leaving_the_box_unticked_subscribes_nobody(): void
    {
        Mail::fake();

        $this->repondre(['wants_newsletter' => false]);

        $this->assertDatabaseCount('newsletter_subscribers', 0);
        Mail::assertNothingQueued();
    }

    /**
     * L'échec d'envoi ne doit pas emporter la réponse.
     *
     * C'est la réponse qui a de la valeur : elle vient d'un secteur où nous
     * n'avons aucun contact. Faire échouer la requête ferait perdre le contact
     * pour sauver l'abonnement, exactement à l'envers de leur valeur
     * respective.
     */
    public function test_a_mail_failure_does_not_cost_the_answer(): void
    {
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP injoignable'));

        $this->repondre()->assertRedirect();

        $this->assertDatabaseHas('sector_leads', [
            'email' => 'infirmier@exemple.lu',
            'wants_newsletter' => true,
        ]);
    }

    /**
     * Répondre deux fois ne crée pas deux abonnés.
     */
    public function test_answering_twice_does_not_duplicate_the_subscriber(): void
    {
        Mail::fake();

        $this->repondre();
        $this->repondre(['message' => 'Une précision.']);

        $this->assertDatabaseCount('newsletter_subscribers', 1);
        $this->assertSame(2, SectorLead::where('email', 'infirmier@exemple.lu')->count());
    }

    /**
     * La langue suit, sans quoi le courriel de confirmation arrive en français
     * à quelqu'un qui lisait la page en portugais.
     */
    public function test_the_confirmation_follows_the_visitor_language(): void
    {
        Mail::fake();

        // La langue vient de la SESSION : le formulaire poste vers
        // `/secteur/interet`, sans préfixe de langue. `app()->setLocale()` dans
        // le test ne survivrait pas au middleware, qui la recalcule.
        $this->withSession(['locale' => 'pt'])
            ->post('/secteur/interet', [
                'sector' => 'construction',
                'email' => 'construtor@exemplo.lu',
                'message' => 'Os orçamentos.',
                'wants_newsletter' => true,
                'form_loaded_at' => now()->subSeconds(30)->timestamp,
            ]);

        $this->assertDatabaseHas('newsletter_subscribers', [
            'email' => 'construtor@exemplo.lu',
            'locale' => 'pt',
        ]);
    }
}
