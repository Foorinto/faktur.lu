<?php

namespace Tests\Unit\Services;

use App\Models\AdminLoginAttempt;
use App\Services\AdminAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

/**
 * Authentification du panneau d'administration.
 *
 * Mécanisme distinct de l'authentification des utilisateurs : identifiants en
 * configuration, 2FA obligatoire, blocage d'IP après échecs répétés. C'est la
 * porte d'entrée vers les données de tous les clients — elle n'avait aucun test.
 */
class AdminAuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AdminAuthService $service;
    private string $secret;

    private const USERNAME = 'admin@faktur.lu';
    private const PASSWORD = 'MotDePasseAdmin!2026';

    protected function setUp(): void
    {
        parent::setUp();

        $this->secret = app(Google2FA::class)->generateSecretKey();

        config([
            'admin.username' => self::USERNAME,
            'admin.password_hash' => Hash::make(self::PASSWORD),
            'admin.2fa_secret' => $this->secret,
            'admin.max_login_attempts' => 3,
            'admin.ip_block_duration' => 60,
        ]);

        $this->service = app(AdminAuthService::class);
    }

    private function requestFrom(string $ip = '203.0.113.10'): Request
    {
        $request = Request::create('/admin/login', 'POST');
        $request->server->set('REMOTE_ADDR', $ip);

        return $request;
    }

    public function test_valid_credentials_are_accepted(): void
    {
        $this->assertTrue($this->service->validateCredentials(self::USERNAME, self::PASSWORD));
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $this->assertFalse($this->service->validateCredentials(self::USERNAME, 'mauvais'));
    }

    public function test_a_wrong_username_is_rejected(): void
    {
        $this->assertFalse($this->service->validateCredentials('intrus@example.com', self::PASSWORD));
    }

    public function test_an_empty_password_is_rejected(): void
    {
        // Garde-fou : une configuration incomplète ne doit pas ouvrir la porte.
        $this->assertFalse($this->service->validateCredentials(self::USERNAME, ''));
    }

    public function test_a_valid_totp_code_is_accepted(): void
    {
        $code = app(Google2FA::class)->getCurrentOtp($this->secret);

        $this->assertTrue($this->service->verify2fa($code));
    }

    public function test_an_invalid_totp_code_is_rejected(): void
    {
        $this->assertFalse($this->service->verify2fa('000000'));
    }

    public function test_an_ip_is_not_blocked_before_reaching_the_limit(): void
    {
        $request = $this->requestFrom();

        // Deux échecs sur trois autorisés.
        $this->service->recordLoginAttempt($request, false, self::USERNAME);
        $this->service->recordLoginAttempt($request, false, self::USERNAME);

        $this->assertFalse($this->service->isIpBlocked('203.0.113.10'));
    }

    public function test_an_ip_is_blocked_after_repeated_failures(): void
    {
        $request = $this->requestFrom();

        for ($i = 0; $i < 3; $i++) {
            $this->service->recordLoginAttempt($request, false, self::USERNAME);
        }

        $this->assertTrue($this->service->isIpBlocked('203.0.113.10'));
        $this->assertNotNull($this->service->getBlockTimeRemaining('203.0.113.10'));
    }

    public function test_a_successful_login_clears_the_failure_counter(): void
    {
        $request = $this->requestFrom();

        $this->service->recordLoginAttempt($request, false, self::USERNAME);
        $this->service->recordLoginAttempt($request, false, self::USERNAME);
        $this->service->recordLoginAttempt($request, true, self::USERNAME);

        // Après une connexion réussie, le compteur repart de zéro : deux nouveaux
        // échecs ne doivent pas suffire à bloquer.
        $this->service->recordLoginAttempt($request, false, self::USERNAME);
        $this->service->recordLoginAttempt($request, false, self::USERNAME);

        $this->assertFalse($this->service->isIpBlocked('203.0.113.10'));
    }

    public function test_blocking_one_ip_does_not_block_another(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->service->recordLoginAttempt($this->requestFrom('203.0.113.10'), false, self::USERNAME);
        }

        $this->assertTrue($this->service->isIpBlocked('203.0.113.10'));
        $this->assertFalse($this->service->isIpBlocked('198.51.100.20'));
    }

    public function test_every_attempt_is_recorded_for_audit(): void
    {
        $this->service->recordLoginAttempt($this->requestFrom(), false, self::USERNAME);

        // Une tentative sur le panneau d'administration doit laisser une trace.
        $this->assertDatabaseHas('admin_login_attempts', [
            'ip_address' => '203.0.113.10',
            'successful' => false,
        ]);
    }
}
