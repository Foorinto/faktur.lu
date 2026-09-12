<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * ADM — la 2FA est obligatoire pour accéder à l'administration.
 *
 * Le panneau donne accès à tous les comptes et à des actions destructives :
 * le mot de passe seul n'y suffit pas. Un admin sans second facteur est renvoyé
 * vers son profil pour l'activer.
 */
class AdminTwoFactorRequiredTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_without_2fa_is_redirected_to_profile(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('profile.edit'));
    }

    public function test_admin_with_2fa_reaches_the_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_non_admin_still_gets_403(): void
    {
        $user = User::factory()->create(['is_admin' => false, 'email_verified_at' => now()]);

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
