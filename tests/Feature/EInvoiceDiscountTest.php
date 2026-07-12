<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\FacturXService;
use App\Services\PeppolExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EInvoiceDiscountTest extends TestCase
{
    use RefreshDatabase;

    private function invoiceWithDiscount(): Invoice
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        BusinessSettings::factory()->create();

        $invoice = Invoice::factory()->finalized()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);

        // 2 × 100 with a 10 % discount → gross 200, allowance 20, net 180
        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title' => 'Prestation remisée',
            'quantity' => 2,
            'unit_price' => 100,
            'discount_type' => 'percent',
            'discount_value' => 10,
            'vat_rate' => 17,
        ]);

        return $invoice->fresh();
    }

    public function test_facturx_emits_line_allowance_reconciling_the_total(): void
    {
        $xml = app(FacturXService::class)->generateXml($this->invoiceWithDiscount());

        // Line allowance (discount) present with the exact amount
        $this->assertStringContainsString('<ram:SpecifiedTradeAllowanceCharge>', $xml);
        $this->assertStringContainsString('<udt:Indicator>false</udt:Indicator>', $xml);
        $this->assertMatchesRegularExpression('/<ram:ActualAmount[^>]*>20\.00<\/ram:ActualAmount>/', $xml);

        // Line total reflects the discounted net (gross 200 − allowance 20 = 180)
        $this->assertMatchesRegularExpression('/<ram:LineTotalAmount[^>]*>180\.00<\/ram:LineTotalAmount>/', $xml);
        // Gross unit price is preserved on the price element
        $this->assertMatchesRegularExpression('/<ram:ChargeAmount[^>]*>100\.00<\/ram:ChargeAmount>/', $xml);
    }

    public function test_peppol_emits_line_allowance_reconciling_the_total(): void
    {
        $xml = app(PeppolExportService::class)->generate($this->invoiceWithDiscount());

        $this->assertStringContainsString('<cac:AllowanceCharge', $xml);
        $this->assertMatchesRegularExpression('/<cbc:ChargeIndicator[^>]*>false<\/cbc:ChargeIndicator>/', $xml);
        $this->assertMatchesRegularExpression('/<cbc:Amount[^>]*>20\.00<\/cbc:Amount>/', $xml);

        // LineExtensionAmount = gross (100 × 2) − allowance (20) = 180
        $this->assertMatchesRegularExpression('/<cbc:LineExtensionAmount[^>]*>180\.00<\/cbc:LineExtensionAmount>/', $xml);
        // Gross unit price preserved
        $this->assertMatchesRegularExpression('/<cbc:PriceAmount[^>]*>100\.00<\/cbc:PriceAmount>/', $xml);
    }
}
