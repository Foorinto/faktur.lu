<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Où atterrit un utilisateur après connexion.
 *
 * Depuis que l'URL du panneau ne figure plus dans le HTML, un administrateur ne
 * peut plus la retrouver en inspectant une page : il faut donc l'y conduire.
 */
class AdminLoginRedirectTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_admin_lands_on_the_panel(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    }

    /**
     * Sauf en usurpation : voir l'application par les yeux d'un utilisateur,
     * puis se faire renvoyer au panneau, annulerait la manœuvre.
     */
    public function test_an_impersonating_admin_stays_in_the_application(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        $this->withSession(['impersonator_id' => 999])
            ->post('/login', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_an_ordinary_user_lands_on_the_dashboard(): void
    {
        $utilisateur = User::factory()->create([
            'email_verified_at' => now(),
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', ['email' => $utilisateur->email, 'password' => 'password'])
            ->assertRedirect(config('fortify.home'));
    }
}
