<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * FEAT-099: conditions de paiement éditables / masquables.
 */
class PaymentConditionsTest extends TestCase
{
    use RefreshDatabase;

    private function renderInvoiceWithSettings(array $settingsAttrs): string
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Client::factory()->create(['user_id' => $user->id]);
        BusinessSettings::factory()->create($settingsAttrs);

        $invoice = Invoice::factory()->create([
            'client_id' => Client::first()->id,
            'user_id' => $user->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'title' => 'X', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 17]);

        return app(InvoicePdfService::class)->previewDraft($invoice->fresh());
    }

    public function test_defaults_are_preserved(): void
    {
        $html = $this->renderInvoiceWithSettings([]);

        $this->assertStringContainsString('3 fois le taux légal', $html);
        $this->assertStringContainsString('40 €', $html);
        $this->assertStringContainsString('Aucun', $html);
    }

    public function test_custom_conditions_are_used(): void
    {
        $html = $this->renderInvoiceWithSettings([
            'late_penalty_text' => 'Aucune pénalité',
            'recovery_fee_amount' => 25,
            'discount_terms' => '2 % sous 10 jours',
        ]);

        $this->assertStringContainsString('Aucune pénalité', $html);
        $this->assertStringContainsString('25 €', $html);
        $this->assertStringContainsString('2 % sous 10 jours', $html);
        $this->assertStringNotContainsString('3 fois le taux légal', $html);
    }

    public function test_conditions_hidden_when_disabled(): void
    {
        $html = $this->renderInvoiceWithSettings([
            'show_payment_conditions' => false,
        ]);

        $this->assertStringNotContainsString('3 fois le taux légal', $html);
        $this->assertStringNotContainsString('Indemnité forfaitaire', $html);
        // Le délai de paiement et le moyen de paiement restent affichés.
        $this->assertStringContainsString(__('invoice.payment_delay'), $html);
    }
}
