<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Le chemin du panneau d'administration ne doit pas être publié.
 *
 * La directive `@routes` sérialise les routes nommées dans le HTML de chaque
 * page. Les routes `admin.*` en faisaient partie, et avec elles le préfixe
 * choisi pour n'être pas devinable : il apparaissait quarante-quatre fois dans
 * le code source de la page d'accueil, lisible par un simple « afficher la
 * source ».
 *
 * Ce n'était pas une faille — le panneau exige `auth`, `verified` et
 * `admin.user` — mais la discrétion du chemin, ajoutée pour écarter les
 * scanners automatisés, ne valait plus rien.
 */
class AdminPanelExposureTest extends TestCase
{
    use RefreshDatabase;

    private function prefixe(): string
    {
        return (string) config('admin.url_prefix');
    }

    public function test_an_anonymous_visitor_never_sees_the_admin_path(): void
    {
        $this->get('/fr')
            ->assertOk()
            ->assertDontSee($this->prefixe(), false);
    }

    public function test_an_ordinary_user_never_sees_the_admin_path(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee($this->prefixe(), false);
    }

    /** L'administrateur, lui, en a besoin pour naviguer. */
    public function test_an_admin_receives_the_admin_routes(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee($this->prefixe(), false);
    }

    /**
     * L'arrêt d'usurpation vit HORS du préfixe et du nom « admin. ».
     *
     * C'est l'utilisateur usurpé qui l'appelle — il n'est pas administrateur.
     * Tant que la route s'appelait `admin.impersonation.stop`, la retirer de la
     * table publiée cassait le bouton ; et son URL publiait le préfixe à elle
     * seule.
     */
    public function test_stopping_impersonation_stays_outside_the_admin_prefix(): void
    {
        $uri = route('impersonation.stop', absolute: false);

        $this->assertStringNotContainsString($this->prefixe(), $uri,
            "L'arrêt d'usurpation ne doit pas passer par le préfixe du panneau.");
    }

    public function test_the_admin_panel_still_demands_authentication(): void
    {
        $this->get('/'.$this->prefixe())->assertRedirect(route('login'));
    }

    public function test_an_ordinary_user_is_refused_by_the_admin_panel(): void
    {
        $this->actingAs(User::factory()->create(['email_verified_at' => now()]))
            ->get('/'.$this->prefixe())
            ->assertForbidden();
    }
}
