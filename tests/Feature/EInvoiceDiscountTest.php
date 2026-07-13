<?php

namespace Tests\Feature;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceDiscount;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Services\DocumentTotalsCalculator;
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

    private function invoiceWithGlobalDiscount(): Invoice
    {
        $user = User::factory()->create();
        $client = Client::factory()->create(['user_id' => $user->id]);
        BusinessSettings::factory()->create();

        $invoice = Invoice::factory()->finalized()->create([
            'client_id' => $client->id,
            'user_id' => $user->id,
        ]);

        // 100 @ 17 % + 100 @ 3 %, global discount 50 € → net 150, VAT ventilated to 15
        InvoiceItem::create(['invoice_id' => $invoice->id, 'title' => 'A', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 17]);
        InvoiceItem::create(['invoice_id' => $invoice->id, 'title' => 'B', 'quantity' => 1, 'unit_price' => 100, 'vat_rate' => 3]);
        InvoiceDiscount::create(['invoice_id' => $invoice->id, 'label' => 'Promo', 'type' => 'amount', 'value' => 50]);

        // Finalized invoices don't auto-recalc; store the correct totals for the test.
        $invoice->refresh();
        $totals = app(DocumentTotalsCalculator::class)->compute($invoice->items, $invoice->discounts);
        $invoice->forceFill([
            'total_ht' => $totals['total_ht'],
            'total_vat' => $totals['total_vat'],
            'total_ttc' => $totals['total_ttc'],
        ])->saveQuietly();

        return $invoice->fresh();
    }

    public function test_facturx_document_allowance_for_global_discount(): void
    {
        $xml = app(FacturXService::class)->generateXml($this->invoiceWithGlobalDiscount());

        // Document-level allowance carries a VAT category (BG-20)
        $this->assertStringContainsString('<ram:CategoryTradeTax>', $xml);
        // Header summation reconciles: 200 line total − 50 allowance = 150 basis
        $this->assertMatchesRegularExpression('/<ram:LineTotalAmount[^>]*>200\.00<\/ram:LineTotalAmount>/', $xml);
        $this->assertMatchesRegularExpression('/<ram:AllowanceTotalAmount[^>]*>50\.00<\/ram:AllowanceTotalAmount>/', $xml);
        $this->assertMatchesRegularExpression('/<ram:TaxBasisTotalAmount[^>]*>150\.00<\/ram:TaxBasisTotalAmount>/', $xml);
        $this->assertMatchesRegularExpression('/<ram:GrandTotalAmount[^>]*>165\.00<\/ram:GrandTotalAmount>/', $xml);
        // Per-rate taxable basis is the ventilated net (75 @ each rate)
        $this->assertMatchesRegularExpression('/<ram:BasisAmount[^>]*>75\.00<\/ram:BasisAmount>/', $xml);
    }

    public function test_peppol_document_allowance_for_global_discount(): void
    {
        $xml = app(PeppolExportService::class)->generate($this->invoiceWithGlobalDiscount());

        $this->assertStringContainsString('<cac:AllowanceCharge', $xml);
        $this->assertMatchesRegularExpression('/<cbc:LineExtensionAmount[^>]*>200\.00<\/cbc:LineExtensionAmount>/', $xml);
        $this->assertMatchesRegularExpression('/<cbc:AllowanceTotalAmount[^>]*>50\.00<\/cbc:AllowanceTotalAmount>/', $xml);
        $this->assertMatchesRegularExpression('/<cbc:TaxExclusiveAmount[^>]*>150\.00<\/cbc:TaxExclusiveAmount>/', $xml);
        $this->assertMatchesRegularExpression('/<cbc:TaxInclusiveAmount[^>]*>165\.00<\/cbc:TaxInclusiveAmount>/', $xml);
        // Per-rate taxable amount is the ventilated net (75)
        $this->assertMatchesRegularExpression('/<cbc:TaxableAmount[^>]*>75\.00<\/cbc:TaxableAmount>/', $xml);
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
