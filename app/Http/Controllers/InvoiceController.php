<?php

namespace App\Http\Controllers;

use App\Actions\CreateCreditNoteAction;
use App\Helpers\DatabaseHelper;
use App\Actions\FinalizeInvoiceAction;
use App\Exceptions\ImmutableInvoiceException;
use App\Http\Requests\Api\V1\StoreInvoiceRequest;
use App\Http\Requests\Api\V1\UpdateInvoiceRequest;
use App\Jobs\SendPeppolInvoiceJob;
use App\Mail\InvoiceMail;
use App\Models\BusinessSettings;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PeppolTransmission;
use App\Services\FacturXService;
use App\Services\InvoicePdfService;
use App\Services\Peppol\PeppolAccessPointInterface;
use App\Services\VatCalculationService;
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
        $years = Invoice::selectRaw(DatabaseHelper::distinctYear('issued_at'))
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
                ['value' => 'draft', 'label' => __('app.draft')],
                ['value' => 'finalized', 'label' => __('app.finalized')],
                ['value' => 'sent', 'label' => __('app.sent')],
                ['value' => 'paid', 'label' => __('app.paid')],
                ['value' => 'cancelled', 'label' => __('app.cancelled')],
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
        $clients = Client::orderBy('name')->get(['id', 'name', 'currency', 'country_code', 'type', 'vat_number', 'default_vat_rate']);

        // Add VAT scenario to each client
        $vatService = app(VatCalculationService::class);
        $clientsWithScenario = $clients->map(function ($client) use ($vatService) {
            $scenario = $vatService->determineScenario($client);
            return array_merge($client->toArray(), [
                'vat_scenario' => $scenario,
            ]);
        });

        $settings = BusinessSettings::getInstance();
        $defaultVatRate = $settings?->getDefaultVatRate() ?? 17;

        // Get VAT scenario for default client if provided
        $defaultClientId = $request->input('client_id');
        $suggestedVatMention = null;
        if ($defaultClientId) {
            $client = Client::find($defaultClientId);
            if ($client) {
                $scenario = $vatService->determineScenario($client, $settings);
                $suggestedVatMention = $scenario['mention'] ?? null;
            }
        }

        return Inertia::render('Invoices/Create', [
            'clients' => $clientsWithScenario,
            'vatRates' => $this->getVatRates(),
            'units' => $this->getUnits(),
            'defaultClientId' => $defaultClientId,
            'isVatExempt' => $this->isVatExempt(),
            'vatScenarios' => VatCalculationService::getAllScenarios(),
            'defaultVatRate' => $defaultVatRate,
            'vatMentionOptions' => BusinessSettings::getVatMentionOptions(),
            'defaultVatMention' => $settings?->default_vat_mention ?? ($this->isVatExempt() ? 'franchise' : 'none'),
            'suggestedVatMention' => $suggestedVatMention,
            'defaultInvoiceFooter' => $settings?->default_invoice_footer ?? 'Merci pour votre confiance !',
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
            'vat_mention' => $request->validated('vat_mention'),
            'custom_vat_mention' => $request->validated('custom_vat_mention'),
            'footer_message' => $request->validated('footer_message'),
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
        $invoice->load(['client', 'items', 'originalInvoice', 'creditNote', 'creditNotes', 'peppolTransmission']);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
            'creditNoteReasons' => Invoice::CREDIT_NOTE_REASONS,
            'peppolEnabled' => config('peppol.enabled', false),
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

        $settings = BusinessSettings::getInstance();
        $vatService = app(VatCalculationService::class);

        // Get clients with VAT scenarios
        $clients = Client::orderBy('name')->get(['id', 'name', 'currency', 'country_code', 'type', 'vat_number']);
        $clientsWithScenario = $clients->map(function ($client) use ($vatService) {
            $scenario = $vatService->determineScenario($client);
            return array_merge($client->toArray(), [
                'vat_scenario' => $scenario,
            ]);
        });

        // Get current client's VAT scenario
        $clientVatScenario = $invoice->client ? $vatService->determineScenario($invoice->client) : null;

        // Determine suggested VAT mention based on client
        $suggestedVatMention = $this->getSuggestedVatMention($invoice->client, $settings);

        return Inertia::render('Invoices/Edit', [
            'invoice' => $invoice,
            'clients' => $clientsWithScenario,
            'vatRates' => $this->getVatRates($invoice->client),
            'units' => $this->getUnits(),
            'isVatExempt' => $this->isVatExempt(),
            'defaultInvoiceFooter' => $settings?->default_invoice_footer ?? 'Merci pour votre confiance !',
            'vatMentionOptions' => BusinessSettings::getVatMentionOptions(),
            'defaultVatMention' => $settings?->default_vat_mention ?? ($this->isVatExempt() ? 'franchise' : 'none'),
            'defaultCustomVatMention' => $settings?->default_custom_vat_mention,
            'clientVatScenario' => $clientVatScenario,
            'suggestedVatMention' => $suggestedVatMention,
            'vatScenarios' => VatCalculationService::getAllScenarios(),
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
     * Send the invoice via Peppol network.
     */
    public function sendViaPeppol(Invoice $invoice, PeppolAccessPointInterface $accessPoint): RedirectResponse
    {
        // Check if Peppol is enabled
        if (!config('peppol.enabled')) {
            return back()->with('error', 'L\'envoi Peppol n\'est pas activé.');
        }

        // Check if Access Point is configured
        if (!$accessPoint->isConfigured()) {
            return back()->with('error', 'Le service Peppol n\'est pas configuré correctement.');
        }

        // Invoice must be finalized
        if (!$invoice->isFinalized()) {
            return back()->with('error', 'Seules les factures finalisées peuvent être envoyées via Peppol.');
        }

        // Check seller has Peppol endpoint
        $seller = $invoice->seller;
        if (empty($seller['peppol_endpoint_id']) || empty($seller['peppol_endpoint_scheme'])) {
            return back()->with('error', 'Veuillez configurer votre identifiant Peppol dans les paramètres entreprise.');
        }

        // Check buyer has Peppol endpoint
        $buyer = $invoice->buyer;
        if (empty($buyer['peppol_endpoint_id']) || empty($buyer['peppol_endpoint_scheme'])) {
            return back()->with('error', 'Le client doit avoir un identifiant Peppol configuré.');
        }

        // Check if there's already a pending or successful transmission
        $existingTransmission = PeppolTransmission::where('invoice_id', $invoice->id)
            ->whereIn('status', [
                PeppolTransmission::STATUS_PENDING,
                PeppolTransmission::STATUS_PROCESSING,
                PeppolTransmission::STATUS_SENT,
                PeppolTransmission::STATUS_DELIVERED,
            ])
            ->first();

        if ($existingTransmission) {
            $status = $existingTransmission->status_label;
            return back()->with('error', "Cette facture a déjà une transmission Peppol en cours ou terminée (statut: {$status}).");
        }

        // Create transmission record
        $transmission = PeppolTransmission::create([
            'user_id' => auth()->id(),
            'invoice_id' => $invoice->id,
            'status' => PeppolTransmission::STATUS_PENDING,
            'recipient_id' => $buyer['peppol_endpoint_id'],
            'recipient_scheme' => $buyer['peppol_endpoint_scheme'],
        ]);

        // Dispatch job
        SendPeppolInvoiceJob::dispatch($transmission);

        return back()->with('success', 'Envoi Peppol initié. Vous pouvez suivre le statut sur cette page.');
    }

    /**
     * Get Peppol transmission status for polling.
     */
    public function peppolStatus(Invoice $invoice): \Illuminate\Http\JsonResponse
    {
        $transmission = $invoice->peppolTransmission;

        if (!$transmission) {
            return response()->json(['transmission' => null]);
        }

        return response()->json([
            'transmission' => [
                'id' => $transmission->id,
                'status' => $transmission->status,
                'document_id' => $transmission->document_id,
                'error_message' => $transmission->error_message,
                'sent_at' => $transmission->sent_at?->toISOString(),
                'delivered_at' => $transmission->delivered_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Download Factur-X PDF (PDF/A-3 with embedded XML).
     */
    public function facturx(Invoice $invoice, FacturXService $facturXService, Request $request): HttpResponse
    {
        if ($invoice->isDraft()) {
            abort(400, 'Impossible de générer Factur-X pour un brouillon.');
        }

        $locale = $request->input('locale', 'fr');
        $profile = $request->input('profile', FacturXService::PROFILE_EN16931);

        try {
            $content = $facturXService->generate($invoice, $profile, $locale);
            $filename = $facturXService->getFilename($invoice);

            return response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Content-Length' => strlen($content),
            ]);
        } catch (\Exception $e) {
            abort(500, 'Erreur lors de la génération Factur-X: ' . $e->getMessage());
        }
    }

    /**
     * Download Factur-X XML only.
     */
    public function facturxXml(Invoice $invoice, FacturXService $facturXService, Request $request): HttpResponse
    {
        if ($invoice->isDraft()) {
            abort(400, 'Impossible de générer Factur-X pour un brouillon.');
        }

        $profile = $request->input('profile', FacturXService::PROFILE_EN16931);

        try {
            $xml = $facturXService->generateXml($invoice, $profile);
            $filename = 'factur-x_' . $invoice->number . '.xml';

            return response($xml, 200, [
                'Content-Type' => 'application/xml',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
                'Content-Length' => strlen($xml),
            ]);
        } catch (\Exception $e) {
            abort(500, 'Erreur lors de la génération XML: ' . $e->getMessage());
        }
    }

    /**
     * Create a credit note for the invoice.
     */
    public function createCreditNote(Request $request, Invoice $invoice, CreateCreditNoteAction $action): RedirectResponse
    {
        $request->validate([
            'reason' => 'nullable|string|in:' . implode(',', array_keys(Invoice::CREDIT_NOTE_REASONS)),
            'item_ids' => 'nullable|array',
            'item_ids.*' => 'exists:invoice_items,id',
        ]);

        try {
            $reason = $request->input('reason', 'cancellation');
            $itemIds = $request->input('item_ids');

            $creditNote = $action->execute($invoice, $reason, $itemIds);

            $message = $itemIds && count($itemIds) < $invoice->items->count()
                ? 'Avoir partiel créé. Vérifiez et finalisez-le.'
                : 'Note de crédit créée. Vérifiez et finalisez-la.';

            return redirect()
                ->route('invoices.edit', $creditNote)
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Get credit note reasons for API.
     */
    public function getCreditNoteReasons(): \Illuminate\Http\JsonResponse
    {
        return response()->json(Invoice::CREDIT_NOTE_REASONS);
    }

    /**
     * Download the invoice as PDF.
     * Accepts optional 'locale' query parameter to override PDF language.
     */
    public function downloadPdf(Request $request, Invoice $invoice, InvoicePdfService $pdfService): HttpResponse
    {
        try {
            $locale = $this->validatePdfLocale($request->query('locale'));
            return $pdfService->download($invoice, $locale);
        } catch (\InvalidArgumentException $e) {
            abort(400, $e->getMessage());
        }
    }

    /**
     * Stream the invoice as PDF.
     * Accepts optional 'locale' query parameter to override PDF language.
     */
    public function streamPdf(Request $request, Invoice $invoice, InvoicePdfService $pdfService): HttpResponse
    {
        try {
            $locale = $this->validatePdfLocale($request->query('locale'));
            return $pdfService->stream($invoice, $locale);
        } catch (\InvalidArgumentException $e) {
            abort(400, $e->getMessage());
        }
    }

    /**
     * Preview the invoice PDF as HTML (Inertia page).
     * Accepts optional 'locale' query parameter to override PDF language.
     */
    public function previewPdf(Request $request, Invoice $invoice, InvoicePdfService $pdfService): Response
    {
        try {
            $locale = $this->validatePdfLocale($request->query('locale'));
            $html = $pdfService->preview($invoice, $locale);

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
     * Accepts optional 'locale' query parameter to override PDF language.
     */
    public function previewHtml(Request $request, Invoice $invoice, InvoicePdfService $pdfService): \Illuminate\Http\JsonResponse
    {
        try {
            $locale = $this->validatePdfLocale($request->query('locale'));
            $html = $pdfService->preview($invoice, $locale);
            return response()->json(['html' => $html]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Get live preview HTML for draft invoice (API endpoint for iframe).
     * Accepts optional 'locale' query parameter to override PDF language.
     */
    public function previewDraft(Request $request, Invoice $invoice, InvoicePdfService $pdfService): \Illuminate\Http\JsonResponse
    {
        try {
            $locale = $this->validatePdfLocale($request->query('locale'));
            $html = $pdfService->previewDraft($invoice, $locale);
            return response()->json(['html' => $html]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Stream draft invoice as PDF.
     * Accepts optional 'locale' query parameter to override PDF language.
     */
    public function streamDraftPdf(Request $request, Invoice $invoice, InvoicePdfService $pdfService): HttpResponse
    {
        $locale = $this->validatePdfLocale($request->query('locale'));

        if ($invoice->isFinalized()) {
            return $pdfService->stream($invoice, $locale);
        }

        return $pdfService->streamDraft($invoice, $locale);
    }

    /**
     * Validate and return PDF locale if valid, null otherwise.
     */
    private function validatePdfLocale(?string $locale): ?string
    {
        $supportedLocales = ['fr', 'de', 'en', 'lb'];

        if ($locale && in_array($locale, $supportedLocales)) {
            return $locale;
        }

        return null;
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
     * Get available VAT rates based on client scenario and seller's country.
     */
    private function getVatRates(?Client $client = null): array
    {
        $settings = BusinessSettings::getInstance();
        $isVatExempt = $settings?->isVatExempt() ?? true;

        // If seller is VAT exempt (franchise), only 0%
        if ($isVatExempt) {
            return [
                ['value' => 0, 'label' => __('app.vat_rates.exempt_franchise'), 'default' => true],
            ];
        }

        // Get country-specific VAT rates
        $countryRates = $settings?->getVatRates() ?? config('countries.LU.vat_rates', []);

        // If client provided, check their VAT scenario
        if ($client) {
            $vatService = app(VatCalculationService::class);
            $scenario = $vatService->determineScenario($client, $settings);

            // For intra-EU B2B with VAT number or export, suggest 0%
            if (in_array($scenario['key'], ['B2B_INTRA_EU', 'EXPORT'])) {
                // Put 0% first with scenario label
                $rates = [
                    ['value' => 0, 'label' => '0% (' . $scenario['label'] . ')', 'default' => true],
                ];

                // Add other country rates (excluding 0% since we already have it)
                foreach ($countryRates as $rate) {
                    if ($rate['value'] > 0) {
                        $rates[] = [
                            'value' => $rate['value'],
                            'label' => $rate['label'],
                        ];
                    }
                }

                return $rates;
            }
        }

        // Return country-specific rates with default marked
        $rates = [];
        foreach ($countryRates as $rate) {
            $rates[] = [
                'value' => $rate['value'],
                'label' => $rate['label'],
                'default' => $rate['default'] ?? false,
            ];
        }

        // Add "Other" option for custom rate
        $rates[] = [
            'value' => 'custom',
            'label' => 'Autre (taux personnalisé)',
            'default' => false,
        ];

        return $rates;
    }

    private function isVatExempt(): bool
    {
        $settings = BusinessSettings::getInstance();
        return $settings?->isVatExempt() ?? true;
    }

    /**
     * Get suggested VAT mention based on client.
     */
    private function getSuggestedVatMention(?Client $client, ?BusinessSettings $settings): ?string
    {
        if (!$client) {
            return $settings?->default_vat_mention;
        }

        $vatService = app(VatCalculationService::class);
        $scenario = $vatService->determineScenario($client, $settings);

        return $scenario['mention'];
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
