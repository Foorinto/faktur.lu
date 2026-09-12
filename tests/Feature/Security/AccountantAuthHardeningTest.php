<?php

namespace Tests\Feature\Security;

use App\Models\Accountant;
use App\Models\AccountantInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Tests\TestCase;

/**
 * HIGH-3 — login et défi TOTP comptable sans limitation de débit.
 * HIGH-4 — acceptation d'invitation qui ouvre une session en contournant la 2FA.
 */
class AccountantAuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    private const MOT_DE_PASSE = 'Correct-Horse-Battery-42!';

    private function comptable(bool $avecDoubleFacteur = false, string $email = 'comptable@example.test'): Accountant
    {
        $comptable = Accountant::create([
            'name' => 'Fiduciaire Test',
            'email' => $email,
            'password' => Hash::make(self::MOT_DE_PASSE),
            'email_verified_at' => now(),
        ]);

        if ($avecDoubleFacteur) {
            app(EnableTwoFactorAuthentication::class)($comptable);
            $comptable->forceFill(['two_factor_confirmed_at' => now()])->save();
            $comptable->refresh();
        }

        return $comptable;
    }

    /** HIGH-3 : le login comptable est limité en débit. */
    public function test_accountant_login_is_rate_limited(): void
    {
        $comptable = $this->comptable();

        // Au-delà de 5/min, la 6e tentative est bloquée (429).
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('accountant.login.submit'), [
                'email' => $comptable->email,
                'password' => 'mauvais',
            ]);
        }

        $this->post(route('accountant.login.submit'), [
            'email' => $comptable->email,
            'password' => 'mauvais',
        ])->assertStatus(429);
    }

    /** HIGH-3 : le défi TOTP est limité en débit. */
    public function test_accountant_two_factor_challenge_is_rate_limited(): void
    {
        $comptable = $this->comptable(avecDoubleFacteur: true);
        session([\App\Http\Controllers\Accountant\AccountantTwoFactorController::SESSION_KEY => $comptable->id]);

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('accountant.two-factor.verify'), ['code' => '000000']);
        }

        $this->post(route('accountant.two-factor.verify'), ['code' => '000000'])
            ->assertStatus(429);
    }

    /** HIGH-4 : accepter une invitation avec 2FA active renvoie au défi, pas de session directe. */
    public function test_accepting_invitation_with_2fa_routes_through_challenge(): void
    {
        $comptable = $this->comptable(avecDoubleFacteur: true);
        $owner = User::factory()->create();

        $invitation = AccountantInvitation::create([
            'user_id' => $owner->id,
            'email' => $comptable->email,
            'token' => 'jeton-test-123',
            'status' => AccountantInvitation::STATUS_PENDING,
            'expires_at' => now()->addDays(7),
        ]);

        $response = $this->post(route('accountant.accept.submit', $invitation->token), [
            'password' => self::MOT_DE_PASSE,
        ]);

        // Renvoyé au défi 2FA, PAS connecté.
        $response->assertRedirect(route('accountant.two-factor.challenge'));
        $this->assertFalse(auth('accountant')->check(), 'Aucune session ouverte sans le second facteur');
    }
}
