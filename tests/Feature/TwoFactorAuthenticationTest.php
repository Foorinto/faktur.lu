<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Fortify\Features;
use Tests\TestCase;

class TwoFactorAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_factor_authentication_can_be_enabled(): void
    {
        if (!Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two factor authentication is not enabled.');
        }

        $user = User::factory()->create();

        $this->actingAs($user);

        // First confirm password
        $this->post('/user/confirm-password', [
            'password' => 'password',
        ]);

        // Then enable 2FA
        $response = $this->post('/user/two-factor-authentication');

        // Fortify redirects after enabling 2FA
        $response->assertRedirect();

        $user->refresh();

        $this->assertNotNull($user->two_factor_secret);
        $this->assertNotNull($user->two_factor_recovery_codes);
    }

    public function test_two_factor_authentication_can_be_confirmed(): void
    {
        if (!Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two factor authentication is not enabled.');
        }

        $user = User::factory()->create();

        $this->actingAs($user);

        // Confirm password
        $this->post('/user/confirm-password', [
            'password' => 'password',
        ]);

        // Enable 2FA
        $this->post('/user/two-factor-authentication');

        $user->refresh();

        // Get the secret and generate a valid code
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $validCode = $google2fa->getCurrentOtp(decrypt($user->two_factor_secret));

        // Confirm 2FA
        $response = $this->post('/user/confirmed-two-factor-authentication', [
            'code' => $validCode,
        ]);

        // Fortify redirects after confirming 2FA
        $response->assertRedirect();

        $user->refresh();

        $this->assertNotNull($user->two_factor_confirmed_at);
        $this->assertTrue($user->hasEnabledTwoFactorAuthentication());
    }

    public function test_two_factor_authentication_can_be_disabled(): void
    {
        if (!Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two factor authentication is not enabled.');
        }

        $user = User::factory()->create();

        $this->actingAs($user);

        // Confirm password
        $this->post('/user/confirm-password', [
            'password' => 'password',
        ]);

        // Enable and confirm 2FA
        $this->post('/user/two-factor-authentication');
        $user->refresh();

        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $validCode = $google2fa->getCurrentOtp(decrypt($user->two_factor_secret));

        $this->post('/user/confirmed-two-factor-authentication', [
            'code' => $validCode,
        ]);

        // Disable 2FA
        $response = $this->delete('/user/two-factor-authentication');

        // Fortify redirects after disabling 2FA
        $response->assertRedirect();

        $user->refresh();

        $this->assertNull($user->two_factor_secret);
        $this->assertFalse($user->hasEnabledTwoFactorAuthentication());
    }

    public function test_recovery_codes_can_be_regenerated(): void
    {
        if (!Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two factor authentication is not enabled.');
        }

        $user = User::factory()->create();

        $this->actingAs($user);

        // Confirm password
        $this->post('/user/confirm-password', [
            'password' => 'password',
        ]);

        // Enable and confirm 2FA
        $this->post('/user/two-factor-authentication');
        $user->refresh();

        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $validCode = $google2fa->getCurrentOtp(decrypt($user->two_factor_secret));

        $this->post('/user/confirmed-two-factor-authentication', [
            'code' => $validCode,
        ]);

        $user->refresh();
        $originalCodes = $user->recoveryCodes();

        // Regenerate recovery codes
        $response = $this->post('/user/two-factor-recovery-codes');

        // Fortify redirects after regenerating codes
        $response->assertRedirect();

        $user->refresh();
        $newCodes = $user->recoveryCodes();

        $this->assertNotEquals($originalCodes, $newCodes);
        $this->assertCount(8, $newCodes);
    }

    public function test_user_model_has_two_factor_enabled_attribute(): void
    {
        $user = User::factory()->create();

        $this->assertFalse($user->two_factor_enabled);

        // After enabling 2FA
        $user->forceFill([
            'two_factor_secret' => encrypt('secret'),
            'two_factor_confirmed_at' => now(),
        ])->save();

        $user->refresh();

        $this->assertTrue($user->two_factor_enabled);
    }

    public function test_two_factor_challenge_page_can_be_rendered(): void
    {
        if (!Features::canManageTwoFactorAuthentication()) {
            $this->markTestSkipped('Two factor authentication is not enabled.');
        }

        // The 2FA challenge page should be accessible when the login.id is in session
        // This simulates the state after a user with 2FA logs in
        $user = User::factory()->create();

        // Enable and confirm 2FA for the user using proper encryption
        $google2fa = app(\PragmaRX\Google2FA\Google2FA::class);
        $secretKey = $google2fa->generateSecretKey();

        $user->forceFill([
            'two_factor_secret' => encrypt($secretKey),
            'two_factor_recovery_codes' => encrypt(json_encode([
                'recovery-code-1',
                'recovery-code-2',
                'recovery-code-3',
                'recovery-code-4',
                'recovery-code-5',
                'recovery-code-6',
                'recovery-code-7',
                'recovery-code-8',
            ])),
            'two_factor_confirmed_at' => now(),
        ])->save();

        // La 2FA est désormais branchée sur la connexion : après validation du
        // mot de passe, l'utilisateur est renvoyé vers le défi sans être connecté.
        $response = $this->withSession([
            'login.id' => $user->id,
            'login.remember' => false,
        ])->get(route('two-factor.login'));

        $response->assertStatus(200);
}
}
