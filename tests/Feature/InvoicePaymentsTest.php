<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Encaissements par moyen de paiement (FEAT-114).
 *
 * Demande d'un client payant, dont la précision a décidé de la forme :
 *
 *     « parfois par différent moyen par facture : j'ai des fois un paiement
 *       en espèces pour une partie et le reste par virement »
 *
 * Ce qui se teste ici n'est pas « on peut enregistrer un paiement » — une
 * ligne de code — mais les trois points où la comptabilité se joue : le statut
 * dérivé, le retour en arrière, et le refus d'inventer un moyen inconnu.
 */
class InvoicePaymentsTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
    }

    private function factureFinalisee(float $ttc = 1000.0): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        return Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_SENT,
            'finalized_at' => now()->subDays(10),
            'total_ht' => $ttc,
            'total_vat' => 0,
            'total_ttc' => $ttc,
        ]);
    }

    public function test_a_partial_payment_leaves_the_invoice_due(): void
    {
        $facture = $this->factureFinalisee(1000);

        $this->actingAs($this->user)->post("/invoices/{$facture->id}/payments", [
            'amount' => 300,
            'paid_at' => now()->subDays(3)->toDateString(),
            'method' => 'cash',
        ])->assertRedirect();

        $facture->refresh();

        $this->assertSame(300.0, $facture->amountPaid());
        $this->assertSame(700.0, $facture->amountDue());
        $this->assertFalse($facture->isPaid());
        $this->assertTrue($facture->isPartiallyPaid());
    }

    /**
     * Le cas exact décrit par le client : une partie en espèces, le reste par
     * virement. La facture doit basculer seule à « payée ».
     */
    public function test_two_methods_on_one_invoice_settle_it(): void
    {
        $facture = $this->factureFinalisee(1000);

        foreach ([[300, 'cash'], [700, 'transfer']] as [$montant, $moyen]) {
            $this->actingAs($this->user)->post("/invoices/{$facture->id}/payments", [
                'amount' => $montant,
                'paid_at' => now()->subDay()->toDateString(),
                'method' => $moyen,
            ]);
        }

        $facture->refresh();

        $this->assertSame(0.0, $facture->amountDue());
        $this->assertTrue($facture->isPaid());
        $this->assertFalse($facture->isPartiallyPaid());
        $this->assertCount(2, $facture->payments);
    }

    /**
     * Un encaissement partiel se corrige.
     */
    public function test_a_partial_payment_can_be_deleted(): void
    {
        $facture = $this->factureFinalisee(500);

        $this->actingAs($this->user)->post("/invoices/{$facture->id}/payments", [
            'amount' => 200,
            'paid_at' => now()->toDateString(),
            'method' => 'card',
        ]);

        $encaissement = $facture->refresh()->payments()->first();

        $this->actingAs($this->user)
            ->delete("/invoices/{$facture->id}/payments/{$encaissement->id}")
            ->assertRedirect();

        $facture->refresh();

        $this->assertSame(0.0, $facture->amountPaid());
        $this->assertSame(500.0, $facture->amountDue());
        $this->assertFalse($facture->isPaid());
    }

    /**
     * Une facture soldée verrouille ses encaissements.
     *
     * ⚠️ Ce n'est pas un choix de confort : le garde d'immuabilité du modèle
     * fait de « payée » un statut terminal — `finalized → sent|paid|cancelled`
     * et `sent → paid|cancelled`, rien n'en sort. Autoriser la suppression
     * laisserait une facture marquée payée sans montant pour l'appuyer.
     *
     * La contrepartie est réelle : une erreur de saisie sur le dernier
     * encaissement n'est plus corrigeable. Voir FEAT-114.
     */
    public function test_a_settled_invoice_locks_its_payments(): void
    {
        $facture = $this->factureFinalisee(500);

        $this->actingAs($this->user)->post("/invoices/{$facture->id}/payments", [
            'amount' => 500,
            'paid_at' => now()->toDateString(),
            'method' => 'card',
        ]);

        $this->assertTrue($facture->refresh()->isPaid());

        $encaissement = $facture->payments()->first();

        $this->actingAs($this->user)
            ->delete("/invoices/{$facture->id}/payments/{$encaissement->id}")
            ->assertSessionHas('error');

        $this->assertCount(1, $facture->refresh()->payments);
        $this->assertTrue($facture->isPaid());
    }

    /**
     * Un trop-perçu s'enregistre, mais le reste dû ne devient pas négatif.
     */
    public function test_an_overpayment_does_not_produce_a_negative_balance(): void
    {
        $facture = $this->factureFinalisee(100);

        $this->actingAs($this->user)->post("/invoices/{$facture->id}/payments", [
            'amount' => 150,
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
        ]);

        $facture->refresh();

        $this->assertSame(150.0, $facture->amountPaid());
        $this->assertSame(0.0, $facture->amountDue());
        $this->assertTrue($facture->isPaid());
    }

    /**
     * Le moyen reste facultatif, et « non renseigné » se dit.
     *
     * Les encaissements repris des factures déjà payées n'ont pas de moyen.
     * Les afficher comme « virement » fabriquerait une donnée comptable qui
     * n'a jamais existé, dans des documents conservés dix ans.
     */
    public function test_an_unknown_method_says_so(): void
    {
        $facture = $this->factureFinalisee(200);

        $this->actingAs($this->user)->post("/invoices/{$facture->id}/payments", [
            'amount' => 200,
            'paid_at' => now()->toDateString(),
        ])->assertRedirect();

        $encaissement = $facture->refresh()->payments()->first();

        $this->assertNull($encaissement->method);
        $this->assertSame(__('app.payment_methods.unknown'), $encaissement->methodLabel());
        $this->assertNotSame(__('app.payment_methods.transfer'), $encaissement->methodLabel());
    }

    public function test_an_unsupported_method_is_refused(): void
    {
        $facture = $this->factureFinalisee();

        $this->actingAs($this->user)->post("/invoices/{$facture->id}/payments", [
            'amount' => 100,
            'paid_at' => now()->toDateString(),
            'method' => 'bitcoin',
        ])->assertSessionHasErrors('method');

        $this->assertCount(0, $facture->refresh()->payments);
    }

    /**
     * Wero figure parmi les moyens, à la demande du client — et Payconiq reste,
     * le temps de l'absorption européenne.
     */
    public function test_wero_and_payconiq_coexist(): void
    {
        $this->assertContains('wero', InvoicePayment::METHODS);
        $this->assertContains('payconiq', InvoicePayment::METHODS);

        foreach (['fr', 'de', 'en', 'lb', 'pt'] as $langue) {
            $this->assertTrue(
                \Illuminate\Support\Facades\Lang::has('app.payment_methods.wero', $langue, false),
                "Le libellé « wero » manque en {$langue}."
            );
            $this->assertTrue(
                \Illuminate\Support\Facades\Lang::has('app.payment_methods.unknown', $langue, false),
                "Le libellé « non renseigné » manque en {$langue}."
            );
        }
    }

    /**
     * Marquer payée en un geste passe par un encaissement.
     *
     * Sans cela, la facture serait « payée » sans qu'aucun montant ne figure au
     * récapitulatif par moyen — ce que le client cherche précisément à obtenir.
     */
    public function test_marking_paid_creates_a_payment(): void
    {
        $facture = $this->factureFinalisee(800);

        $this->actingAs($this->user)->post("/invoices/{$facture->id}/mark-paid", [
            'paid_at' => now()->toDateString(),
            'method' => 'transfer',
        ])->assertRedirect();

        $facture->refresh();

        $this->assertTrue($facture->isPaid());
        $this->assertCount(1, $facture->payments);
        $this->assertSame(800.0, $facture->amountPaid());
        $this->assertSame('transfer', $facture->payments->first()->method);
    }
}
