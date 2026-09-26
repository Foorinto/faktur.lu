<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Recherche et fiche des utilisateurs dans l'administration.
 *
 * Le 26/09/2026, la recherche tombait en production : elle cherchait dans
 * une colonne users.company_name qui n'a jamais existé (le nom d'entreprise
 * est dans les réglages). SQLite, ici, la lisait comme du texte sans erreur ;
 * d'où une recherche par nom d'entreprise, que l'ancienne requête ne
 * trouvait jamais.
 */
class AdminUserSearchTest extends TestCase
{
    use RefreshDatabase;

    private function chemin(string $suite = ''): string
    {
        return '/'.config('admin.url_prefix').'/users'.$suite;
    }

    public function test_la_recherche_trouve_un_compte_par_son_nom_d_entreprise(): void
    {
        $admin = User::factory()->admin()->create();
        $cible = User::factory()->create(['name' => 'Marie Schmit', 'email' => 'marie@exemple.lu']);
        BusinessSettings::factory()->create(['user_id' => $cible->id, 'company_name' => 'Atelier Céramique Schmit']);
        $autre = User::factory()->create(['name' => 'Paul Weber', 'email' => 'paul@exemple.lu']);
        BusinessSettings::factory()->create(['user_id' => $autre->id, 'company_name' => 'Menuiserie Weber']);

        $this->actingAs($admin)
            ->get($this->chemin('?search=Céramique'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Users/Index')
                ->has('users.data', 1)
                ->where('users.data.0.email', 'marie@exemple.lu')
                ->where('users.data.0.business_settings.company_name', 'Atelier Céramique Schmit')
            );

        // Nom et adresse restent cherchables.
        $this->actingAs($admin)->get($this->chemin('?search=Weber'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('users.data', 1)->where('users.data.0.email', 'paul@exemple.lu'));
        $this->actingAs($admin)->get($this->chemin('?search=marie@'))
            ->assertInertia(fn (AssertableInertia $page) => $page->has('users.data', 1));
    }

    public function test_la_fiche_affiche_la_societe_la_tva_et_le_telephone_des_reglages(): void
    {
        $admin = User::factory()->admin()->create();
        $cible = User::factory()->create();
        BusinessSettings::factory()->create([
            'user_id' => $cible->id, 'company_name' => 'Atelier Céramique Schmit', 'vat_number' => 'LU12345678', 'phone' => '+352 621 000 000',
        ]);

        $this->actingAs($admin)
            ->get($this->chemin('/'.$cible->id))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('user.company_name', 'Atelier Céramique Schmit')
                ->where('user.vat_number', 'LU12345678')
                ->where('user.phone', '+352 621 000 000')
            );
    }
}
