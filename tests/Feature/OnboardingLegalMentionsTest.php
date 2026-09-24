<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * L'étape « entreprise » de l'accueil pose la forme d'exercice et exige le
 * RCS et l'autorisation d'établissement selon la réponse (FEAT-133) : un
 * nouveau compte ne part pas sans ses mentions obligatoires.
 */
class OnboardingLegalMentionsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($this->user);
    }

    private function etape(array $surcharge = []): array
    {
        return array_merge([
            'company_name' => 'Peinture Muller',
            'matricule' => '2020123456789',
            'exercise_form' => 'company',
            'rcs_number' => 'B123456',
            'establishment_authorization' => '10012345',
            'no_establishment_authorization' => false,
        ], $surcharge);
    }

    public function test_la_forme_d_exercice_est_obligatoire_des_l_accueil(): void
    {
        $this->postJson(route('onboarding.company'), $this->etape(['exercise_form' => '']))
            ->assertStatus(422)->assertJsonValidationErrors('exercise_form');
    }

    public function test_une_societe_doit_donner_rcs_et_autorisation_des_l_accueil(): void
    {
        $this->postJson(route('onboarding.company'), $this->etape(['rcs_number' => '', 'establishment_authorization' => '']))
            ->assertStatus(422)->assertJsonValidationErrors(['rcs_number', 'establishment_authorization']);

        $this->postJson(route('onboarding.company'), $this->etape())->assertOk();

        $reglages = BusinessSettings::withoutGlobalScopes()->where('user_id', $this->user->id)->first();
        $this->assertSame('company', $reglages->exercise_form);
        $this->assertSame('B123456', $reglages->rcs_number);
        $this->assertSame('10012345', $reglages->establishment_authorization);
        $this->assertSame([], $reglages->missingLegalMentions());
    }

    public function test_une_profession_liberale_passe_sans_rcs_ni_autorisation(): void
    {
        $this->postJson(route('onboarding.company'), $this->etape(['exercise_form' => 'liberal', 'rcs_number' => '', 'establishment_authorization' => '']))
            ->assertOk();

        $reglages = BusinessSettings::withoutGlobalScopes()->where('user_id', $this->user->id)->first();
        $this->assertSame('liberal', $reglages->exercise_form);
        $this->assertSame([], $reglages->missingLegalMentions());
    }

    public function test_une_societe_dispensee_le_declare_d_une_case(): void
    {
        $this->postJson(route('onboarding.company'), $this->etape(['establishment_authorization' => '', 'no_establishment_authorization' => true]))
            ->assertOk();

        $this->assertTrue(BusinessSettings::withoutGlobalScopes()->where('user_id', $this->user->id)->first()->no_establishment_authorization);
    }
}
