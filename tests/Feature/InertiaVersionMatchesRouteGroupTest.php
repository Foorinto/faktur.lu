<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * La page blanche après connexion.
 *
 * `@routes` grave la liste des routes Ziggy dans le HTML, et cette liste dépend
 * du profil : un visiteur anonyme n'en reçoit qu'une trentaine de familles, un
 * administrateur les reçoit toutes. Inertia, lui, ne recharge pas le HTML : il
 * échange le composant. Après une connexion, le navigateur gardait donc la
 * liste ANONYME, et le premier `route('…')` du tableau de bord levait
 * « route is not in the route list ». La page restait blanche jusqu'à un
 * rechargement manuel.
 *
 * Reproduit dans un vrai navigateur le 2026-08-21 :
 *   admin      → « Ziggy error: route 'admin.dashboard' is not in the route list. »
 *   ordinaire  → « Ziggy error: route 'profile.edit' is not in the route list. »
 *
 * Le correctif fait entrer le groupe de routes dans la version Inertia. Tout
 * changement de profil produit alors un écart de version, et Inertia impose un
 * rechargement complet — ce pour quoi ce mécanisme existe.
 */
class InertiaVersionMatchesRouteGroupTest extends TestCase
{
    use RefreshDatabase;

    private function version(): string
    {
        return app(HandleInertiaRequests::class)->version(request());
    }

    public function test_the_version_changes_with_the_profile(): void
    {
        $anonyme = $this->version();

        Auth::login(User::factory()->create(['is_admin' => false]));
        $ordinaire = $this->version();

        Auth::logout();
        Auth::login(User::factory()->create(['is_admin' => true]));
        $administrateur = $this->version();

        $this->assertNotSame($anonyme, $ordinaire,
            "Un visiteur anonyme et un utilisateur connecté ne reçoivent pas les mêmes routes : "
            ."leurs versions doivent différer, sans quoi Inertia ne rechargera pas."
        );

        $this->assertNotSame($ordinaire, $administrateur,
            'Un administrateur reçoit les routes admin, pas un utilisateur ordinaire.'
        );

        $this->assertNotSame($anonyme, $administrateur);
    }

    /**
     * Le groupe publié et le groupe supposé viennent de la même source.
     *
     * Si la vue publiait un groupe et la version en supposait un autre, le
     * rechargement ne se déclencherait pas au bon moment et la panne
     * reviendrait, silencieuse.
     */
    public function test_the_view_and_the_version_read_the_same_group(): void
    {
        $vue = file_get_contents(resource_path('views/app.blade.php'));

        $this->assertMatchesRegularExpression(
            '/@routes\(\s*\\\\?App\\\\Http\\\\Middleware\\\\HandleInertiaRequests::ziggyGroup\(\)\s*\)/',
            $vue,
            "app.blade.php doit publier le groupe rendu par HandleInertiaRequests::ziggyGroup(), "
            .'sans quoi la vue et la version peuvent diverger.'
        );
    }

    public function test_the_group_matches_the_profile(): void
    {
        $this->assertSame('public', HandleInertiaRequests::ziggyGroup());

        Auth::login(User::factory()->create(['is_admin' => false]));
        $this->assertNull(HandleInertiaRequests::ziggyGroup(),
            'Un utilisateur connecté reçoit le groupe par défaut : toutes les routes sauf les exclues.'
        );

        Auth::logout();
        Auth::login(User::factory()->create(['is_admin' => true]));
        $this->assertSame('admin', HandleInertiaRequests::ziggyGroup());
    }

    /**
     * Le comportement observable : une visite Inertia portant la version d'un
     * anonyme reçoit un 409, que le client transforme en rechargement complet.
     */
    public function test_a_stale_version_forces_a_full_reload(): void
    {
        $versionAnonyme = $this->version();

        $admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);

        $reponse = $this->actingAs($admin)->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $versionAnonyme,
            'X-Requested-With' => 'XMLHttpRequest',
        ])->get('/'.config('admin.url_prefix'));

        $reponse->assertStatus(409);

        $this->assertNotNull($reponse->headers->get('X-Inertia-Location'),
            'Inertia doit indiquer où recharger, sans quoi le client reste sur une page blanche.'
        );
    }

    /**
     * Et une visite portant la bonne version passe normalement.
     *
     * Sans ce contrôle, une version qui changerait à chaque requête ferait
     * recharger la page entière en permanence : la panne serait réparée en
     * annulant tout l'intérêt d'Inertia.
     */
    public function test_a_matching_version_is_served_normally(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);

        Auth::login($admin);
        $versionAdmin = $this->version();
        Auth::logout();

        $this->actingAs($admin)->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => $versionAdmin,
            'X-Requested-With' => 'XMLHttpRequest',
        ])->get('/'.config('admin.url_prefix'))->assertOk();
    }
}
