<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

/**
 * Secteur d'activité déclaré à l'inscription.
 *
 * Cet écran ne configure rien : il MESURE. Avant d'engager treize jours sur un
 * pack métier — celui de la santé, par exemple — on veut savoir qui s'inscrit
 * réellement plutôt que de le supposer.
 *
 * D'où deux exigences que les tests fixent. La valeur doit être contrainte à
 * une liste fermée : un champ libre rendrait tout dénombrement impossible. Et
 * l'absence de réponse doit rester distincte d'une réponse « Autre » —
 * confondre les comptes antérieurs, jamais interrogés, avec ceux qui ont
 * répondu fausserait la mesure dès le premier jour.
 */
class BusinessSectorTest extends TestCase
{
    use RefreshDatabase;

    private function utilisateur(): User
    {
        return User::factory()->create(['onboarding_step' => 'sector']);
    }

    /**
     * Un compte fraîchement créé doit arriver sur la question.
     *
     * L'inscription écrivait `onboarding_step = 'company'` en dur : l'écran
     * était donc purement sauté, et le repli `?? 'sector'` du contrôleur ne
     * s'appliquait jamais. Rien ne le signalait — le parcours restait
     * parfaitement fonctionnel, simplement amputé de sa première étape.
     */
    public function test_a_new_account_starts_on_the_sector_question(): void
    {
        $this->post('/register', [
            'name' => 'Nouvelle inscrite',
            'email' => 'nouvelle@exemple.lu',
            'password' => 'Alex1234$$$$',
            'password_confirmation' => 'Alex1234$$$$',
            'terms' => true,
            'dpa' => true,
            'homepage_url' => '',
            'form_loaded_at' => microtime(true) - 30,
        ])->assertRedirect();

        $this->assertSame(
            'sector',
            User::where('email', 'nouvelle@exemple.lu')->value('onboarding_step'),
            "Le parcours doit commencer par la question du secteur."
        );
    }

    public function test_a_sector_is_recorded_with_its_date(): void
    {
        $utilisateur = $this->utilisateur();

        $this->actingAs($utilisateur)
            ->postJson(route('onboarding.sector'), ['business_sector' => 'health'])
            ->assertOk()
            ->assertJson(['success' => true, 'next_step' => 'company']);

        $utilisateur->refresh();

        $this->assertSame('health', $utilisateur->business_sector);
        $this->assertNotNull($utilisateur->business_sector_set_at);
        $this->assertSame('company', $utilisateur->onboarding_step, 'Le parcours doit enchaîner sur l\'entreprise.');
    }

    /**
     * Un champ libre rendrait tout dénombrement impossible : c'est précisément
     * ce qu'on cherche à produire.
     */
    public function test_an_unknown_sector_is_refused(): void
    {
        $this->actingAs($this->utilisateur())
            ->postJson(route('onboarding.sector'), ['business_sector' => 'astrologie'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('business_sector');
    }

    /** Les sept valeurs de la liste doivent toutes passer. */
    public function test_every_sector_of_the_list_is_accepted(): void
    {
        foreach (User::BUSINESS_SECTORS as $secteur) {
            $utilisateur = User::factory()->create(['onboarding_step' => 'sector']);

            $this->actingAs($utilisateur)
                ->postJson(route('onboarding.sector'), ['business_sector' => $secteur])
                ->assertOk();

            $this->assertSame($secteur, $utilisateur->refresh()->business_sector);
        }
    }

    /**
     * Les comptes créés avant cet écran n'ont jamais été interrogés. Les
     * compter comme « Autre » fausserait la mesure.
     */
    public function test_an_older_account_has_no_sector(): void
    {
        $this->assertNull(User::factory()->create()->business_sector);
    }

    /**
     * La mesure doit être lisible depuis l'administration : c'est elle qui
     * tranchera quel pack métier construire.
     */
    public function test_the_admin_can_read_the_distribution(): void
    {
        User::factory()->count(3)->create(['business_sector' => 'health']);
        User::factory()->create(['business_sector' => 'construction']);
        User::factory()->count(2)->create(); // jamais interrogés

        $stats = app(\App\Services\AdminStatsService::class)->getSectorStats();

        $this->assertSame(4, $stats['repondants']);
        $this->assertSame(2, $stats['sans_reponse'], 'Les comptes jamais interrogés se comptent à part.');

        $premier = $stats['par_secteur'][0];
        $this->assertSame('health', $premier['secteur'], 'Le secteur le plus représenté vient en tête.');
        $this->assertSame(3, $premier['total']);
        $this->assertSame('Professions de santé', $premier['libelle']);
    }

    /**
     * L'écran d'inscription promet qu'on pourra changer d'avis. La promesse
     * doit tenir, sans quoi elle vaut mieux retirée du texte.
     */
    public function test_the_sector_can_be_changed_later_from_the_profile(): void
    {
        $utilisateur = User::factory()->create(['business_sector' => 'other']);

        $this->actingAs($utilisateur)
            ->patch(route('profile.update'), [
                'name' => $utilisateur->name,
                'email' => $utilisateur->email,
                'business_sector' => 'health',
            ])
            ->assertRedirect();

        $this->assertSame('health', $utilisateur->refresh()->business_sector);
    }

    /** Omettre le champ ne doit pas effacer une réponse déjà donnée. */
    public function test_omitting_the_field_leaves_the_sector_intact(): void
    {
        $utilisateur = User::factory()->create(['business_sector' => 'health']);

        $this->actingAs($utilisateur)
            ->patch(route('profile.update'), ['name' => 'Nouveau nom', 'email' => $utilisateur->email])
            ->assertRedirect();

        $this->assertSame('health', $utilisateur->refresh()->business_sector);
    }

    /** Les sept secteurs doivent être nommés dans les cinq langues. */
    public function test_every_sector_is_translated(): void
    {
        foreach (['fr', 'en', 'de', 'lb', 'pt'] as $locale) {
            foreach (User::BUSINESS_SECTORS as $secteur) {
                foreach (['label', 'hint'] as $champ) {
                    $this->assertTrue(
                        Lang::has("app.business_sectors.{$secteur}.{$champ}", $locale),
                        "Le secteur {$secteur} n'a pas de {$champ} en {$locale}."
                    );
                }
            }
        }
    }
}
