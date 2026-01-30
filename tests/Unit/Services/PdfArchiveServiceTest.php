<?php

namespace Tests\Unit\Services;

use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\PdfArchiveService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PdfArchiveServiceTest extends TestCase
{
    use RefreshDatabase;

    private PdfArchiveService $service;
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        // Create business settings
        BusinessSettings::factory()->assujetti()->create();

        // Create a finalized invoice
        $client = Client::factory()->create();
        $this->invoice = Invoice::factory()->create([
            'client_id' => $client->id,
            'status' => Invoice::STATUS_FINALIZED,
            'number' => 'FAC-2026-001',
            'issued_at' => now(),
            'total_ht' => 1000,
            'total_vat' => 170,
            'total_ttc' => 1170,
        ]);

        InvoiceItem::factory()->create([
            'invoice_id' => $this->invoice->id,
            'total_ht' => 1000,
            'total_vat' => 170,
            'total_ttc' => 1170,
        ]);

        $this->service = app(PdfArchiveService::class);
    }

    public function test_archive_creates_file(): void
    {
        $result = $this->service->archive($this->invoice);

        $this->assertTrue($result['success']);
        $this->assertNotNull($result['checksum']);
        $this->assertNotNull($result['path']);

        Storage::disk('local')->assertExists($result['path']);
    }

    public function test_archive_updates_invoice_fields(): void
    {
        $this->service->archive($this->invoice);

        $this->invoice->refresh();

        $this->assertNotNull($this->invoice->archived_at);
        $this->assertNotNull($this->invoice->archive_format);
        $this->assertNotNull($this->invoice->archive_checksum);
        $this->assertNotNull($this->invoice->archive_path);
        $this->assertNotNull($this->invoice->archive_expires_at);
    }

    public function test_archive_sets_expiry_to_10_years(): void
    {
        $this->service->archive($this->invoice);

        $this->invoice->refresh();

        $expectedExpiry = now()->addYears(10);
        $this->assertTrue($this->invoice->archive_expires_at->isSameDay($expectedExpiry));
    }

    public function test_archive_calculates_sha256_checksum(): void
    {
        $result = $this->service->archive($this->invoice);

        // SHA256 produces 64 character hex string
        $this->assertEquals(64, strlen($result['checksum']));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result['checksum']);
    }

    public function test_cannot_archive_draft_invoice(): void
    {
        $draftInvoice = Invoice::factory()->create([
            'status' => Invoice::STATUS_DRAFT,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->archive($draftInvoice);
    }

    public function test_verify_integrity_passes_for_valid_archive(): void
    {
        $this->service->archive($this->invoice);
        $this->invoice->refresh();

        $result = $this->service->verifyIntegrity($this->invoice);

        $this->assertTrue($result['valid']);
        $this->assertEquals($this->invoice->archive_checksum, $result['checksum']);
    }

    public function test_verify_integrity_fails_when_not_archived(): void
    {
        $result = $this->service->verifyIntegrity($this->invoice);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('pas archivée', $result['error']);
    }

    public function test_verify_integrity_fails_when_file_missing(): void
    {
        $this->service->archive($this->invoice);
        $this->invoice->refresh();

        // Delete the file
        Storage::disk('local')->delete($this->invoice->archive_path);

        $result = $this->service->verifyIntegrity($this->invoice);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('n\'existe plus', $result['error']);
    }

    public function test_verify_integrity_fails_when_checksum_mismatch(): void
    {
        $this->service->archive($this->invoice);
        $this->invoice->refresh();

        // Modify the file
        Storage::disk('local')->put($this->invoice->archive_path, 'modified content');

        $result = $this->service->verifyIntegrity($this->invoice);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('checksum ne correspond pas', $result['error']);
    }

    public function test_batch_archive_multiple_invoices(): void
    {
        $invoice2 = Invoice::factory()->create([
            'status' => Invoice::STATUS_FINALIZED,
            'number' => 'FAC-2026-002',
            'issued_at' => now(),
        ]);
        InvoiceItem::factory()->create(['invoice_id' => $invoice2->id]);

        $result = $this->service->archiveBatch([
            $this->invoice->id,
            $invoice2->id,
        ]);

        $this->assertEquals(2, $result['success']);
        $this->assertEquals(0, $result['failed']);
        $this->assertEquals(0, $result['skipped']);
    }

    public function test_batch_archive_skips_already_archived(): void
    {
        // Archive first
        $this->service->archive($this->invoice);

        // Try batch with same invoice
        $result = $this->service->archiveBatch([$this->invoice->id]);

        $this->assertEquals(0, $result['success']);
        $this->assertEquals(1, $result['skipped']);
    }

    public function test_get_statistics_returns_correct_counts(): void
    {
        // Archive one invoice
        $this->service->archive($this->invoice);

        // Create another finalized but not archived
        $invoice2 = Invoice::factory()->create([
            'status' => Invoice::STATUS_FINALIZED,
        ]);

        $stats = $this->service->getStatistics();

        $this->assertEquals(2, $stats['total_finalized']);
        $this->assertEquals(1, $stats['total_archived']);
        $this->assertEquals(1, $stats['not_archived']);
        $this->assertEquals(50.0, $stats['archive_percentage']);
    }

    public function test_get_pending_archival_returns_unarchived_invoices(): void
    {
        // invoice is finalized but not archived
        $pending = $this->service->getPendingArchival();

        $this->assertCount(1, $pending);
        $this->assertEquals($this->invoice->id, $pending->first()->id);
    }

    public function test_get_archived_content_returns_file_content(): void
    {
        $this->service->archive($this->invoice);
        $this->invoice->refresh();

        $content = $this->service->getArchivedContent($this->invoice);

        $this->assertNotNull($content);
        // PDF starts with %PDF
        $this->assertStringStartsWith('%PDF', $content);
    }

    public function test_archive_path_uses_correct_structure(): void
    {
        $result = $this->service->archive($this->invoice);

        $year = now()->year;
        $month = str_pad(now()->month, 2, '0', STR_PAD_LEFT);

        $this->assertStringContainsString("archive/{$year}/{$month}/", $result['path']);
        $this->assertStringEndsWith('.pdf', $result['path']);
    }
}
