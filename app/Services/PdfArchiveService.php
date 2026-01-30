<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PdfArchiveService
{
    public const FORMAT_PDFA_1B = 'pdfa-1b';
    public const FORMAT_PDFA_3B = 'pdfa-3b';
    public const FORMAT_PDF = 'pdf';

    public const RETENTION_YEARS = 10;

    protected InvoicePdfService $pdfService;

    public function __construct(InvoicePdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Archive an invoice as PDF/A.
     */
    public function archive(Invoice $invoice, string $format = self::FORMAT_PDFA_1B): array
    {
        if (!$invoice->isFinalized()) {
            throw new \InvalidArgumentException('Seules les factures finalisées peuvent être archivées.');
        }

        // Generate PDF content
        $pdfContent = $this->pdfService->getContent($invoice);

        // Try to convert to PDF/A if Ghostscript is available
        $conversionResult = $this->convertToPdfA($pdfContent, $format);

        $finalContent = $conversionResult['content'];
        $actualFormat = $conversionResult['format'];

        // Calculate checksum
        $checksum = hash('sha256', $finalContent);

        // Generate archive path
        $archivePath = $this->generateArchivePath($invoice);

        // Store the file
        Storage::disk('local')->put($archivePath, $finalContent);

        // Update invoice
        $expiresAt = now()->addYears(self::RETENTION_YEARS);

        $invoice->update([
            'archived_at' => now(),
            'archive_format' => $actualFormat,
            'archive_checksum' => $checksum,
            'archive_path' => $archivePath,
            'archive_expires_at' => $expiresAt,
        ]);

        return [
            'success' => true,
            'format' => $actualFormat,
            'checksum' => $checksum,
            'path' => $archivePath,
            'expires_at' => $expiresAt,
            'pdf_a_converted' => $conversionResult['converted'],
        ];
    }

    /**
     * Archive multiple invoices.
     */
    public function archiveBatch(array $invoiceIds, string $format = self::FORMAT_PDFA_1B): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($invoiceIds as $invoiceId) {
            $invoice = Invoice::find($invoiceId);

            if (!$invoice) {
                $results['failed']++;
                $results['errors'][] = "Facture #{$invoiceId} introuvable";
                continue;
            }

            if ($invoice->archived_at) {
                $results['skipped']++;
                continue;
            }

            if (!$invoice->isFinalized()) {
                $results['skipped']++;
                continue;
            }

            try {
                $this->archive($invoice, $format);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = "Facture {$invoice->number}: {$e->getMessage()}";
            }
        }

        return $results;
    }

    /**
     * Verify the integrity of an archived invoice.
     */
    public function verifyIntegrity(Invoice $invoice): array
    {
        if (!$invoice->archived_at || !$invoice->archive_path) {
            return [
                'valid' => false,
                'error' => 'La facture n\'est pas archivée.',
            ];
        }

        if (!Storage::disk('local')->exists($invoice->archive_path)) {
            return [
                'valid' => false,
                'error' => 'Le fichier d\'archive n\'existe plus.',
            ];
        }

        $content = Storage::disk('local')->get($invoice->archive_path);
        $currentChecksum = hash('sha256', $content);

        if ($currentChecksum !== $invoice->archive_checksum) {
            return [
                'valid' => false,
                'error' => 'Le checksum ne correspond pas. Le fichier a été modifié.',
                'expected' => $invoice->archive_checksum,
                'actual' => $currentChecksum,
            ];
        }

        return [
            'valid' => true,
            'checksum' => $currentChecksum,
            'format' => $invoice->archive_format,
            'archived_at' => $invoice->archived_at,
            'expires_at' => $invoice->archive_expires_at,
        ];
    }

    /**
     * Get archive statistics.
     */
    public function getStatistics(): array
    {
        $totalFinalized = Invoice::whereIn('status', [
            Invoice::STATUS_FINALIZED,
            Invoice::STATUS_SENT,
            Invoice::STATUS_PAID,
        ])->count();

        $totalArchived = Invoice::whereNotNull('archived_at')->count();

        $byFormat = Invoice::whereNotNull('archived_at')
            ->selectRaw('archive_format, COUNT(*) as count')
            ->groupBy('archive_format')
            ->pluck('count', 'archive_format')
            ->toArray();

        $notArchived = Invoice::whereNull('archived_at')
            ->whereIn('status', [
                Invoice::STATUS_FINALIZED,
                Invoice::STATUS_SENT,
                Invoice::STATUS_PAID,
            ])
            ->count();

        $expiringThisYear = Invoice::whereNotNull('archived_at')
            ->whereYear('archive_expires_at', now()->year)
            ->count();

        return [
            'total_finalized' => $totalFinalized,
            'total_archived' => $totalArchived,
            'not_archived' => $notArchived,
            'archive_percentage' => $totalFinalized > 0
                ? round(($totalArchived / $totalFinalized) * 100, 1)
                : 0,
            'by_format' => $byFormat,
            'expiring_this_year' => $expiringThisYear,
        ];
    }

    /**
     * Get archived PDF content.
     */
    public function getArchivedContent(Invoice $invoice): ?string
    {
        if (!$invoice->archive_path || !Storage::disk('local')->exists($invoice->archive_path)) {
            return null;
        }

        return Storage::disk('local')->get($invoice->archive_path);
    }

    /**
     * Check if Ghostscript is available.
     */
    public function isGhostscriptAvailable(): bool
    {
        $result = @exec('which gs 2>/dev/null', $output, $returnCode);
        return $returnCode === 0 && !empty($result);
    }

    /**
     * Convert PDF to PDF/A format.
     */
    protected function convertToPdfA(string $pdfContent, string $format): array
    {
        // Check if Ghostscript is available
        if (!$this->isGhostscriptAvailable()) {
            Log::info('Ghostscript not available, storing as regular PDF');
            return [
                'content' => $pdfContent,
                'format' => self::FORMAT_PDF,
                'converted' => false,
            ];
        }

        try {
            // Create temp files
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $inputFile = $tempDir . '/' . uniqid('pdf_') . '.pdf';
            $outputFile = $tempDir . '/' . uniqid('pdfa_') . '.pdf';

            file_put_contents($inputFile, $pdfContent);

            // Ghostscript PDF/A conversion
            $pdfaVersion = $format === self::FORMAT_PDFA_3B ? 3 : 1;

            $command = sprintf(
                'gs -dPDFA=%d -dBATCH -dNOPAUSE -dNOOUTERSAVE ' .
                '-sColorConversionStrategy=RGB ' .
                '-sDEVICE=pdfwrite ' .
                '-dPDFACompatibilityPolicy=1 ' .
                '-sOutputFile=%s %s 2>&1',
                $pdfaVersion,
                escapeshellarg($outputFile),
                escapeshellarg($inputFile)
            );

            exec($command, $output, $returnCode);

            // Clean up input file
            @unlink($inputFile);

            if ($returnCode === 0 && file_exists($outputFile)) {
                $convertedContent = file_get_contents($outputFile);
                @unlink($outputFile);

                return [
                    'content' => $convertedContent,
                    'format' => $format,
                    'converted' => true,
                ];
            }

            // Conversion failed, fall back to regular PDF
            Log::warning('PDF/A conversion failed', [
                'return_code' => $returnCode,
                'output' => implode("\n", $output),
            ]);

            @unlink($outputFile);

            return [
                'content' => $pdfContent,
                'format' => self::FORMAT_PDF,
                'converted' => false,
            ];
        } catch (\Exception $e) {
            Log::error('PDF/A conversion error', ['error' => $e->getMessage()]);

            return [
                'content' => $pdfContent,
                'format' => self::FORMAT_PDF,
                'converted' => false,
            ];
        }
    }

    /**
     * Generate archive path for an invoice.
     */
    protected function generateArchivePath(Invoice $invoice): string
    {
        $year = $invoice->issued_at?->year ?? now()->year;
        $month = str_pad($invoice->issued_at?->month ?? now()->month, 2, '0', STR_PAD_LEFT);

        // Sanitize invoice number for filename
        $filename = preg_replace('/[^a-zA-Z0-9\-]/', '_', $invoice->number) . '.pdf';

        return "archive/{$year}/{$month}/{$filename}";
    }

    /**
     * Get list of invoices pending archival.
     */
    public function getPendingArchival(int $limit = 50): \Illuminate\Database\Eloquent\Collection
    {
        return Invoice::whereNull('archived_at')
            ->whereIn('status', [
                Invoice::STATUS_FINALIZED,
                Invoice::STATUS_SENT,
                Invoice::STATUS_PAID,
            ])
            ->with('client')
            ->orderBy('issued_at')
            ->limit($limit)
            ->get();
    }
}
