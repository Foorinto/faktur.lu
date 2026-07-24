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
 * FEAT-098: moyens de paiement personnalisables sur la facture.
 */
class PaymentMethodsTest extends TestCase
{
    use RefreshDatabase;

    public function test_effective_payment_methods_default_and_freetext(): void
    {
        $settings = new BusinessSettings();
        // Aucun réglage → défaut Virement.
        $this->assertSame(['transfer'], $settings->getEffectivePaymentMethods());

        // Texte libre (FEAT-098) : valeurs conservées, vides retirées, ordre préservé.
        $settings->default_payment_methods = ['Wero', '  ', 'Mon moyen à moi'];
        $this->assertSame(['Wero', 'Mon moyen à moi'], $settings->getEffectivePaymentMethods());

        // Les anciennes clés restent acceptées telles quelles (compat).
        $settings->default_payment_methods = ['transfer', 'cash'];
        $this->assertSame(['transfer', 'cash'], $settings->getEffectivePaymentMethods());
    }

    public function test_invoice_pdf_shows_configured_payment_methods(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Client::factory()->create(['user_id' => $user->id]);
        BusinessSettings::factory()->create([
            // Clé connue traduite ('transfer') + texte libre affiché tel quel.
            'default_payment_methods' => ['transfer', 'Wero', 'Espèces sur place'],
        ]);

        $client = Client::first();
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'title' => 'X', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 17]);

        $html = app(InvoicePdfService::class)->previewDraft($invoice->fresh());

        $this->assertStringContainsString(__('app.payment_methods.transfer'), $html);
        $this->assertStringContainsString('Wero', $html);
        $this->assertStringContainsString('Espèces sur place', $html);
    }

    public function test_invoice_pdf_shows_payment_instructions(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Client::factory()->create(['user_id' => $user->id]);
        BusinessSettings::factory()->create([
            'payment_instructions' => '<p>Chèque à l\'ordre de <strong>Test SARL</strong>. <a href="https://pay.me">Payer</a></p><script>alert(1)</script>',
        ]);

        $client = Client::first();
        $invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
            'status' => Invoice::STATUS_DRAFT,
        ]);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'title' => 'X', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 17]);

        $html = app(InvoicePdfService::class)->previewDraft($invoice->fresh());

        // Mise en forme WYSIWYG conservée (gras + lien), script retiré.
        $this->assertStringContainsString('<strong>Test SARL</strong>', $html);
        $this->assertStringContainsString('href="https://pay.me"', $html);
        $this->assertStringNotContainsString('<script', $html);
    }

    public function test_payment_methods_persist_and_cast_to_array(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $settings = BusinessSettings::factory()->create([
            'default_payment_methods' => ['transfer', 'payconiq'],
        ]);

        $this->assertSame(['transfer', 'payconiq'], $settings->fresh()->getEffectivePaymentMethods());
        // Inclus dans le snapshot (factures finalisées).
        $this->assertSame(['transfer', 'payconiq'], $settings->fresh()->toSnapshot()['default_payment_methods']);
    }
}
