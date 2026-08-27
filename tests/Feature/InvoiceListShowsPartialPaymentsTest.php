<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * La liste des factures montre les encaissements partiels.
 *
 * Sans cela, une facture réglée à moitié s'affiche comme n'importe quelle
 * facture due : le montant total, et rien qui dise qu'une partie est rentrée.
 * L'utilisateur doit ouvrir chaque facture pour connaître l'état réel de sa
 * créance.
 */
class InvoiceListShowsPartialPaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_list_carries_the_amount_received(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $client = Client::factory()->create(['user_id' => $user->id]);

        $facture = Invoice::factory()->create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_SENT,
            'finalized_at' => now()->subDays(5),
            'total_ht' => 1000,
            'total_vat' => 0,
            'total_ttc' => 1000,
        ]);

        $facture->payments()->create([
            'amount' => 400,
            'paid_at' => now()->subDay()->toDateString(),
            'method' => 'cash',
        ]);

        $this->actingAs($user)
            ->get('/invoices')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('invoices.data.0.encaisse', fn ($v) => (float) $v === 400.0)
            );
    }

    /**
     * L'agrégat vient d'une seule requête, pas d'une relation chargée.
     *
     * Charger `payments` pour quinze factures produirait quinze requêtes de
     * plus à chaque page. Ce test compte les requêtes plutôt que de faire
     * confiance à la forme du code.
     */
    public function test_the_list_does_not_query_once_per_invoice(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $client = Client::factory()->create(['user_id' => $user->id]);

        foreach (range(1, 10) as $i) {
            $facture = Invoice::factory()->create([
                'user_id' => $user->id,
                'client_id' => $client->id,
                'status' => Invoice::STATUS_SENT,
                'finalized_at' => now()->subDays(5),
                'total_ht' => 100, 'total_vat' => 0, 'total_ttc' => 100,
            ]);
            $facture->payments()->create([
                'amount' => 50,
                'paid_at' => now()->toDateString(),
                'method' => 'transfer',
            ]);
        }

        $requetes = 0;
        \Illuminate\Support\Facades\DB::listen(function () use (&$requetes) {
            $requetes++;
        });

        $this->actingAs($user)->get('/invoices')->assertOk();

        // Large, mais très en dessous des dix requêtes qu'une relation
        // chargée par facture ajouterait.
        $this->assertLessThan(20, $requetes,
            "La liste exécute {$requetes} requêtes : l'agrégat des encaissements a probablement été remplacé par un chargement de relation."
        );
    }
}
