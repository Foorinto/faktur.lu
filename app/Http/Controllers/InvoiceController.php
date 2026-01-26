<?php

namespace App\Http\Controllers;

use App\Actions\CreateCreditNoteAction;
use App\Actions\FinalizeInvoiceAction;
use App\Exceptions\ImmutableInvoiceException;
use App\Http\Requests\Api\V1\StoreInvoiceRequest;
use App\Http\Requests\Api\V1\UpdateInvoiceRequest;
use App\Mail\InvoiceMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\InvoicePdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    /**
     * Display a listing of invoices.
     */
    public function index(Request $request): Response
    {
        $query = Invoice::query()
            ->with('client')
            ->withCount('items');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by year
        if ($request->filled('year')) {
            $query->whereYear('issued_at', $request->input('year'));
        }

        // Filter by client
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->input('client_id'));
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        $invoices = $query
            ->orderByRaw("CASE WHEN status = 'draft' THEN 0 ELSE 1 END")
            ->orderByDesc('issued_at')
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        // Get available years for filter
        $years = Invoice::selectRaw('DISTINCT strftime("%Y", issued_at) as year')
            ->whereNotNull('issued_at')
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->values();

        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
            'filters' => [
                'status' => $request->input('status'),
                'year' => $request->input('year'),
                'client_id' => $request->input('client_id'),
                'type' => $request->input('type'),
            ],
            'statuses' => [
                ['value' => 'draft', 'label' => 'Brouillon'],
                ['value' => 'finalized', 'label' => 'Finalisée'],
                ['value' => 'sent', 'label' => 'Envoyée'],
                ['value' => 'paid', 'label' => 'Payée'],
                ['value' => 'cancelled', 'label' => 'Annulée'],
            ],
            'years' => $years,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Show the form for creating a new invoice.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Invoices/Create', [
            'clients' => Client::orderBy('name')->get(['id', 'name', 'currency']),
            'vatRates' => $this->getVatRates(),
            'units' => $this->getUnits(),
            'defaultClientId' => $request->input('client_id'),
            'isVatExempt' => $this->isVatExempt(),
        ]);
    }

    /**
     * Store a newly created invoice.
     */
    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $client = Client::findOrFail($request->validated('client_id'));

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'currency' => $request->validated('currency') ?? $client->currency,
            'due_at' => $request->validated('due_at'),
            'notes' => $request->validated('notes'),
            'status' => Invoice::STATUS_DRAFT,
        ]);

        // Create items if provided
        if ($request->has('items')) {
            foreach ($request->validated('items') as $index => $itemData) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'title' => $itemData['title'],
                    'description' => $itemData['description'] ?? null,
                    'quantity' => $itemData['quantity'],
                    'unit' => $itemData['unit'] ?? null,
                    'unit_price' => $itemData['unit_price'],
                    'vat_rate' => $itemData['vat_rate'],
                    'sort_order' => $index,
                ]);
            }
        }

        return redirect()
            ->route('invoices.edit', $invoice)
            ->with('success', 'Brouillon de facture créé.');
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice): Response
    {
        $invoice->load(['client', 'items', 'originalInvoice', 'creditNote']);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Show the form for editing the specified invoice.
     */
    public function edit(Invoice $invoice): Response|RedirectResponse
    {
        if ($invoice->isImmutable()) {
            return redirect()->route('invoices.show', $invoice);
        }

        $invoice->load(['client', 'items']);

        $settings = \App\Models\BusinessSettings::getInstance();

        return Inertia::render('Invoices/Edit', [
            'invoice' => $invoice,
            'clients' => Client::orderBy('name')->get(['id', 'name', 'currency']),
            'vatRates' => $this->getVatRates(),
            'units' => $this->getUnits(),
            'isVatExempt' => $this->isVatExempt(),
            'defaultInvoiceFooter' => $settings?->default_invoice_footer ?? 'Merci pour votre confiance !',
            'vatMentionOptions' => \App\Models\BusinessSettings::getVatMentionOptions(),
            'defaultVatMention' => $settings?->default_vat_mention ?? ($this->isVatExempt() ? 'franchise' : 'none'),
            'defaultCustomVatMention' => $settings?->default_custom_vat_mention,
        ]);
    }

    /**
     * Update the specified invoice.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        $invoice->update($request->validated());

        return back()->with('success', 'Facture mise à jour.');
    }

    /**
     * Remove the specified invoice.
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        try {
            $invoice->delete();
            return redirect()
                ->route('invoices.index')
                ->with('success', 'Brouillon supprimé.');
        } catch (ImmutableInvoiceException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Finalize the invoice.
     */
    public function finalize(Invoice $invoice, FinalizeInvoiceAction $action): RedirectResponse
    {
        try {
            $action->execute($invoice);
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', "Facture n° {$invoice->number} finalisée avec succès.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark the invoice as sent.
     */
    public function markAsSent(Invoice $invoice): RedirectResponse
    {
        if (!$invoice->isFinalized() || $invoice->status === Invoice::STATUS_SENT) {
            return back()->with('error', 'Cette action n\'est pas autorisée.');
        }

        $invoice->update([
            'status' => Invoice::STATUS_SENT,
            'sent_at' => now(),
        ]);

        return back()->with('success', 'Facture marquée comme envoyée.');
    }

    /**
     * Mark the invoice as paid.
     */
    public function markAsPaid(Invoice $invoice): RedirectResponse
    {
        if (!$invoice->isFinalized() || $invoice->isPaid()) {
            return back()->with('error', 'Cette action n\'est pas autorisée.');
        }

        $invoice->update([
            'status' => Invoice::STATUS_PAID,
            'paid_at' => now(),
        ]);

        return back()->with('success', 'Facture marquée comme payée.');
    }

    /**
     * Create a credit note for the invoice.
     */
    public function createCreditNote(Invoice $invoice, CreateCreditNoteAction $action): RedirectResponse
    {
        try {
            $creditNote = $action->execute($invoice);
            return redirect()
                ->route('invoices.edit', $creditNote)
                ->with('success', 'Note de crédit créée. Vérifiez et finalisez-la.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Download the invoice as PDF.
     */
    public function downloadPdf(Invoice $invoice, InvoicePdfService $pdfService): HttpResponse
    {
        try {
            return $pdfService->download($invoice);
        } catch (\InvalidArgumentException $e) {
            abort(400, $e->getMessage());
        }
    }

    /**
     * Stream the invoice as PDF.
     */
    public function streamPdf(Invoice $invoice, InvoicePdfService $pdfService): HttpResponse
    {
        try {
            return $pdfService->stream($invoice);
        } catch (\InvalidArgumentException $e) {
            abort(400, $e->getMessage());
        }
    }

    /**
     * Preview the invoice PDF as HTML (Inertia page).
     */
    public function previewPdf(Invoice $invoice, InvoicePdfService $pdfService): Response
    {
        try {
            $html = $pdfService->preview($invoice);

            return Inertia::render('Invoices/PdfPreview', [
                'invoice' => $invoice,
                'htmlContent' => $html,
            ]);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get preview HTML for finalized invoice (API endpoint for modal).
     */
    public function previewHtml(Invoice $invoice, InvoicePdfService $pdfService): \Illuminate\Http\JsonResponse
    {
        try {
            $html = $pdfService->preview($invoice);
            return response()->json(['html' => $html]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get live preview HTML for draft invoice (API endpoint for iframe).
     */
    public function previewDraft(Invoice $invoice, InvoicePdfService $pdfService): \Illuminate\Http\JsonResponse
    {
        try {
            $html = $pdfService->previewDraft($invoice);
            return response()->json(['html' => $html]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Stream draft invoice as PDF.
     */
    public function streamDraftPdf(Invoice $invoice, InvoicePdfService $pdfService): HttpResponse
    {
        if ($invoice->isFinalized()) {
            return $this->streamPdf($invoice, $pdfService);
        }

        return $pdfService->streamDraft($invoice);
    }

    /**
     * Send the invoice by email.
     */
    public function sendEmail(Request $request, Invoice $invoice): RedirectResponse
    {
        if (!$invoice->isFinalized()) {
            return back()->with('error', 'Impossible d\'envoyer une facture non finalisée.');
        }

        $request->validate([
            'email' => 'required|email',
            'message' => 'nullable|string|max:2000',
        ]);

        try {
            Mail::to($request->input('email'))
                ->send(new InvoiceMail($invoice, $request->input('message')));

            // Mark as sent if not already
            if ($invoice->status === Invoice::STATUS_FINALIZED) {
                $invoice->update([
                    'status' => Invoice::STATUS_SENT,
                    'sent_at' => now(),
                ]);
            }

            return back()->with('success', 'Facture envoyée par email avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de l\'envoi: ' . $e->getMessage());
        }
    }

    /**
     * Get available VAT rates.
     */
    private function getVatRates(): array
    {
        $settings = \App\Models\BusinessSettings::getInstance();
        $isVatExempt = $settings?->isVatExempt() ?? true;

        if ($isVatExempt) {
            return [
                ['value' => 0, 'label' => '0% (Exonéré)'],
            ];
        }

        return [
            ['value' => 17, 'label' => '17% (Standard)'],
            ['value' => 14, 'label' => '14% (Intermédiaire)'],
            ['value' => 8, 'label' => '8% (Réduit)'],
            ['value' => 3, 'label' => '3% (Super-réduit)'],
            ['value' => 0, 'label' => '0% (Exonéré)'],
        ];
    }

    private function isVatExempt(): bool
    {
        $settings = \App\Models\BusinessSettings::getInstance();
        return $settings?->isVatExempt() ?? true;
    }

    /**
     * Get available units.
     */
    private function getUnits(): array
    {
        $units = [];
        foreach (InvoiceItem::getUnits() as $value => $label) {
            $units[] = ['value' => $value, 'label' => $label];
        }
        return $units;
    }
}
