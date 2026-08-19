<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear all rate limiters before each test
        RateLimiter::clear('login');
        RateLimiter::clear('register');
        RateLimiter::clear('password-reset');
    }

    /** Nombre de tentatives autorisées par un limiteur, lu sur sa définition. */
    private function quotaDe(string $limiteur): int
    {
        return app(\Illuminate\Cache\RateLimiter::class)
            ->limiter($limiteur)(\Illuminate\Http\Request::create('/', 'POST'))
            ->maxAttempts;
    }

    public function test_login_rate_limit_allows_up_to_5_attempts(): void
    {
        $email = 'test@example.com';

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'email' => $email,
                'password' => 'wrong-password',
            ]);

            $response->assertStatus(302); // Redirect back with errors
        }

        // 6th attempt should be rate limited
        $response = $this->post('/login', [
            'email' => $email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
    }

    public function test_login_rate_limit_returns_retry_after_header(): void
    {
        $email = 'test@example.com';

        // Exhaust the rate limit
        for ($i = 0; $i < 6; $i++) {
            $this->post('/login', [
                'email' => $email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post('/login', [
            'email' => $email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
        $response->assertHeader('Retry-After');
    }

    /**
     * Le quota est lu sur le limiteur plutôt qu'écrit ici.
     *
     * La valeur est passée de 3 à 10 le 2026-08-18 : sous NAT partagé — une
     * PME, un espace de coworking, un opérateur mobile — trois inscriptions par
     * heure bloquaient des utilisateurs légitimes. Figer le chiffre dans le test
     * obligerait à le corriger à chaque ajustement, et un test qu'on corrige
     * machinalement finit par ne plus rien vérifier.
     */
    public function test_register_rate_limit_blocks_beyond_its_quota(): void
    {
        $quota = $this->quotaDe('register');

        for ($i = 0; $i < $quota; $i++) {
            $response = $this->post('/register', [
                'name' => 'Test User ' . $i,
                'email' => "test{$i}@example.com",
                'password' => 'password123',
                'password_confirmation' => 'password123',
            ]);

            // Should either succeed or fail validation, but not be rate limited
            $this->assertNotEquals(429, $response->status());
        }

        // La tentative suivante doit être refusée.
        $response = $this->post('/register', [
            'name' => 'Une de trop',
            'email' => 'une-de-trop@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(429);
    }

    public function test_password_reset_rate_limit_allows_up_to_3_attempts_per_hour(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $response = $this->post('/forgot-password', [
                'email' => "test{$i}@example.com",
            ]);

            // Should either succeed or fail validation, but not be rate limited
            $this->assertNotEquals(429, $response->status());
        }

        // 4th attempt should be rate limited
        $response = $this->post('/forgot-password', [
            'email' => 'test4@example.com',
        ]);

        $response->assertStatus(429);
    }

    public function test_crud_rate_limit_returns_appropriate_headers(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/clients');

        $response->assertStatus(200);
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }

    public function test_dashboard_api_rate_limit_allows_60_per_minute(): void
    {
        $this->withoutVite();
        $user = User::factory()->create();

        // Make 60 requests - should all succeed
        for ($i = 0; $i < 60; $i++) {
            $response = $this->actingAs($user)->get('/api/dashboard/kpis');
            $this->assertNotEquals(429, $response->status());
        }

        // 61st request should be rate limited
        $response = $this->actingAs($user)->get('/api/dashboard/kpis');
        $response->assertStatus(429);
    }

    public function test_rate_limits_are_per_user_not_global(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // User 1 makes requests
        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user1)->get('/clients');
        }

        // User 2 should not be affected by User 1's requests
        $response = $this->actingAs($user2)->get('/clients');
        $response->assertStatus(200);
    }

    public function test_rate_limit_response_is_json_for_api_requests(): void
    {
        $email = 'test@example.com';

        // Exhaust the rate limit
        for ($i = 0; $i < 6; $i++) {
            $this->postJson('/login', [
                'email' => $email,
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->postJson('/login', [
            'email' => $email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(429);
        $response->assertJson([
            'message' => 'Trop de tentatives de connexion.',
        ]);
        $response->assertJsonStructure(['message', 'retry_after']);
    }

    public function test_rate_limiters_are_configured(): void
    {
        // Verify all expected rate limiters exist by checking they return limits
        $request = request();

        $limiters = ['api', 'crud', 'dashboard', 'pdf', 'export', 'email', 'register', 'password-reset', 'login'];

        foreach ($limiters as $limiter) {
            $limit = RateLimiter::limiter($limiter);
            $this->assertNotNull($limit, "Rate limiter '{$limiter}' should be configured");
        }
    }
}
