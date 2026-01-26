<?php

namespace Tests\Unit\Services;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePdfServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InvoicePdfService $service;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InvoicePdfService();

        // Create business settings
        BusinessSettings::factory()->create([
            'vat_regime' => 'assujetti',
            'vat_number' => 'LU12345678',
        ]);

        // Create a finalized invoice
        $client = Client::factory()->create();
        $this->invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'number' => '2026-001',
            'issued_at' => now(),
            'due_at' => now()->addDays(30),
            'seller_snapshot' => [
                'company_name' => 'Test Company',
                'matricule' => '12345678901',
                'vat_regime' => 'assujetti',
                'vat_number' => 'LU12345678',
                'address' => '12 Rue Test',
                'postal_code' => 'L-1234',
                'city' => 'Luxembourg',
                'iban' => 'LU12 3456 7890 1234',
                'bic' => 'BGLLLULL',
            ],
            'buyer_snapshot' => [
                'company_name' => 'Client Company',
                'address' => '45 Avenue Client',
                'postal_code' => 'L-2000',
                'city' => 'Luxembourg',
                'vat_number' => 'LU87654321',
            ],
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $this->invoice->id,
            'title' => 'Development services',
            'quantity' => 10,
            'unit_price' => 100,
            'vat_rate' => 17,
        ]);
    }

    public function test_prepare_data_uses_snapshot_not_relations(): void
    {
        $data = $this->service->prepareData($this->invoice);

        // Should use snapshot data
        $this->assertEquals('Test Company', $data['seller']['company_name']);
        $this->assertEquals('Client Company', $data['buyer']['company_name']);
        $this->assertEquals('12345678901', $data['seller']['matricule']);
    }

    public function test_prepare_data_detects_vat_exempt_franchise(): void
    {
        // Create a franchise invoice from scratch
        $client = Client::factory()->create();
        $franchiseInvoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'number' => '2026-002',
            'issued_at' => now(),
            'seller_snapshot' => [
                'company_name' => 'Franchise Company',
                'matricule' => '12345678901',
                'vat_regime' => 'franchise',
                'address' => '12 Rue Test',
                'postal_code' => 'L-1234',
                'city' => 'Luxembourg',
            ],
            'buyer_snapshot' => [
                'company_name' => 'Client',
                'address' => 'Address',
                'postal_code' => 'L-2000',
                'city' => 'Luxembourg',
            ],
        ]);

        $data = $this->service->prepareData($franchiseInvoice);

        $this->assertTrue($data['isVatExempt']);
    }

    public function test_prepare_data_not_vat_exempt_for_assujetti(): void
    {
        $data = $this->service->prepareData($this->invoice);

        $this->assertFalse($data['isVatExempt']);
    }

    public function test_prepare_data_includes_vat_summary(): void
    {
        $data = $this->service->prepareData($this->invoice);

        $this->assertArrayHasKey('vatSummary', $data);
        $this->assertIsArray($data['vatSummary']);
    }

    public function test_prepare_data_generates_payment_reference(): void
    {
        $data = $this->service->prepareData($this->invoice);

        $this->assertArrayHasKey('paymentReference', $data);
        $this->assertStringContainsString('2026-001', $data['paymentReference']);
    }

    public function test_cannot_generate_pdf_for_draft_invoice(): void
    {
        $draftInvoice = Invoice::factory()->create([
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('non finalisée');

        $this->service->preview($draftInvoice);
    }

    public function test_preview_returns_html_string(): void
    {
        $html = $this->service->preview($this->invoice);

        $this->assertIsString($html);
        $this->assertStringContainsString('<!DOCTYPE html>', $html);
        $this->assertStringContainsString('2026-001', $html);
    }

    public function test_preview_contains_seller_info(): void
    {
        $html = $this->service->preview($this->invoice);

        $this->assertStringContainsString('Test Company', $html);
        $this->assertStringContainsString('12345678901', $html);
        $this->assertStringContainsString('LU12345678', $html);
    }

    public function test_preview_contains_buyer_info(): void
    {
        $html = $this->service->preview($this->invoice);

        $this->assertStringContainsString('Client Company', $html);
        $this->assertStringContainsString('LU87654321', $html);
    }

    public function test_preview_contains_items(): void
    {
        $html = $this->service->preview($this->invoice);

        $this->assertStringContainsString('Development services', $html);
    }

    public function test_preview_contains_legal_notices(): void
    {
        $html = $this->service->preview($this->invoice);

        $this->assertStringContainsString('Pénalité de retard', $html);
        $this->assertStringContainsString('Indemnité forfaitaire', $html);
    }

    public function test_preview_shows_franchise_notice_when_exempt(): void
    {
        // Create a franchise invoice from scratch
        $client = Client::factory()->create();
        $franchiseInvoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'number' => '2026-003',
            'issued_at' => now(),
            'seller_snapshot' => [
                'company_name' => 'Franchise Company',
                'matricule' => '12345678901',
                'vat_regime' => 'franchise',
                'address' => '12 Rue Test',
                'postal_code' => 'L-1234',
                'city' => 'Luxembourg',
            ],
            'buyer_snapshot' => [
                'company_name' => 'Client',
                'address' => 'Address',
                'postal_code' => 'L-2000',
                'city' => 'Luxembourg',
            ],
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $franchiseInvoice->id,
        ]);

        $html = $this->service->preview($franchiseInvoice);

        $this->assertStringContainsString('TVA non applicable', $html);
        $this->assertStringContainsString('art. 57', $html);
    }

    public function test_get_content_returns_pdf_binary(): void
    {
        $content = $this->service->getContent($this->invoice);

        $this->assertIsString($content);
        // PDF files start with %PDF
        $this->assertStringStartsWith('%PDF', $content);
    }

    public function test_credit_note_shows_credit_note_label(): void
    {
        // Create a credit note from scratch
        $client = Client::factory()->create();
        $creditNote = Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'number' => '2026-CN-001',
            'type' => Invoice::TYPE_CREDIT_NOTE,
            'issued_at' => now(),
            'seller_snapshot' => [
                'company_name' => 'Test Company',
                'matricule' => '12345678901',
                'vat_regime' => 'assujetti',
                'vat_number' => 'LU12345678',
                'address' => '12 Rue Test',
                'postal_code' => 'L-1234',
                'city' => 'Luxembourg',
            ],
            'buyer_snapshot' => [
                'company_name' => 'Client',
                'address' => 'Address',
                'postal_code' => 'L-2000',
                'city' => 'Luxembourg',
            ],
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $creditNote->id,
        ]);

        $html = $this->service->preview($creditNote);

        $this->assertStringContainsString('Avoir', $html);
    }
}
