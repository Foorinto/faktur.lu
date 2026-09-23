<?php

namespace Tests\Feature\Auth;

use App\Models\Organization;
use App\Models\OrganizationMember;
use App\Models\User;
use App\Notifications\EmailOtpNotification;
use Database\Seeders\PlansSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Où l'on atterrit après la connexion (App\Auth\LoginDestination). La logique
 * a quitté le contrôleur de connexion pour servir aussi la fin du défi par
 * e-mail (FEAT-124) : un collaborateur doit arriver au même endroit par les
 * deux chemins. L'administrateur est couvert par AdminLoginRedirectTest.
 */
class LoginDestinationTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Fakt#2026!Secur';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PlansSeeder::class);
        Notification::fake();
    }

    private function utilisateur(array $attributs = []): User
    {
        return User::factory()->create(array_merge([
            'password' => Hash::make(self::PASSWORD),
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ], $attributs));
    }

    private function collaborateur(array $attributs = []): User
    {
        $proprietaire = $this->utilisateur();
        $organisation = Organization::create(['user_id' => $proprietaire->id, 'name' => 'Atelier', 'slug' => 'atelier']);
        $collaborateur = $this->utilisateur($attributs);
        OrganizationMember::create([
            'organization_id' => $organisation->id,
            'user_id' => $collaborateur->id,
            'role' => OrganizationMember::ROLE_COLLABORATOR,
            'invited_at' => now(),
            'joined_at' => now(),
        ]);

        return $collaborateur;
    }

    private function connexion(User $user): TestResponse
    {
        return $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD]);
    }

    public function test_un_compte_ordinaire_arrive_au_tableau_de_bord(): void
    {
        $this->connexion($this->utilisateur())->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_un_collaborateur_arrive_a_son_propre_tableau_de_bord(): void
    {
        $this->connexion($this->collaborateur())->assertRedirect(route('collaborator.dashboard'));
    }

    public function test_le_defi_par_email_mene_au_meme_endroit(): void
    {
        $collaborateur = $this->collaborateur(['email_otp_enabled_at' => now()]);
        $this->connexion($collaborateur)->assertRedirect(route('two-factor.email'));

        $code = null;
        Notification::assertSentTo($collaborateur, EmailOtpNotification::class, function (EmailOtpNotification $n) use (&$code) {
            $code = $n->code;

            return true;
        });

        $this->post(route('two-factor.email.store'), ['code' => $code])->assertRedirect(route('collaborator.dashboard'));
        $this->assertAuthenticatedAs($collaborateur);
    }
}
