<?php

namespace Tests\Feature;

use App\Models\SectorLead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Suppression d'une réponse sectorielle depuis l'administration.
 *
 * Deux besoins la justifient : le spam qui franchit le pot de miel, et une
 * demande d'effacement. L'adresse est une donnée personnelle, donnée pour être
 * recontacté, et rien n'oblige la personne à le rester.
 *
 * Ce qui se teste ici n'est pas tant que la suppression fonctionne — une ligne
 * de code — que le fait qu'elle soit réservée aux administrateurs et qu'elle
 * n'emporte rien d'autre au passage.
 */
class AdminSectorLeadDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function chemin(SectorLead $lead): string
    {
        return '/'.config('admin.url_prefix').'/secteurs/'.$lead->id;
    }

    private function reponse(array $attributs = []): SectorLead
    {
        return SectorLead::create(array_merge([
            'sector' => 'health',
            'email' => 'infirmier@exemple.lu',
            'message' => 'Recopier les mêmes actes chaque semaine.',
            'locale' => 'fr',
            'wants_newsletter' => false,
        ], $attributs));
    }

    public function test_an_administrator_can_delete_a_response(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);
        $reponse = $this->reponse();

        $this->actingAs($admin)
            ->delete($this->chemin($reponse))
            ->assertRedirect();

        $this->assertDatabaseMissing('sector_leads', ['id' => $reponse->id]);
    }

    public function test_only_the_targeted_response_disappears(): void
    {
        $admin = User::factory()->create(['is_admin' => true, 'email_verified_at' => now()]);

        $cible = $this->reponse(['email' => 'a@exemple.lu']);
        $voisine = $this->reponse(['email' => 'b@exemple.lu']);

        $this->actingAs($admin)->delete($this->chemin($cible));

        $this->assertDatabaseMissing('sector_leads', ['id' => $cible->id]);
        $this->assertDatabaseHas('sector_leads', ['id' => $voisine->id]);
    }

    public function test_an_ordinary_user_cannot_delete_a_response(): void
    {
        $utilisateur = User::factory()->create(['is_admin' => false, 'email_verified_at' => now()]);
        $reponse = $this->reponse();

        $this->actingAs($utilisateur)
            ->delete($this->chemin($reponse))
            ->assertForbidden();

        $this->assertDatabaseHas('sector_leads', ['id' => $reponse->id]);
    }

    public function test_an_anonymous_visitor_cannot_delete_a_response(): void
    {
        $reponse = $this->reponse();

        $this->delete($this->chemin($reponse));

        $this->assertDatabaseHas('sector_leads', ['id' => $reponse->id]);
    }

    /**
     * L'administration affiche ses messages de confirmation.
     *
     * Les contrôleurs redirigent avec `->with('success', …)` depuis toujours,
     * mais le toast n'était monté que dans les gabarits applicatif,
     * collaborateur et employé. « Ticket supprimé. » ne s'est jamais vu, et
     * une suppression se faisait sans un mot.
     */
    public function test_the_admin_layout_displays_flash_messages(): void
    {
        $gabarit = file_get_contents(resource_path('js/Layouts/AdminLayout.vue'));

        $this->assertStringContainsString('<ToastNotification />', $gabarit,
            "Le gabarit d'administration n'affiche plus les messages de confirmation."
        );
    }
}
