<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    /** Mot de passe conforme à la politique en vigueur (12 car., casse, chiffre, symbole). */
    private const PASSWORD = 'Fakt#2026!Secur';

    /**
     * Le formulaire est protégé par un honeypot : un champ leurre qui doit rester
     * vide, et un horodatage de chargement — un envoi trop rapide est tenu pour
     * automatisé. Un test doit donc simuler un formulaire ouvert depuis un moment.
     *
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'terms' => true, // acceptation des CGU, obligatoire
            'dpa' => true, // acceptation du DPA, obligatoire depuis la v1.1
            'homepage_url' => '',
            'form_loaded_at' => now()->subSeconds(10)->timestamp,
        ], $overrides);
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $response = $this->post('/register', $this->payload());

        $this->assertAuthenticated();
        // L'inscription passe par une page de remerciement, pas directement
        // par le tableau de bord.
        $response->assertRedirect(route('register.thank-you', absolute: false));
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    public function test_a_filled_honeypot_field_blocks_the_registration(): void
    {
        $this->post('/register', $this->payload(['homepage_url' => 'http://spam.example']));

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_an_instant_submission_blocks_the_registration(): void
    {
        // Un humain ne remplit pas le formulaire en moins d'une seconde et demie.
        $this->post('/register', $this->payload(['form_loaded_at' => now()->timestamp]));

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['email' => 'test@example.com']);
    }

    public function test_a_weak_password_is_rejected(): void
    {
        $this->post('/register', $this->payload([
            'password' => 'password',
            'password_confirmation' => 'password',
        ]))->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_registration_requires_accepting_the_terms(): void
    {
        $this->post('/register', $this->payload(['terms' => false]))
            ->assertSessionHasErrors('terms');

        $this->assertGuest();
    }
}
