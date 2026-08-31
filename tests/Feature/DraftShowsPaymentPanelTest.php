<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Le brouillon reçoit de quoi saisir un acompte.
 *
 * J'avais ouvert la saisie côté serveur et déverrouillé le bloc dans
 * `Invoices/Show.vue` — sans voir qu'un brouillon n'ouvre pas cette page. La
 * liste des factures renvoie les brouillons vers `Invoices/Edit.vue`, où le
 * bloc n'existait pas. L'auteur l'a constaté immédiatement : « en brouillon je
 * ne vois pas où ajouter un encaissement » (2026-08-31).
 *
 * Ces tests vérifient que les DEUX pages reçoivent de quoi l'afficher.
 */
class DraftShowsPaymentPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($this->user);
        BusinessSettings::factory()->assujetti()->create(['user_id' => $this->user->id]);
    }

    private function brouillon(): Invoice
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        return Invoice::factory()->create([
            'user_id' => $this->user->id,
            'client_id' => $client->id,
            'status' => Invoice::STATUS_DRAFT,
            'finalized_at' => null,
            'number' => null,
            'total_ht' => 1000, 'total_vat' => 0, 'total_ttc' => 1000,
            'deposit_type' => 'percent', 'deposit_value' => 30,
        ]);
    }

    public function test_the_edit_page_carries_the_payments(): void
    {
        $brouillon = $this->brouillon();
        $brouillon->payments()->create([
            'amount' => 300, 'paid_at' => now()->subDays(3)->toDateString(), 'method' => 'transfer',
        ]);

        $this->get(route('invoices.edit', $brouillon))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Invoices/Edit')
                ->has('payments', 1)
                ->where('paymentSummary.paid', 300)
            );
    }

    /**
     * L'acompte annoncé sur le devis se propose dès le brouillon : c'est là
     * qu'on l'encaisse.
     */
    public function test_the_expected_deposit_reaches_the_edit_page(): void
    {
        $this->get(route('invoices.edit', $this->brouillon()))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('paymentSummary.deposit', 300)
            );
    }

    public function test_the_payment_methods_reach_the_edit_page(): void
    {
        // Sans elles, le sélecteur de moyen serait vide et l'utilisateur ne
        // pourrait pas dire comment il a été payé.
        $this->get(route('invoices.edit', $this->brouillon()))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('paymentMethods', count(\App\Models\InvoicePayment::METHODS))
            );
    }

    /**
     * La page de consultation continue de les porter : après finalisation,
     * c'est là qu'on corrige et qu'on encaisse le solde.
     */
    public function test_the_show_page_still_carries_them(): void
    {
        $facture = $this->brouillon();
        $facture->forceFill([
            'status' => Invoice::STATUS_SENT,
            'finalized_at' => now(),
            'number' => 'FAC-2026-900',
        ])->saveQuietly();

        $this->get(route('invoices.show', $facture))
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Invoices/Show')
                ->has('paymentSummary')
                ->has('paymentMethods')
            );
    }
}
