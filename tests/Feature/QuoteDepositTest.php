<?php

namespace Tests\Feature;

use App\Actions\ConvertQuoteToInvoiceAction;
use App\Models\Client;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Acompte demandé sur le devis (« confirmation de commande »).
 *
 * Demande d'un client payant, coiffure-esthétique (2026-08-28) :
 *
 *     « ma cliente me le paie pour valider mon devis, et c'est ce qui permet
 *       de fixer le rdv final »
 *
 * Le devis annonce donc le montant attendu, l'acompte suit à la conversion, et
 * la facture propose de l'encaisser en un clic.
 *
 * ⚠️ CE QUI NE DOIT JAMAIS BOUGER : les totaux. L'acompte est une DEMANDE, pas
 * un encaissement et surtout pas une remise. Une remise réduirait la base
 * taxable et donc la TVA ; ici le prix est le même, seul le calendrier de
 * paiement change.
 */
class QuoteDepositTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);
        $this->actingAs($this->user);
        \App\Models\BusinessSettings::factory()->assujetti()->create(['user_id' => $this->user->id]);
    }

    private function devis(array $acompte = [], float $ttc = 1170): Quote
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $devis = Quote::create(array_merge([
            'client_id' => $client->id,
            'currency' => 'EUR',
            'status' => Quote::STATUS_ACCEPTED,
            'total_ht' => 1000, 'total_vat' => 170, 'total_ttc' => $ttc,
        ], $acompte));

        $devis->items()->create([
            'title' => 'Prestation',
            'quantity' => 1,
            'unit_price' => 1000,
            'vat_rate' => 17,
            'total_ht' => 1000,
            'position' => 1,
        ]);

        return $devis->fresh();
    }

    public function test_a_percentage_deposit_is_computed_on_the_gross_total(): void
    {
        // Le pourcentage porte sur le TTC : c'est la somme que le client sort
        // de sa poche, et celle qu'il comprend.
        $devis = $this->devis(['deposit_type' => 'percent', 'deposit_value' => 30]);

        $this->assertSame(351.0, $devis->depositAmount());
        $this->assertSame(819.0, $devis->depositBalance());
    }

    public function test_a_fixed_deposit_is_taken_as_is(): void
    {
        $devis = $this->devis(['deposit_type' => 'amount', 'deposit_value' => 250]);

        $this->assertSame(250.0, $devis->depositAmount());
        $this->assertSame(920.0, $devis->depositBalance());
    }

    /**
     * Au-delà du total, ce n'est plus un acompte.
     */
    public function test_a_deposit_never_exceeds_the_total(): void
    {
        $devis = $this->devis(['deposit_type' => 'amount', 'deposit_value' => 5000]);

        $this->assertSame(1170.0, $devis->depositAmount());
        $this->assertSame(0.0, $devis->depositBalance());
    }

    public function test_no_deposit_means_nothing_to_show(): void
    {
        $devis = $this->devis();

        $this->assertNull($devis->depositAmount());
        $this->assertFalse($devis->hasDeposit());
    }

    /**
     * ⚠️ Le cœur du test : l'acompte ne touche pas aux totaux.
     */
    public function test_a_deposit_changes_neither_the_total_nor_the_vat(): void
    {
        $devis = $this->devis(['deposit_type' => 'percent', 'deposit_value' => 30]);

        $this->assertSame('1000.0000', (string) $devis->total_ht);
        // 170 € de TVA sur 1 000 € HT, et pas 119 € comme le donnerait une remise.
        $this->assertSame('170.0000', (string) $devis->total_vat);
        $this->assertSame('1170.0000', (string) $devis->total_ttc);
    }

    public function test_the_quote_document_announces_the_deposit(): void
    {
        $devis = $this->devis(['deposit_type' => 'percent', 'deposit_value' => 30]);

        $html = view('pdf.quote', app(\App\Services\QuotePdfService::class)->prepareData($devis))->render();

        $this->assertStringContainsString(__('invoice.deposit_requested'), $html);
        $this->assertStringContainsString('351,00', $html);
        $this->assertStringContainsString(__('invoice.deposit_balance'), $html);
        $this->assertStringContainsString('819,00', $html);
        // Le total reste affiché en entier.
        $this->assertStringContainsString('1 170,00', $html);
    }

    public function test_a_quote_without_deposit_shows_no_block(): void
    {
        $devis = $this->devis();

        $html = view('pdf.quote', app(\App\Services\QuotePdfService::class)->prepareData($devis))->render();

        $this->assertStringNotContainsString(__('invoice.deposit_requested'), $html);
    }

    /**
     * L'acompte suit la conversion : c'est lui qui a été annoncé au client.
     */
    public function test_the_deposit_follows_the_conversion(): void
    {
        $devis = $this->devis(['deposit_type' => 'percent', 'deposit_value' => 30]);

        $facture = app(ConvertQuoteToInvoiceAction::class)->execute($devis);

        $this->assertSame('percent', $facture->deposit_type);
        $this->assertSame(351.0, $facture->depositAmount());
    }

    /**
     * Et il ne fabrique aucun encaissement : le versement se constate, il ne
     * se suppose pas.
     */
    public function test_the_conversion_creates_no_payment(): void
    {
        $devis = $this->devis(['deposit_type' => 'percent', 'deposit_value' => 30]);

        $facture = app(ConvertQuoteToInvoiceAction::class)->execute($devis);

        $this->assertSame(0.0, $facture->amountPaid());
        $this->assertCount(0, $facture->payments);
    }

    public function test_the_quote_form_accepts_a_deposit(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $this->post(route('quotes.store'), [
            'client_id' => $client->id,
            'deposit_type' => 'percent',
            'deposit_value' => 30,
        ])->assertSessionHasNoErrors();

        $this->assertSame('percent', Quote::latest('id')->first()->deposit_type);
    }

    public function test_an_unknown_deposit_type_is_refused(): void
    {
        $client = Client::factory()->create(['user_id' => $this->user->id]);

        $this->post(route('quotes.store'), [
            'client_id' => $client->id,
            'deposit_type' => 'remise',
            'deposit_value' => 30,
        ])->assertSessionHasErrors('deposit_type');
    }
}
