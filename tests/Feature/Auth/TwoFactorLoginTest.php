<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Features;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * La double authentification doit réellement s'interposer à la connexion.
 *
 * L'application sert la connexion via Breeze alors que la 2FA vient de Fortify :
 * sans branchement explicite, un utilisateur ayant activé la 2FA se connectait
 * avec son seul mot de passe. La protection était affichée mais inopérante.
 */
class TwoFactorLoginTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Fakt#2026!Secur';

    private function userWithTwoFactor(): array
    {
        $secret = app(Google2FA::class)->generateSecretKey();

        $user = User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
        $user->forceFill([
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode(['code-de-secours-1', 'code-de-secours-2'])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        return [$user, $secret];
    }

    private function login(User $user): \Illuminate\Testing\TestResponse
    {
        return $this->post('/login', [
            'email' => $user->email,
            'password' => self::PASSWORD,
        ]);
    }

    public function test_a_user_with_two_factor_is_not_logged_in_by_the_password_alone(): void
    {
        [$user] = $this->userWithTwoFactor();

        $this->login($user)->assertRedirect(route('two-factor.login'));

        // Le point central : le mot de passe seul ne suffit pas.
        $this->assertGuest();
    }

    public function test_the_challenge_screen_is_reachable(): void
    {
        [$user] = $this->userWithTwoFactor();

        $this->login($user);

        $this->get(route('two-factor.login'))->assertOk();
    }

    public function test_a_valid_code_completes_the_login(): void
    {
        [$user, $secret] = $this->userWithTwoFactor();

        $this->login($user);

        $this->post('/two-factor-challenge', [
            'code' => app(Google2FA::class)->getCurrentOtp($secret),
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_an_invalid_code_does_not_log_the_user_in(): void
    {
        [$user] = $this->userWithTwoFactor();

        $this->login($user);

        $this->post('/two-factor-challenge', ['code' => '000000']);

        $this->assertGuest();
    }

    public function test_a_recovery_code_completes_the_login(): void
    {
        [$user] = $this->userWithTwoFactor();

        $this->login($user);

        $this->post('/two-factor-challenge', ['recovery_code' => 'code-de-secours-1'])
            ->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_a_user_without_two_factor_logs_in_normally(): void
    {
        $user = User::factory()->create(['password' => Hash::make(self::PASSWORD)]);

        $this->login($user)->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    /**
     * Un secret généré mais jamais confirmé (QR affiché, page quittée en cours
     * de route) ne doit pas verrouiller l'accès : sinon l'utilisateur s'enferme
     * dehors sans jamais avoir terminé l'activation.
     */
    public function test_an_unconfirmed_secret_does_not_block_the_login(): void
    {
        $user = User::factory()->create(['password' => Hash::make(self::PASSWORD)]);
        $user->forceFill([
            'two_factor_secret' => encrypt(app(Google2FA::class)->generateSecretKey()),
            'two_factor_confirmed_at' => null,
        ])->save();

        $this->login($user);

        $this->assertAuthenticatedAs($user);
    }
}
