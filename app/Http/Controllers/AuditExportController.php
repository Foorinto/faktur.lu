<?php

namespace App\Http\Controllers;

use App\Models\AuditExport;
use App\Services\AuditExportService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditExportController extends Controller
{
    public function __construct(
        private AuditExportService $exportService,
    ) {}

    /**
     * Display the audit export page.
     */
    public function index(): Response
    {
        $exports = AuditExport::query()
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        // Get available years from invoices
        $years = \App\Models\Invoice::query()
            ->whereIn('status', [
                \App\Models\Invoice::STATUS_FINALIZED,
                \App\Models\Invoice::STATUS_SENT,
                \App\Models\Invoice::STATUS_PAID,
            ])
            ->selectRaw('DISTINCT strftime("%Y", issued_at) as year')
            ->whereNotNull('issued_at')
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->values();

        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        return Inertia::render('Exports/Audit', [
            'exports' => $exports,
            'years' => $years,
            'formats' => AuditExport::FORMATS,
            'defaultYear' => now()->year,
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

        $periodStart = Carbon::parse($request->input('period_start'));
        $periodEnd = Carbon::parse($request->input('period_end'));

        $preview = $this->exportService->getPreview($periodStart, $periodEnd, [
            'include_credit_notes' => $request->boolean('include_credit_notes', true),
        ]);

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
            'format' => 'required|in:csv,json,xml',
            'include_credit_notes' => 'boolean',
            'anonymize' => 'boolean',
        ]);

        $export = AuditExport::create([
            'user_id' => $request->user()?->id,
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'format' => $validated['format'],
            'options' => [
                'include_credit_notes' => $request->boolean('include_credit_notes', true),
                'anonymize' => $request->boolean('anonymize', false),
            ],
            'status' => AuditExport::STATUS_PENDING,
        ]);

        try {
            $this->exportService->generate($export);

            return redirect()
                ->route('exports.audit.index')
                ->with('success', 'Export généré avec succès.');
        } catch (\Exception $e) {
            return redirect()
                ->route('exports.audit.index')
                ->with('error', 'Erreur lors de la génération: ' . $e->getMessage());
        }
    }

    /**
     * Download an export file.
     */
    public function download(AuditExport $export): StreamedResponse|RedirectResponse
    {
        if (!$export->isCompleted() || !$export->file_path) {
            return redirect()
                ->route('exports.audit.index')
                ->with('error', 'Ce fichier n\'est pas disponible.');
        }

        if (!Storage::disk('local')->exists($export->file_path)) {
            return redirect()
                ->route('exports.audit.index')
                ->with('error', 'Le fichier n\'existe plus sur le serveur.');
        }

        $mimeType = match ($export->format) {
            AuditExport::FORMAT_CSV => 'text/csv',
            AuditExport::FORMAT_JSON => 'application/json',
            AuditExport::FORMAT_XML => 'application/xml',
            default => 'application/octet-stream',
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
    public function destroy(AuditExport $export): RedirectResponse
    {
        // Delete the file if it exists
        if ($export->file_path && Storage::disk('local')->exists($export->file_path)) {
            Storage::disk('local')->delete($export->file_path);
        }

        $export->delete();

        return redirect()
            ->route('exports.audit.index')
            ->with('success', 'Export supprimé.');
    }
}
