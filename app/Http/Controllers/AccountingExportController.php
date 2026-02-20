<?php

namespace App\Http\Controllers;

use App\Helpers\DatabaseHelper;
use App\Models\AccountingExport;
use App\Models\AccountingSetting;
use App\Models\Invoice;
use App\Services\Accounting\AccountingExportService;
use App\Services\InvoicePdfService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use ZipArchive;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountingExportController extends Controller
{
    public function __construct(
        private AccountingExportService $exportService,
    ) {}

    /**
     * Display the accounting export page.
     */
    public function index(): Response
    {
        $user = auth()->user();

        $exports = AccountingExport::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $years = \App\Models\Invoice::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                \App\Models\Invoice::STATUS_FINALIZED,
                \App\Models\Invoice::STATUS_SENT,
                \App\Models\Invoice::STATUS_PAID,
            ])
            ->selectRaw(DatabaseHelper::distinctYear('issued_at'))
            ->whereNotNull('issued_at')
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->values();

        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        $settings = AccountingSetting::getForUser($user);

        return Inertia::render('Exports/Accounting', [
            'exports' => $exports,
            'years' => $years,
            'formats' => AccountingExport::FORMATS,
            'defaultYear' => now()->year,
            'accountingSettings' => $settings,
        ]);
    }

    /**
     * Get preview data for the export.
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'include_credit_notes' => 'boolean',
        ]);

        $preview = $this->exportService->getPreview(
            $request->user(),
            Carbon::parse($request->input('period_start')),
            Carbon::parse($request->input('period_end')),
            ['include_credit_notes' => $request->boolean('include_credit_notes', true)]
        );

        return response()->json($preview);
    }

    /**
     * Create a new export.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'format' => 'required|in:sage_bob,sage_100,generic',
            'include_credit_notes' => 'boolean',
        ]);

        $export = AccountingExport::create([
            'user_id' => $request->user()->id,
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'format' => $validated['format'],
            'options' => [
                'include_credit_notes' => $request->boolean('include_credit_notes', true),
            ],
            'status' => AccountingExport::STATUS_PENDING,
        ]);

        try {
            $this->exportService->generate($export);

            return redirect()
                ->route('exports.accounting.index')
                ->with('success', 'Export comptable généré avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->route('exports.accounting.index')
                ->with('error', 'Erreur lors de la génération: ' . $e->getMessage());
        }
    }

    /**
     * Download an export file.
     */
    public function download(AccountingExport $export): StreamedResponse|RedirectResponse
    {
        if (!$export->isCompleted() || !$export->file_path) {
            return redirect()
                ->route('exports.accounting.index')
                ->with('error', 'Ce fichier n\'est pas disponible.');
        }

        if (!Storage::disk('local')->exists($export->file_path)) {
            return redirect()
                ->route('exports.accounting.index')
                ->with('error', 'Le fichier n\'existe plus sur le serveur.');
        }

        $mimeType = match ($export->format) {
            AccountingExport::FORMAT_SAGE_BOB => 'text/plain',
            default => 'text/csv',
        };

        return Storage::disk('local')->download(
            $export->file_path,
            $export->file_name,
            ['Content-Type' => $mimeType]
        );
    }

    /**
     * Delete an export.
     */
    public function destroy(AccountingExport $export): RedirectResponse
    {
        if ($export->file_path && Storage::disk('local')->exists($export->file_path)) {
            Storage::disk('local')->delete($export->file_path);
        }

        $export->delete();

        return redirect()
            ->route('exports.accounting.index')
            ->with('success', 'Export supprimé.');
    }

    /**
     * Download a PDF archive (ZIP) of all invoices for the period.
     */
    public function pdfArchive(Request $request)
    {
        $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after_or_equal:period_start',
            'include_credit_notes' => 'boolean',
        ]);

        $user = $request->user();
        $periodStart = Carbon::parse($request->input('period_start'));
        $periodEnd = Carbon::parse($request->input('period_end'));
        $includeCreditNotes = $request->boolean('include_credit_notes', true);

        $query = $user->userInvoices()
            ->whereIn('status', [Invoice::STATUS_FINALIZED, Invoice::STATUS_SENT, Invoice::STATUS_PAID])
            ->whereBetween('issued_at', [$periodStart->startOfDay(), $periodEnd->endOfDay()])
            ->orderBy('issued_at')
            ->orderBy('number');

        if (!$includeCreditNotes) {
            $query->where('type', Invoice::TYPE_INVOICE);
        }

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            return redirect()
                ->route('exports.accounting.index')
                ->with('error', 'Aucune facture pour cette période.');
        }

        $tempDir = storage_path('app/temp/' . uniqid('pdf_archive_'));
        mkdir($tempDir, 0755, true);

        $zipPath = $tempDir . '/archive.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            abort(500, 'Impossible de créer l\'archive.');
        }

        $pdfService = app(InvoicePdfService::class);

        foreach ($invoices as $invoice) {
            try {
                $pdfContent = $pdfService->getContent($invoice);
                $zip->addFromString($invoice->number . '.pdf', $pdfContent);
            } catch (\Exception $e) {
                continue;
            }
        }

        $zip->close();

        $period = $periodStart->format('Ymd') . '-' . $periodEnd->format('Ymd');
        $filename = "archive_pdf_{$period}.zip";

        return response()->download($zipPath, $filename)->deleteFileAfterSend(true);
    }
}
