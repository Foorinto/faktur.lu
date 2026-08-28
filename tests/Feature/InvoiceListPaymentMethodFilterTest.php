<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Filtrer les factures par moyen d'encaissement (FEAT-114).
 *
 * Rend le récapitulatif actionnable : on lit « 3 300 € en espèces », on filtre,
 * on a les factures derrière.
 *
 * La subtilité tient au montant affiché. Une facture réglée moitié espèces
 * moitié virement apparaît dans les deux filtres — c'est exact — mais elle doit
 * alors montrer la PART qui revient au moyen filtré, pas son total encaissé.
 */
class InvoiceListPaymentMethodFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    private function facture(float $ttc): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        return Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_SENT,
            'finalized_at' => now()->subMonth(),
            'total_ht' => $ttc, 'total_vat' => 0, 'total_ttc' => $ttc,
        ]);
    }

    public function test_the_filter_keeps_only_invoices_paid_with_that_method(): void
    {
        $especes = $this->facture(500);
        $especes->payments()->create([
            'amount' => 500, 'paid_at' => now()->toDateString(), 'method' => 'cash',
        ]);

        $virement = $this->facture(800);
        $virement->payments()->create([
            'amount' => 800, 'paid_at' => now()->toDateString(), 'method' => 'transfer',
        ]);

        $this->actingAs($this->user)
            ->get('/invoices?payment_method=cash')
            ->assertOk()
            ->assertInertia(function ($page) use ($especes) {
                $lignes = collect($page->toArray()['props']['invoices']['data']);

                $this->assertCount(1, $lignes);
                $this->assertSame($especes->id, $lignes->first()['id']);
            });
    }

    /**
     * Le cœur du besoin : la part du moyen filtré, pas le total.
     */
    public function test_a_mixed_invoice_shows_the_share_of_the_filtered_method(): void
    {
        $facture = $this->facture(1000);
        $facture->payments()->createMany([
            ['amount' => 300, 'paid_at' => now()->toDateString(), 'method' => 'cash'],
            ['amount' => 700, 'paid_at' => now()->toDateString(), 'method' => 'transfer'],
        ]);

        $this->actingAs($this->user)
            ->get('/invoices?payment_method=cash')
            ->assertOk()
            ->assertInertia(function ($page) {
                $ligne = collect($page->toArray()['props']['invoices']['data'])->first();

                $this->assertSame(1000.0, (float) $ligne['encaisse'],
                    'Le total encaissé reste celui de la facture entière.');
                $this->assertSame(300.0, (float) $ligne['encaisse_moyen'],
                    'La part filtrée doit être celle des espèces seules.');
            });
    }

    /**
     * « Non renseigné » est filtrable : c'est ce qui permet de retrouver les
     * encaissements repris de l'ancien fonctionnement pour les compléter.
     */
    public function test_unknown_method_is_filterable(): void
    {
        $sansMoyen = $this->facture(400);
        $sansMoyen->payments()->create([
            'amount' => 400, 'paid_at' => now()->toDateString(), 'method' => null,
        ]);

        $avecMoyen = $this->facture(200);
        $avecMoyen->payments()->create([
            'amount' => 200, 'paid_at' => now()->toDateString(), 'method' => 'card',
        ]);

        $this->actingAs($this->user)
            ->get('/invoices?payment_method=unknown')
            ->assertOk()
            ->assertInertia(function ($page) use ($sansMoyen) {
                $lignes = collect($page->toArray()['props']['invoices']['data']);

                $this->assertCount(1, $lignes);
                $this->assertSame($sansMoyen->id, $lignes->first()['id']);
                $this->assertSame(400.0, (float) $lignes->first()['encaisse_moyen']);
            });
    }

    /**
     * Sans filtre, la part n'est pas calculée : elle n'aurait pas de sens.
     */
    public function test_without_a_filter_no_share_is_computed(): void
    {
        $this->facture(300)->payments()->create([
            'amount' => 300, 'paid_at' => now()->toDateString(), 'method' => 'cash',
        ]);

        $this->actingAs($this->user)
            ->get('/invoices')
            ->assertOk()
            ->assertInertia(function ($page) {
                $ligne = collect($page->toArray()['props']['invoices']['data'])->first();

                $this->assertArrayNotHasKey('encaisse_moyen', $ligne);
            });
    }

    public function test_an_unsupported_method_is_ignored(): void
    {
        $this->facture(100)->payments()->create([
            'amount' => 100, 'paid_at' => now()->toDateString(), 'method' => 'cash',
        ]);

        $this->actingAs($this->user)
            ->get('/invoices?payment_method=bitcoin')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.payment_method', null)
                ->has('invoices.data', 1)
            );
    }
}
