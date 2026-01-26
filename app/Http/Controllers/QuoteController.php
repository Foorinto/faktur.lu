<?php

namespace App\Http\Controllers;

use App\Actions\ConvertQuoteToInvoiceAction;
use App\Http\Requests\Api\V1\StoreQuoteRequest;
use App\Http\Requests\Api\V1\UpdateQuoteRequest;
use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Services\QuotePdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;

class QuoteController extends Controller
{
    /**
     * Display a listing of quotes.
     */
    public function index(Request $request): Response
    {
        $query = Quote::query()
            ->with('client')
            ->withCount('items');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter by year
        if ($request->filled('year')) {
            $query->whereYear('created_at', $request->input('year'));
        }

        // Filter by client
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->input('client_id'));
        }

        $quotes = $query
            ->orderByRaw("CASE WHEN status = 'draft' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        // Get available years for filter
        $years = Quote::selectRaw('DISTINCT strftime("%Y", created_at) as year')
            ->whereNotNull('created_at')
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->values();

        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        return Inertia::render('Quotes/Index', [
            'quotes' => $quotes,
            'filters' => [
                'status' => $request->input('status'),
                'year' => $request->input('year'),
                'client_id' => $request->input('client_id'),
            ],
            'statuses' => [
                ['value' => 'draft', 'label' => 'Brouillon'],
                ['value' => 'sent', 'label' => 'Envoyé'],
                ['value' => 'accepted', 'label' => 'Accepté'],
                ['value' => 'declined', 'label' => 'Refusé'],
                ['value' => 'expired', 'label' => 'Expiré'],
                ['value' => 'converted', 'label' => 'Converti'],
            ],
            'years' => $years,
            'clients' => Client::orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Show the form for creating a new quote.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Quotes/Create', [
            'clients' => Client::orderBy('name')->get(['id', 'name', 'currency']),
            'vatRates' => $this->getVatRates(),
            'units' => $this->getUnits(),
            'defaultClientId' => $request->input('client_id'),
            'isVatExempt' => $this->isVatExempt(),
        ]);
    }

    /**
     * Store a newly created quote.
     */
    public function store(StoreQuoteRequest $request): RedirectResponse
    {
        $client = Client::findOrFail($request->validated('client_id'));

        $quote = Quote::create([
            'client_id' => $client->id,
            'currency' => $request->validated('currency') ?? $client->currency,
            'valid_until' => $request->validated('valid_until'),
            'notes' => $request->validated('notes'),
            'status' => Quote::STATUS_DRAFT,
        ]);

        // Create items if provided
        if ($request->has('items')) {
            foreach ($request->validated('items') as $index => $itemData) {
                QuoteItem::create([
                    'quote_id' => $quote->id,
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
            ->route('quotes.edit', $quote)
            ->with('success', 'Brouillon de devis créé.');
    }

    /**
     * Display the specified quote.
     */
    public function show(Quote $quote): Response
    {
        $quote->load(['client', 'items', 'convertedInvoice']);

        return Inertia::render('Quotes/Show', [
            'quote' => $quote,
        ]);
    }

    /**
     * Show the form for editing the specified quote.
     */
    public function edit(Quote $quote): Response|RedirectResponse
    {
        if (!$quote->canEdit()) {
            return redirect()->route('quotes.show', $quote);
        }

        $quote->load(['client', 'items']);

        return Inertia::render('Quotes/Edit', [
            'quote' => $quote,
            'clients' => Client::orderBy('name')->get(['id', 'name', 'currency']),
            'vatRates' => $this->getVatRates(),
            'units' => $this->getUnits(),
            'isVatExempt' => $this->isVatExempt(),
        ]);
    }

    /**
     * Update the specified quote.
     */
    public function update(UpdateQuoteRequest $request, Quote $quote): RedirectResponse
    {
        $quote->update($request->validated());

        return back()->with('success', 'Devis mis à jour.');
    }

    /**
     * Remove the specified quote.
     */
    public function destroy(Quote $quote): RedirectResponse
    {
        try {
            $quote->delete();
            return redirect()
                ->route('quotes.index')
                ->with('success', 'Devis supprimé.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Mark the quote as sent.
     */
    public function markAsSent(Quote $quote): RedirectResponse
    {
        if (!$quote->canMarkAsSent()) {
            return back()->with('error', 'Ce devis ne peut pas être marqué comme envoyé.');
        }

        Quote::withoutEvents(function () use ($quote) {
            $quote->update([
                'status' => Quote::STATUS_SENT,
                'sent_at' => now(),
            ]);
        });

        return back()->with('success', 'Devis marqué comme envoyé.');
    }

    /**
     * Mark the quote as accepted.
     */
    public function markAsAccepted(Quote $quote): RedirectResponse
    {
        if (!$quote->canAccept()) {
            return back()->with('error', 'Ce devis ne peut pas être accepté.');
        }

        Quote::withoutEvents(function () use ($quote) {
            $quote->update([
                'status' => Quote::STATUS_ACCEPTED,
                'accepted_at' => now(),
            ]);
        });

        return redirect()
            ->route('quotes.show', $quote)
            ->with('success', 'Devis marqué comme accepté. Vous pouvez maintenant le convertir en facture.');
    }

    /**
     * Mark the quote as declined.
     */
    public function markAsDeclined(Quote $quote): RedirectResponse
    {
        if (!$quote->canDecline()) {
            return back()->with('error', 'Ce devis ne peut pas être refusé.');
        }

        Quote::withoutEvents(function () use ($quote) {
            $quote->update([
                'status' => Quote::STATUS_DECLINED,
                'declined_at' => now(),
            ]);
        });

        return redirect()
            ->route('quotes.show', $quote)
            ->with('success', 'Devis marqué comme refusé.');
    }

    /**
     * Convert the quote to an invoice.
     */
    public function convertToInvoice(Quote $quote, ConvertQuoteToInvoiceAction $action): RedirectResponse
    {
        try {
            $invoice = $action->execute($quote);
            return redirect()
                ->route('invoices.edit', $invoice)
                ->with('success', 'Devis converti en facture. Vous pouvez maintenant finaliser la facture.');
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Download the quote as PDF.
     */
    public function downloadPdf(Quote $quote, QuotePdfService $pdfService): HttpResponse
    {
        return $pdfService->download($quote);
    }

    /**
     * Stream the quote as PDF.
     */
    public function streamPdf(Quote $quote, QuotePdfService $pdfService): HttpResponse
    {
        return $pdfService->stream($quote);
    }

    /**
     * Preview the quote PDF as HTML (Inertia page).
     */
    public function previewPdf(Quote $quote, QuotePdfService $pdfService): Response
    {
        $html = $pdfService->preview($quote);

        return Inertia::render('Quotes/PdfPreview', [
            'quote' => $quote,
            'htmlContent' => $html,
        ]);
    }

    /**
     * Get preview HTML for quote (API endpoint for modal).
     */
    public function previewHtml(Quote $quote, QuotePdfService $pdfService): \Illuminate\Http\JsonResponse
    {
        $html = $pdfService->preview($quote);
        return response()->json(['html' => $html]);
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

    /**
     * Check if business is VAT exempt.
     */
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
        foreach (QuoteItem::getUnits() as $value => $label) {
            $units[] = ['value' => $value, 'label' => $label];
        }
        return $units;
    }
}
