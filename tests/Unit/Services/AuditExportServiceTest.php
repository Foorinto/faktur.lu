<?php

namespace Tests\Unit\Services;

use App\Models\AuditExport;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\AuditExportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AuditExportServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuditExportService $service;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AuditExportService::class);

        // Create business settings
        BusinessSettings::factory()->assujetti()->create();

        // Create a client
        $this->client = Client::factory()->create([
            'name' => 'Test Client SARL',
            'vat_number' => 'LU12345678',
        ]);
    }

    protected function createFinalizedInvoice(array $attributes = []): Invoice
    {
        $invoice = Invoice::factory()->create(array_merge([
            'client_id' => $this->client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'type' => Invoice::TYPE_INVOICE,
            'issued_at' => now(),
            'total_ht' => 1000,
            'total_vat' => 170,
            'total_ttc' => 1170,
        ], $attributes));

        InvoiceItem::factory()->create([
            'invoice_id' => $invoice->id,
            'total_ht' => 1000,
            'total_vat' => 170,
            'total_ttc' => 1170,
            'vat_rate' => 17,
        ]);

        return $invoice;
    }

    public function test_preview_returns_correct_counts(): void
    {
        // Create 3 invoices and 1 credit note
        $this->createFinalizedInvoice(['number' => 'FAC-2026-001']);
        $this->createFinalizedInvoice(['number' => 'FAC-2026-002']);
        $this->createFinalizedInvoice(['number' => 'FAC-2026-003']);
        $this->createFinalizedInvoice([
            'number' => 'AV-2026-001',
            'type' => Invoice::TYPE_CREDIT_NOTE,
            'total_ht' => -500,
            'total_vat' => -85,
            'total_ttc' => -585,
        ]);

        $preview = $this->service->getPreview(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-12-31')
        );

        $this->assertEquals(3, $preview['invoices_count']);
        $this->assertEquals(1, $preview['credit_notes_count']);
    }

    public function test_preview_calculates_totals_correctly(): void
    {
        $this->createFinalizedInvoice([
            'number' => 'FAC-2026-001',
            'total_ht' => 1000,
            'total_vat' => 170,
            'total_ttc' => 1170,
        ]);
        $this->createFinalizedInvoice([
            'number' => 'FAC-2026-002',
            'total_ht' => 2000,
            'total_vat' => 340,
            'total_ttc' => 2340,
        ]);

        $preview = $this->service->getPreview(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-12-31')
        );

        $this->assertEquals(3000, $preview['total_ht']);
        $this->assertEquals(510, $preview['total_vat']);
        $this->assertEquals(3510, $preview['total_ttc']);
    }

    public function test_preview_excludes_credit_notes_when_option_false(): void
    {
        $this->createFinalizedInvoice(['number' => 'FAC-2026-001']);
        $this->createFinalizedInvoice([
            'number' => 'AV-2026-001',
            'type' => Invoice::TYPE_CREDIT_NOTE,
        ]);

        $preview = $this->service->getPreview(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-12-31'),
            ['include_credit_notes' => false]
        );

        $this->assertEquals(1, $preview['invoices_count']);
        $this->assertEquals(0, $preview['credit_notes_count']);
    }

    public function test_sequence_validation_detects_gaps(): void
    {
        $this->createFinalizedInvoice(['number' => 'FAC-2026-001']);
        $this->createFinalizedInvoice(['number' => 'FAC-2026-003']); // Gap: 002 missing

        $preview = $this->service->getPreview(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-12-31')
        );

        $this->assertFalse($preview['sequence_valid']);
        $this->assertContains('Numéro manquant: FAC-2026-002', $preview['sequence_errors']);
    }

    public function test_sequence_validation_passes_with_no_gaps(): void
    {
        $this->createFinalizedInvoice(['number' => 'FAC-2026-001']);
        $this->createFinalizedInvoice(['number' => 'FAC-2026-002']);
        $this->createFinalizedInvoice(['number' => 'FAC-2026-003']);

        $preview = $this->service->getPreview(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-12-31')
        );

        $this->assertTrue($preview['sequence_valid']);
        $this->assertEmpty($preview['sequence_errors']);
    }

    public function test_generates_csv_export(): void
    {
        Storage::fake('local');

        $this->createFinalizedInvoice(['number' => 'FAC-2026-001']);

        $export = AuditExport::create([
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'format' => AuditExport::FORMAT_CSV,
            'options' => ['include_credit_notes' => true],
            'status' => AuditExport::STATUS_PENDING,
        ]);

        $this->service->generate($export);

        $export->refresh();

        $this->assertEquals(AuditExport::STATUS_COMPLETED, $export->status);
        $this->assertNotNull($export->file_path);
        $this->assertStringEndsWith('.csv', $export->file_name);

        Storage::disk('local')->assertExists($export->file_path);

        $content = Storage::disk('local')->get($export->file_path);
        $this->assertStringContainsString('numero;date_emission', $content);
        $this->assertStringContainsString('FAC-2026-001', $content);
    }

    public function test_generates_json_export(): void
    {
        Storage::fake('local');

        $this->createFinalizedInvoice(['number' => 'FAC-2026-001']);

        $export = AuditExport::create([
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'format' => AuditExport::FORMAT_JSON,
            'options' => ['include_credit_notes' => true],
            'status' => AuditExport::STATUS_PENDING,
        ]);

        $this->service->generate($export);

        $export->refresh();

        $this->assertEquals(AuditExport::STATUS_COMPLETED, $export->status);
        $this->assertStringEndsWith('.json', $export->file_name);

        $content = Storage::disk('local')->get($export->file_path);
        $data = json_decode($content, true);

        $this->assertArrayHasKey('export', $data);
        $this->assertArrayHasKey('summary', $data);
        $this->assertArrayHasKey('documents', $data);
        $this->assertEquals(1, $data['summary']['invoices_count']);
    }

    public function test_generates_xml_export(): void
    {
        Storage::fake('local');

        $this->createFinalizedInvoice(['number' => 'FAC-2026-001']);

        $export = AuditExport::create([
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'format' => AuditExport::FORMAT_XML,
            'options' => ['include_credit_notes' => true],
            'status' => AuditExport::STATUS_PENDING,
        ]);

        $this->service->generate($export);

        $export->refresh();

        $this->assertEquals(AuditExport::STATUS_COMPLETED, $export->status);
        $this->assertStringEndsWith('.xml', $export->file_name);

        $content = Storage::disk('local')->get($export->file_path);
        $this->assertStringContainsString('<?xml version="1.0"', $content);
        $this->assertStringContainsString('<AuditFile', $content);
        $this->assertStringContainsString('<InvoiceNo>FAC-2026-001</InvoiceNo>', $content);
    }

    public function test_anonymizes_client_data_when_option_true(): void
    {
        Storage::fake('local');

        $this->createFinalizedInvoice(['number' => 'FAC-2026-001']);

        $export = AuditExport::create([
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'format' => AuditExport::FORMAT_CSV,
            'options' => ['include_credit_notes' => true, 'anonymize' => true],
            'status' => AuditExport::STATUS_PENDING,
        ]);

        $this->service->generate($export);

        $content = Storage::disk('local')->get($export->file_path);

        $this->assertStringNotContainsString('Test Client SARL', $content);
        $this->assertStringNotContainsString('LU12345678', $content);
        $this->assertStringContainsString('Client #', $content);
    }

    public function test_export_marks_as_failed_on_error(): void
    {
        $export = AuditExport::create([
            'period_start' => '2026-01-01',
            'period_end' => '2026-12-31',
            'format' => 'invalid_format',
            'options' => [],
            'status' => AuditExport::STATUS_PENDING,
        ]);

        try {
            $this->service->generate($export);
        } catch (\Exception $e) {
            // Expected
        }

        $export->refresh();

        $this->assertEquals(AuditExport::STATUS_FAILED, $export->status);
        $this->assertNotNull($export->error_message);
    }
}
