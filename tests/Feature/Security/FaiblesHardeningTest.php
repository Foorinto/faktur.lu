<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Renforts de faible gravité (hygiène).
 * AUTH-2 — révocation des autres sessions au changement de mot de passe.
 * PUB-1  — tri du blog admin en liste blanche.
 */
class FaiblesHardeningTest extends TestCase
{
    use RefreshDatabase;

    /** AUTH-2 : changer le mot de passe supprime les autres sessions en base. */
    public function test_changing_password_revokes_other_database_sessions(): void
    {
        config(['session.driver' => 'database']);

        $user = User::factory()->create(['password' => Hash::make('ancien-MDP-123456')]);

        // Une session « autre appareil » du même utilisateur, en base.
        DB::table('sessions')->insert([
            'id' => 'autre-appareil', 'user_id' => $user->id, 'ip_address' => '10.0.0.1',
            'user_agent' => 'test', 'payload' => 'x', 'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($user)->put(route('password.update'), [
            'current_password' => 'ancien-MDP-123456',
            'password' => 'Nouveau-MDP-Solide-99!',
            'password_confirmation' => 'Nouveau-MDP-Solide-99!',
        ]);

        // La session de l'autre appareil (id != session courante) est supprimée.
        $this->assertSame(
            0,
            DB::table('sessions')->where('id', 'autre-appareil')->count(),
            'La session d\'un autre appareil doit être révoquée'
        );
    }

    /** PUB-1 : un champ de tri arbitraire retombe sur la valeur par défaut. */
    public function test_admin_blog_sort_field_is_whitelisted(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);

        // Un champ de tri non autorisé ne doit pas provoquer d'erreur SQL :
        // la page répond normalement (retombe sur created_at).
        $this->actingAs($admin)
            ->get(route('admin.blog.index', ['sort' => 'password', 'direction' => 'drop']))
            ->assertOk();
    }
}
