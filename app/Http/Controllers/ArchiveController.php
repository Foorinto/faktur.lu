<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\PdfArchiveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArchiveController extends Controller
{
    public function __construct(
        private PdfArchiveService $archiveService,
    ) {}

    /**
     * Display the archive dashboard.
     */
    public function index(): Response
    {
        $stats = $this->archiveService->getStatistics();
        $pendingInvoices = $this->archiveService->getPendingArchival(100);

        // Get recently archived invoices
        $recentlyArchived = Invoice::whereNotNull('archived_at')
            ->with('client')
            ->orderByDesc('archived_at')
            ->limit(20)
            ->get();

        return Inertia::render('Archive/Index', [
            'stats' => $stats,
            'pendingInvoices' => $pendingInvoices,
            'recentlyArchived' => $recentlyArchived,
            'ghostscriptAvailable' => $this->archiveService->isGhostscriptAvailable(),
            'formats' => [
                PdfArchiveService::FORMAT_PDFA_1B => 'PDF/A-1b (Recommandé)',
                PdfArchiveService::FORMAT_PDFA_3B => 'PDF/A-3b (Avec pièces jointes)',
                PdfArchiveService::FORMAT_PDF => 'PDF Standard',
            ],
        ]);
    }

    /**
     * Archive a single invoice.
     */
    public function archive(Request $request, Invoice $invoice): RedirectResponse
    {
        $format = $request->input('format', PdfArchiveService::FORMAT_PDFA_1B);

        try {
            $result = $this->archiveService->archive($invoice, $format);

            $message = $result['pdf_a_converted']
                ? "Facture archivée au format {$result['format']}."
                : "Facture archivée au format PDF (PDF/A non disponible).";

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Archive multiple invoices.
     */
    public function archiveBatch(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'exists:invoices,id',
            'format' => 'nullable|in:pdfa-1b,pdfa-3b,pdf',
        ]);

        $format = $validated['format'] ?? PdfArchiveService::FORMAT_PDFA_1B;

        $result = $this->archiveService->archiveBatch($validated['invoice_ids'], $format);

        $message = "{$result['success']} facture(s) archivée(s)";
        if ($result['skipped'] > 0) {
            $message .= ", {$result['skipped']} ignorée(s)";
        }
        if ($result['failed'] > 0) {
            $message .= ", {$result['failed']} en erreur";
        }

        if ($result['failed'] > 0) {
            return back()->with('warning', $message);
        }

        return back()->with('success', $message);
    }

    /**
     * Download archived PDF.
     */
    public function download(Invoice $invoice): StreamedResponse|RedirectResponse
    {
        if (!$invoice->isArchived()) {
            return back()->with('error', 'Cette facture n\'est pas archivée.');
        }

        $content = $this->archiveService->getArchivedContent($invoice);

        if (!$content) {
            return back()->with('error', 'Le fichier d\'archive est introuvable.');
        }

        $filename = $invoice->number . '_archive.pdf';

        return response()->streamDownload(function () use ($content) {
            echo $content;
        }, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Verify archive integrity.
     */
    public function verify(Invoice $invoice): JsonResponse
    {
        $result = $this->archiveService->verifyIntegrity($invoice);

        return response()->json($result);
    }

    /**
     * Get archive info for an invoice (API).
     */
    public function info(Invoice $invoice): JsonResponse
    {
        if (!$invoice->isArchived()) {
            return response()->json([
                'archived' => false,
            ]);
        }

        $verification = $this->archiveService->verifyIntegrity($invoice);

        return response()->json([
            'archived' => true,
            'archived_at' => $invoice->archived_at?->toIso8601String(),
            'format' => $invoice->archive_format,
            'checksum' => $invoice->archive_checksum,
            'expires_at' => $invoice->archive_expires_at?->toIso8601String(),
            'days_until_expiry' => $invoice->archive_expires_at?->diffInDays(now()),
            'integrity_valid' => $verification['valid'],
        ]);
    }
}
