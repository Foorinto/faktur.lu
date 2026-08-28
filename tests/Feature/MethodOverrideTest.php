<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Substitution de méthode : un POST qui vaut PATCH.
 *
 * Le serveur mutualisé de production (LiteSpeed, o2switch) coupe la connexion
 * sur toute requête PATCH, avant PHP. Constaté le 2026-08-28 : dix-huit
 * fonctionnalités échouaient en ligne — modification d'un encaissement, mise à
 * jour du profil, validation des congés — pendant que tout marchait en local,
 * où `php artisan serve` accepte PATCH.
 *
 * Le client envoie donc un POST porteur de `X-HTTP-METHOD-OVERRIDE: PATCH`
 * (voir resources/js/Support/patchViaPost.js). Ces tests vérifient le versant
 * serveur : que Laravel achemine bien un tel POST vers la route PATCH.
 *
 * Ils gardent une garantie qui ne nous appartient pas — `getMethod()` de
 * Symfony, et `enableHttpMethodParameterOverride()` de Laravel. Une montée de
 * version qui la retirerait remettrait la production par terre en silence.
 */
class MethodOverrideTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($this->user);
    }

    private function factureAvecEncaissement(): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $facture = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_SENT,
            'finalized_at' => now()->subDays(10),
            'total_ht' => 1000, 'total_vat' => 0, 'total_ttc' => 1000,
        ]);

        $facture->payments()->create([
            'amount' => 300,
            'paid_at' => now()->subDays(3)->toDateString(),
            'method' => 'cash',
        ]);

        return $facture;
    }

    public function test_a_post_carrying_the_override_header_reaches_the_patch_route(): void
    {
        $facture = $this->factureAvecEncaissement();
        $encaissement = $facture->payments()->first();

        $this->post(
            "/invoices/{$facture->id}/payments/{$encaissement->id}",
            ['amount' => 500, 'paid_at' => $encaissement->paid_at->toDateString(), 'method' => 'transfer'],
            ['X-HTTP-METHOD-OVERRIDE' => 'PATCH']
        )->assertRedirect();

        $encaissement->refresh();

        $this->assertSame(500.0, (float) $encaissement->amount);
        $this->assertSame('transfer', $encaissement->method);
    }

    /**
     * Sans l'en-tête, la même requête n'a nulle part où aller : c'est ce qui
     * prouve que c'est bien lui qui achemine, et non un POST accepté par
     * ailleurs.
     */
    public function test_the_same_post_without_the_header_matches_no_route(): void
    {
        $facture = $this->factureAvecEncaissement();
        $encaissement = $facture->payments()->first();

        $this->post("/invoices/{$facture->id}/payments/{$encaissement->id}", ['amount' => 500])
            ->assertStatus(405);

        $this->assertSame(300.0, (float) $encaissement->fresh()->amount);
    }

    /**
     * La substitution vaut pour toute l'application, pas pour cette seule
     * route : l'intercepteur est global.
     */
    public function test_the_profile_route_accepts_the_override_too(): void
    {
        $this->post(
            '/profile',
            ['name' => 'Nom Modifié', 'email' => $this->user->email],
            ['X-HTTP-METHOD-OVERRIDE' => 'PATCH']
        )->assertRedirect();

        $this->assertSame('Nom Modifié', $this->user->fresh()->name);
    }
}
