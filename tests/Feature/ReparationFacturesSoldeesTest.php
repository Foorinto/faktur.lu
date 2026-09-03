<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Reprise des factures entièrement réglées mais restées « dues ».
 *
 * Le correctif des encaissements fantômes empêchait de NOUVELLES factures
 * d'entrer dans cet état, et la migration supprimait les lignes à zéro. Aucun
 * des deux ne réparait les factures déjà bloquées.
 *
 * Ce n'est pas cosmétique : les relances automatiques visent les statuts
 * « finalisée » et « envoyée ». Une facture intégralement encaissée mais restée
 * « envoyée » fait donc relancer un client qui a déjà payé.
 */
class ReparationFacturesSoldeesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($this->user);
    }

    private function facture(string $statut, float $ttc, float $encaisse): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $facture = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => $statut,
            'type' => Invoice::TYPE_INVOICE,
            'issued_at' => now()->subDays(60),
            'finalized_at' => now()->subDays(60),
            'due_at' => now()->subDays(30),
            'total_ht' => $ttc, 'total_vat' => 0, 'total_ttc' => $ttc,
        ]);

        if ($encaisse > 0) {
            $facture->payments()->create([
                'amount' => $encaisse,
                'paid_at' => now()->subDays(61)->toDateString(),
                'method' => 'transfer',
            ]);
        }

        return $facture->fresh();
    }

    private function rejouerLaReprise(): void
    {
        $migration = require database_path('migrations/2026_09_03_140000_reparer_les_factures_soldees_restees_dues.php');
        $migration->up();
    }

    public function test_a_settled_invoice_left_as_due_is_repaired(): void
    {
        $facture = $this->facture(Invoice::STATUS_SENT, 1000, 1000);
        $this->assertFalse($facture->isPaid());

        $this->rejouerLaReprise();

        $this->assertTrue($facture->fresh()->isPaid(), 'Entièrement encaissée, elle doit être payée');
    }

    /**
     * L'enjeu réel : ne plus relancer un client à jour.
     */
    public function test_the_repaired_invoice_no_longer_triggers_a_reminder(): void
    {
        $this->facture(Invoice::STATUS_SENT, 1000, 1000);

        $this->rejouerLaReprise();

        $relancables = Invoice::whereIn('status', [Invoice::STATUS_FINALIZED, Invoice::STATUS_SENT])
            ->where('type', Invoice::TYPE_INVOICE)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now()->startOfDay())
            ->count();

        $this->assertSame(0, $relancables, 'Une facture payée ne doit plus être relançable');
    }

    /**
     * Les trois cas qui ne doivent PAS bouger.
     */
    public function test_a_partially_paid_invoice_is_left_alone(): void
    {
        $facture = $this->facture(Invoice::STATUS_SENT, 1000, 400);

        $this->rejouerLaReprise();

        $this->assertSame(Invoice::STATUS_SENT, $facture->fresh()->status);
        $this->assertSame(600.0, $facture->fresh()->amountDue());
    }

    public function test_an_unpaid_invoice_is_left_alone(): void
    {
        $facture = $this->facture(Invoice::STATUS_SENT, 1000, 0);

        $this->rejouerLaReprise();

        $this->assertSame(Invoice::STATUS_SENT, $facture->fresh()->status);
    }

    public function test_a_cancelled_invoice_is_never_touched(): void
    {
        $facture = $this->facture(Invoice::STATUS_CANCELLED, 1000, 1000);

        $this->rejouerLaReprise();

        $this->assertSame(Invoice::STATUS_CANCELLED, $facture->fresh()->status);
    }

    /**
     * La reprise est rejouable sans effet de bord.
     */
    public function test_running_the_repair_twice_changes_nothing(): void
    {
        $facture = $this->facture(Invoice::STATUS_SENT, 1000, 1000);

        $this->rejouerLaReprise();
        $premier = $facture->fresh()->paid_at;

        $this->rejouerLaReprise();

        $this->assertEquals($premier, $facture->fresh()->paid_at);
    }
}
