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
use App\Models\InvoicePayment;
use App\Models\InvoiceItem;
use App\Models\PeppolTransmission;
use App\Services\FacturXService;
use App\Services\InvoicePdfService;
use App\Services\Peppol\PeppolAccessPointInterface;
use App\Services\VatCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        // Filtre par moyen d'encaissement (FEAT-114).
        //
        // On retient les factures qui portent AU MOINS un encaissement de ce
        // moyen — une facture réglée moitié espèces moitié virement apparaît
        // donc dans les deux filtres, ce qui est exact.
        $moyen = $request->input('payment_method');

        if ($moyen === 'unknown') {
            // « Non renseigné » est une valeur à part entière : les
            // encaissements repris de l'ancien fonctionnement n'ont pas de
            // moyen, et pouvoir les retrouver est précisément ce qui permet de
            // les compléter.
            $query->whereHas('payments', fn ($q) => $q->whereNull('method'));
        } elseif (in_array($moyen, InvoicePayment::METHODS, true)) {
            $query->whereHas('payments', fn ($q) => $q->where('method', $moyen));
        } else {
            $moyen = null;
        }

        $invoices = $query
            // Somme des encaissements en une seule requête agrégée : charger
            // la relation pour quinze factures en produirait quinze de plus.
            ->withSum('payments as encaisse', 'amount')
            // Et, quand un moyen est filtré, la part qui lui revient : c'est
            // elle qui intéresse l'utilisateur, pas le total encaissé.
            ->when($moyen, fn ($q) => $q->withSum(
                ['payments as encaisse_moyen' => fn ($p) => $moyen === 'unknown'
                    ? $p->whereNull('method')
                    : $p->where('method', $moyen)],
                'amount'
            ))
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
                'payment_method' => $moyen,
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

            // Moyens proposés au filtre. « Non renseigné » y figure : c'est ce
            // qui permet de retrouver les encaissements repris et de les
            // compléter.
            'paymentMethods' => collect(InvoicePayment::METHODS)
                ->map(fn ($m) => ['value' => $m, 'label' => __("app.payment_methods.{$m}")])
                ->push(['value' => 'unknown', 'label' => __('app.payment_methods.unknown')])
                ->values(),
        ]);
    }

    /**
     * Show the form for creating a new invoice.
     */
    public function create(Request $request): Response
    {
        $clients = Client::orderBy('name')->get(['id', 'name', 'currency', 'country_code', 'type', 'vat_number', 'default_vat_rate', 'default_hourly_rate', 'status', 'default_discount_type', 'default_discount_value', 'default_discount_label']);

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
            'defaultPaymentMethods' => $settings?->getEffectivePaymentMethods() ?? ['transfer'],
            'defaultInvoiceFooter' => $settings?->default_invoice_footer ?? 'Merci pour votre confiance !',
            'numberingHint' => $this->buildNumberingHint($settings),
            // Sans paramètres d'entreprise, la finalisation échouera : on prévient
            // ici plutôt qu'après la saisie complète de la facture.
            'businessSettingsMissing' => $settings === null,
        ]);
    }

    /**
     * Build the numbering hint shown on the create page for users who have not
     * customised their numbering yet. Returns null when the user already changed
     * any numbering field - the assumption being they don't need the reminder.
     */
    private function buildNumberingHint(?BusinessSettings $settings): ?array
    {
        $isCustomised = $settings && (
            $settings->number_format !== \App\Services\DocumentNumberFormatter::DEFAULT_TEMPLATE
            || $settings->invoice_prefix !== \App\Actions\GenerateInvoiceNumberAction::DEFAULT_PREFIX_INVOICE
            || $settings->invoice_starting_number !== null
            || $settings->number_padding !== 3
        );

        if ($isCustomised) {
            return null;
        }

        $action = app(\App\Actions\GenerateInvoiceNumberAction::class);
        $temp = new \App\Models\Invoice(['type' => \App\Models\Invoice::TYPE_INVOICE]);

        return [
            'preview_number' => $action->preview($temp),
        ];
    }

    /**
     * Store a newly created invoice.
     */
    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $client = Client::findOrFail($request->validated('client_id'));

        $retentionRate = $request->validated('retention_guarantee_rate');
        $retentionAmount = null;

        $invoice = Invoice::create([
            'client_id' => $client->id,
            'title' => $request->validated('title'),
            'currency' => $request->validated('currency') ?? $client->currency,
            'issued_at' => $request->validated('issued_at'),
            'due_at' => $request->validated('due_at'),
            'notes' => $request->validated('notes'),
            'vat_mention' => $request->validated('vat_mention'),
            'custom_vat_mention' => $request->validated('custom_vat_mention'),
            'footer_message' => $request->validated('footer_message'),
            // FEAT-098 : null signifie « rien de précisé », la facture suivra
            // alors le réglage d'entreprise. Un tableau vide dit la même chose,
            // on le ramène à null pour n'avoir qu'une seule façon de l'écrire.
            'payment_methods' => $request->validated('payment_methods'),
            'retention_guarantee_rate' => $retentionRate,
            'retention_release_date' => $request->validated('retention_release_date'),
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
                    'discount_type' => $itemData['discount_type'] ?? 'percent',
                    'discount_value' => $itemData['discount_value'] ?? 0,
                    'vat_rate' => $itemData['vat_rate'],
                    'pcn_account' => $itemData['pcn_account'] ?? null,
                    'sort_order' => $index,
                ]);
            }
        }

        // Create global discounts if provided
        if ($request->has('discounts')) {
            foreach ($request->validated('discounts') as $index => $discountData) {
                $invoice->discounts()->create([
                    'label' => $discountData['label'] ?? null,
                    'type' => $discountData['type'],
                    'value' => $discountData['value'],
                    'sort_order' => $index,
                ]);
            }
        } elseif ($defaultDiscount = $client->defaultDiscountPayload()) {
            // Remise permanente du client (FEAT-108), recopiée une fois pour
            // toutes. La clé `discounts` absente signifie « rien de précisé » ;
            // un tableau vide signifie « pas de remise », et rien n'est créé.
            $invoice->discounts()->create($defaultDiscount + ['sort_order' => 0]);
        }

        return redirect()
            ->route('invoices.edit', $invoice)
            ->with('success', __('app.invoices_flash.created'));
    }

    /**
     * Display the specified invoice.
     */
    public function show(Invoice $invoice): Response
    {
        $invoice->load(['client', 'items', 'discounts', 'originalInvoice', 'creditNote', 'creditNotes', 'peppolTransmission', 'payments']);

        return Inertia::render('Invoices/Show', [
            'invoice' => $invoice,
            'creditNoteReasons' => Invoice::CREDIT_NOTE_REASONS,
            'peppolEnabled' => config('peppol.enabled', false),

            // Encaissements (FEAT-114). Les montants sont calculés côté
            // serveur : le modèle connaît la règle du trop-perçu et la
            // comparaison au centime près, l'interface n'a pas à la refaire.
            'payments' => $invoice->payments->map(fn ($p) => [
                'id' => $p->id,
                'amount' => (float) $p->amount,
                'paid_at' => $p->paid_at?->format('Y-m-d'),
                'method' => $p->method,
                'method_label' => $p->methodLabel(),
                // Le libellé porté par la facture : sans lui, la correction
                // rouvrirait le champ vide et l'effacerait à l'enregistrement.
                'label' => $p->label,
                'reference' => $p->reference,
            ]),
            'paymentSummary' => [
                'paid' => $invoice->amountPaid(),
                'due' => $invoice->amountDue(),
                // Acompte annoncé sur le devis, repris à la conversion : la
                // saisie le propose en un clic plutôt que de faire recalculer
                // 30 % de 1 170 € à la main.
                'deposit' => $invoice->depositAmount(),
                'is_partial' => $invoice->isPartiallyPaid(),
                'locked' => $invoice->isPaid(),
            ],
            'paymentMethods' => collect(InvoicePayment::METHODS)
                ->map(fn ($m) => ['value' => $m, 'label' => __("app.payment_methods.{$m}")])
                ->values(),
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

        $invoice->load(['client', 'items', 'discounts']);

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
            'defaultPaymentMethods' => $settings?->getEffectivePaymentMethods() ?? ['transfer'],
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

        // Recalculate retention guarantee if rate changed
        if ($request->has('retention_guarantee_rate')) {
            $invoice->load('items');
            app(\App\Actions\CalculateInvoiceTotalsAction::class)->execute($invoice);
        }

        return back()->with('success', __('app.invoices_flash.updated'));
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
                ->with('success', __('app.invoices_flash.deleted'));
        } catch (ImmutableInvoiceException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Duplicate an existing invoice as a new draft.
     * Number, snapshots, status timestamps and credit-note links are NOT copied.
     */
    public function duplicate(Invoice $invoice): RedirectResponse
    {
        if ($invoice->type === Invoice::TYPE_CREDIT_NOTE) {
            return back()->with('error', __('app.invoices_flash.error_credit_note_not_duplicable'));
        }

        $invoice->load('items');

        $duplicate = \Illuminate\Support\Facades\DB::transaction(function () use ($invoice) {
            $newInvoice = Invoice::create([
                'client_id' => $invoice->client_id,
                'title' => $invoice->title,
                'type' => Invoice::TYPE_INVOICE,
                'status' => Invoice::STATUS_DRAFT,
                'currency' => $invoice->currency,
                'issued_at' => now()->toDateString(),
                'due_at' => now()->addDays(config('billing.default_payment_days', 30))->toDateString(),
                'notes' => $invoice->notes,
                'footer_message' => $invoice->footer_message,
                // Dupliquer une facture doit redonner le même document : les
                // moyens de paiement choisis en font partie (FEAT-098).
                'payment_methods' => $invoice->payment_methods,
                'vat_mention' => $invoice->vat_mention,
                'custom_vat_mention' => $invoice->custom_vat_mention,
                'retention_guarantee_rate' => $invoice->retention_guarantee_rate,
                'retention_release_date' => $invoice->retention_release_date,
            ]);

            foreach ($invoice->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $newInvoice->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'discount_type' => $item->discount_type,
                    'discount_value' => $item->discount_value,
                    'vat_rate' => $item->vat_rate,
                    'sort_order' => $item->sort_order,
                ]);
            }

            foreach ($invoice->discounts as $discount) {
                $newInvoice->discounts()->create([
                    'label' => $discount->label,
                    'type' => $discount->type,
                    'value' => $discount->value,
                    'sort_order' => $discount->sort_order,
                ]);
            }

            return $newInvoice;
        });

        return redirect()
            ->route('invoices.edit', $duplicate)
            ->with('success', __('app.invoices_flash.duplicated'));
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
                ->with('success', __('app.invoices_flash.finalized', ['number' => $invoice->number]));
        } catch (\Exception $e) {
            return back()->with('error', \App\Support\UserError::report($e, 'invoice.finalize'));
        }
    }

    /**
     * Mark the invoice as sent.
     */
    public function markAsSent(Invoice $invoice): RedirectResponse
    {
        if (!$invoice->isFinalized() || $invoice->status === Invoice::STATUS_SENT) {
            return back()->with('error', __('app.invoices_flash.error_action_not_allowed'));
        }

        $invoice->update([
            'status' => Invoice::STATUS_SENT,
            'sent_at' => now(),
        ]);

        return back()->with('success', __('app.invoices_flash.marked_sent'));
    }

    /**
     * Mark the invoice as paid.
     */
    /**
     * Marquer la facture comme réglée en totalité, en un seul encaissement.
     *
     * Conservé pour le cas courant — le client paie tout, d'un coup. Mais le
     * chemin passe désormais par un encaissement : sans lui, la facture serait
     * « payée » sans qu'aucun montant ne figure au récapitulatif par moyen.
     */
    public function markAsPaid(Request $request, Invoice $invoice): RedirectResponse
    {
        if (!$invoice->isFinalized() || $invoice->isPaid()) {
            return back()->with('error', __('app.invoices_flash.error_action_not_allowed'));
        }

        $validated = $request->validate([
            'paid_at' => 'nullable|date|before_or_equal:today',
            'method' => ['nullable', Rule::in(InvoicePayment::METHODS)],
        ]);

        $invoice->payments()->create([
            'amount' => $invoice->amountDue(),
            'paid_at' => $validated['paid_at'] ?? now()->toDateString(),
            'method' => $validated['method'] ?? null,
        ]);

        $invoice->refresh()->refreshPaymentStatus();

        return back()->with('success', __('app.invoices_flash.marked_paid'));
    }

    /**
     * Enregistre un encaissement partiel (FEAT-114).
     *
     * Le montant n'est pas plafonné au reste dû : un trop-perçu arrive, et le
     * refuser obligerait l'utilisateur à mentir sur ce qu'il a réellement reçu.
     * Le reste dû, lui, ne descend jamais sous zéro.
     */
    public function storePayment(Request $request, Invoice $invoice): RedirectResponse
    {
        if (! $invoice->isFinalized()) {
            return back()->with('error', __('app.invoices_flash.error_action_not_allowed'));
        }

        // Plafonné au reste dû. Le trop-perçu existe — un client qui règle un
        // montant rond en espèces — mais la faute de frappe est bien plus
        // fréquente, et une facture encaissée au-delà de son total est presque
        // toujours une erreur de saisie.
        $donnees = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$invoice->amountDue()],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['nullable', Rule::in(InvoicePayment::METHODS)],
            // Libellé porté par la facture. Laissé vide, le PDF écrit
            // « Acompte versé le … » ou « Règlement du … » selon la date.
            'label' => ['nullable', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:255'],
        ], [
            'amount.max' => __('app.invoices_flash.error_payment_exceeds', [
                'amount' => number_format($invoice->amountDue(), 2, ',', ' '),
            ]),
        ]);

        $invoice->payments()->create($donnees);
        $invoice->refresh()->refreshPaymentStatus();

        return back()->with('success', __('app.invoices_flash.payment_recorded'));
    }

    /**
     * Corrige le moyen, la date ou la référence d'un encaissement.
     *
     * Toujours autorisé, même sur une facture soldée — et c'est le point
     * important. Aucun de ces trois champs ne touche au statut ni aux montants
     * de la facture : le garde d'immuabilité n'est pas concerné.
     *
     * Sans cette correction, marquer une facture payée depuis la LISTE créait
     * un encaissement sans moyen, aussitôt verrouillé : l'information que
     * l'utilisateur cherche à collecter était perdue définitivement.
     *
     * Le montant est modifiable lui aussi : le réduire fait redescendre la
     * somme sous le total et la facture redevient due, ce que le modèle
     * autorise depuis FEAT-114.
     */
    public function updatePayment(Request $request, Invoice $invoice, InvoicePayment $payment): RedirectResponse
    {
        abort_unless($payment->invoice_id === $invoice->id, 404);

        // Le plafond exclut l'encaissement en cours de correction : sans cela,
        // repasser 500 € à 500 € serait refusé sur une facture soldée.
        $plafond = round((float) $invoice->total_ttc - $invoice->amountPaid() + (float) $payment->amount, 2);

        $donnees = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:'.$plafond],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['nullable', Rule::in(InvoicePayment::METHODS)],
            // Libellé porté par la facture. Laissé vide, le PDF écrit
            // « Acompte versé le … » ou « Règlement du … » selon la date.
            'label' => ['nullable', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:255'],
        ], [
            'amount.max' => __('app.invoices_flash.error_payment_exceeds', [
                'amount' => number_format($plafond, 2, ',', ' '),
            ]),
        ]);

        $payment->update($donnees);

        // Le montant a pu changer : le statut et `paid_at` en dérivent tous
        // les deux, et la facture peut redevenir due.
        $invoice->refresh()->refreshPaymentStatus();

        return back()->with('success', __('app.invoices_flash.payment_updated'));
    }

    /**
     * Supprime un encaissement.
     *
     * Possible même sur une facture soldée : la suppression fait redescendre la
     * somme sous le total, et la facture redevient due. C'est ce qui permet de
     * traiter un chèque revenu impayé, un virement rejeté ou une saisie
     * erronée sur un paiement en plusieurs fois.
     */
    public function destroyPayment(Invoice $invoice, InvoicePayment $payment): RedirectResponse
    {
        abort_unless($payment->invoice_id === $invoice->id, 404);

        $payment->delete();
        $invoice->refresh()->refreshPaymentStatus();

        return back()->with('success', __('app.invoices_flash.payment_deleted'));
    }

    /**
     * Update the payment date of an already paid invoice.
     */
    public function updatePaidAt(Request $request, Invoice $invoice): RedirectResponse
    {
        if (!$invoice->isPaid()) {
            return back()->with('error', __('app.invoices_flash.error_not_paid'));
        }

        $validated = $request->validate([
            'paid_at' => 'required|date|before_or_equal:today',
        ]);

        $invoice->update(['paid_at' => $validated['paid_at']]);

        return back()->with('success', __('app.invoices_flash.paid_at_updated'));
    }

    /**
     * Send the invoice via Peppol network.
     */
    public function sendViaPeppol(Invoice $invoice, PeppolAccessPointInterface $accessPoint): RedirectResponse
    {
        // Check if Peppol is enabled
        if (!config('peppol.enabled')) {
            return back()->with('error', __('app.invoices_flash.peppol_disabled'));
        }

        // Check if Access Point is configured
        if (!$accessPoint->isConfigured()) {
            return back()->with('error', __('app.invoices_flash.peppol_not_configured'));
        }

        // Invoice must be finalized
        if (!$invoice->isFinalized()) {
            return back()->with('error', __('app.invoices_flash.peppol_requires_finalized'));
        }

        // Check seller has Peppol endpoint
        $seller = $invoice->seller;
        if (empty($seller['peppol_endpoint_id']) || empty($seller['peppol_endpoint_scheme'])) {
            return back()->with('error', __('app.invoices_flash.peppol_seller_endpoint_missing'));
        }

        // Check buyer has Peppol endpoint
        $buyer = $invoice->buyer;
        if (empty($buyer['peppol_endpoint_id']) || empty($buyer['peppol_endpoint_scheme'])) {
            return back()->with('error', __('app.invoices_flash.peppol_buyer_endpoint_missing'));
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
            return back()->with('error', __('app.invoices_flash.peppol_transmission_exists', ['status' => $status]));
        }

        // Autoriser un nouvel essai après un échec : purger les transmissions échouées.
        PeppolTransmission::where('invoice_id', $invoice->id)
            ->where('status', PeppolTransmission::STATUS_FAILED)
            ->delete();

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

        return back()->with('success', __('app.invoices_flash.peppol_sent'));
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
            abort(500, \App\Support\UserError::report($e, 'invoice.facturx_pdf'));
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
            abort(500, \App\Support\UserError::report($e, 'invoice.facturx_xml'));
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
                ? __('app.invoices_flash.credit_note_partial_created')
                : __('app.invoices_flash.credit_note_created');

            return redirect()
                ->route('invoices.edit', $creditNote)
                ->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', \App\Support\UserError::report($e, 'invoice.credit_note'));
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
            return response()->json(['error' => \App\Support\UserError::report($e, 'invoice.preview_draft')], 400);
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
        $supportedLocales = ['fr', 'de', 'en', 'lb', 'pt'];

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
            return back()->with('error', __('app.invoices_flash.email_requires_finalized'));
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

            return back()->with('success', __('app.invoices_flash.email_sent'));
        } catch (\Exception $e) {
            return back()->with('error', \App\Support\UserError::report($e, 'invoice.email'));
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
