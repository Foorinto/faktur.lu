<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\User;
use App\Support\DpaDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Acceptation de l'accord de traitement des données.
 *
 * Le DPA prend effet « à la création du compte et à l'acceptation des CGU ».
 * Encore faut-il pouvoir en administrer la preuve : la case des conditions
 * générales était jusqu'ici validée puis oubliée, sans qu'il reste trace de ce
 * qui avait été accepté, quand, ni dans quelle version.
 *
 * Ces tests verrouillent les deux bouts — on ne crée pas de compte sans
 * accepter, et l'acceptation laisse une trace exploitable.
 */
class DpaAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, string> */
    private function inscription(array $remplacements = []): array
    {
        return array_merge([
            'name' => 'Jean Test',
            'email' => 'jean@example.test',
            'password' => 'Correct-Horse-Battery-42!',
            'password_confirmation' => 'Correct-Horse-Battery-42!',
            'terms' => true,
            'dpa' => true,
            // Le formulaire est protégé par un honeypot : champ leurre vide et
            // horodatage de chargement, un envoi trop rapide étant tenu pour
            // automatisé.
            'homepage_url' => '',
            'form_loaded_at' => now()->subSeconds(10)->timestamp,
        ], $remplacements);
    }

    /**
     * Inscrit puis marque l'email comme vérifié : les écrans du profil sont
     * derrière le middleware `verified`, et ce n'est pas ce qu'on teste ici.
     */
    private function inscrit(): User
    {
        $this->post('/register', $this->inscription());

        $user = User::firstWhere('email', 'jean@example.test');
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user->fresh();
    }

    public function test_no_account_is_created_without_accepting_the_dpa(): void
    {
        $this->post('/register', $this->inscription(['dpa' => false]))
            ->assertSessionHasErrors('dpa');

        $this->assertSame(0, User::count(), 'Aucun compte ne doit exister.');
    }

    public function test_an_omitted_checkbox_is_refused_too(): void
    {
        $donnees = $this->inscription();
        unset($donnees['dpa']);

        $this->post('/register', $donnees)->assertSessionHasErrors('dpa');

        $this->assertSame(0, User::count());
    }

    public function test_the_terms_remain_required_as_well(): void
    {
        $this->post('/register', $this->inscription(['terms' => false]))
            ->assertSessionHasErrors('terms');

        $this->assertSame(0, User::count());
    }

    /**
     * L'horodatage, la version et l'adresse font la valeur probante : sans eux
     * il ne reste qu'une affirmation.
     */
    public function test_the_acceptance_leaves_a_usable_trace(): void
    {
        $this->post('/register', $this->inscription())->assertSessionHasNoErrors();

        $user = User::firstWhere('email', 'jean@example.test');

        $this->assertNotNull($user->dpa_accepted_at);
        $this->assertNotNull($user->terms_accepted_at, 'La case des CGU laissait jusqu\'ici la table intacte.');
        $this->assertSame(DpaDocument::VERSION, $user->dpa_version);
        $this->assertNotNull($user->acceptance_ip);
    }

    public function test_the_profile_shows_the_acceptance(): void
    {
        $user = $this->inscrit();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Profile/Edit')
                ->has('dpa.accepted_at')
                ->where('dpa.version', DpaDocument::VERSION)
            );
    }

    public function test_the_downloaded_copy_carries_the_company_name(): void
    {
        $user = $this->inscrit();

        $this->actingAs($user);
        BusinessSettings::factory()->create(['legal_name' => 'La Cornerie SARL-S']);

        $reponse = $this->get(route('profile.dpa'));

        $reponse->assertOk();
        $reponse->assertHeader('content-type', 'application/pdf');

        $this->assertStringContainsString(
            'la-cornerie-sarl-s',
            $reponse->headers->get('content-disposition'),
            'Le fichier doit porter le nom de l\'entreprise, pas un nom générique.'
        );
    }

    public function test_a_visitor_cannot_download_someone_elses_copy(): void
    {
        $this->get(route('profile.dpa'))->assertRedirect(route('login'));
    }

    /**
     * La version doit être figée. L'en-tête affichait `date('d/m/Y')`, donc une
     * date qui changeait chaque jour : impossible de dire ensuite ce qui avait
     * été accepté.
     */
    public function test_the_document_version_does_not_drift(): void
    {
        $this->assertMatchesRegularExpression('/^\d+\.\d+$/', DpaDocument::VERSION);
        $this->assertStringContainsString(
            DpaDocument::effectiveDate()->format('d/m/Y'),
            DpaDocument::label()
        );
    }
}
