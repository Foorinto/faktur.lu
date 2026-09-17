<?php

namespace App\Http\Controllers;

use App\Helpers\DatabaseHelper;
use App\Http\Requests\Api\V1\StoreExpenseRequest;
use App\Http\Requests\Api\V1\UpdateExpenseRequest;
use App\Models\BusinessSettings;
use App\Models\Expense;
use App\Models\Product;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses.
     */
    public function index(Request $request): Response
    {
        $expenses = $this->filtered($request)
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        // Le récapitulatif est construit à partir des MÊMES filtres que la
        // liste. Les deux requêtes étaient auparavant écrites séparément, et le
        // filtre « fournisseur » avait été oublié dans celle-ci : les totaux
        // affichés ne correspondaient alors plus aux lignes listées.
        $summary = $this->filtered($request)->selectRaw('
            SUM(amount_ht) as total_ht,
            SUM(amount_vat) as total_vat,
            SUM(amount_ttc) as total_ttc,
            COUNT(*) as count
        ')->first();

        // Get available years for filter
        $years = Expense::selectRaw(DatabaseHelper::distinctYear('date'))
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->values();

        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'summary' => [
                'total_ht' => $summary->total_ht ?? 0,
                'total_vat' => $summary->total_vat ?? 0,
                'total_ttc' => $summary->total_ttc ?? 0,
                'count' => $summary->count ?? 0,
            ],
            'filters' => [
                'category' => $request->input('category'),
                'year' => $request->input('year'),
                'month' => $request->input('month'),
                'provider' => $request->input('provider'),
            ],
            'categories' => $this->getCategoriesForSelect(),
            'years' => $years,
            'months' => $this->getMonthsForSelect(),
        ]);
    }

    /**
     * Show the form for creating a new expense.
     */
    public function create(): Response
    {
        return Inertia::render('Expenses/Create', $this->formOptions());
    }

    /**
     * Store a newly created expense.
     */
    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $expense = Expense::create($request->validated());

        // La ventilation (FEAT-115) est écrite après la dépense : les lignes ont
        // besoin de son id, et c'est leur somme qui fixe ensuite ses montants.
        $this->syncLines($expense, $request->validated());

        // Handle attachment upload
        if ($request->hasFile('attachment')) {
            $expense->addMediaFromRequest('attachment')
                ->toMediaCollection('attachments');
        }

        return redirect()
            ->route('expenses.index')
            ->with('success', __('app.expenses_flash.created'));
    }

    /**
     * Display the specified expense.
     */
    public function show(Expense $expense): Response
    {
        return Inertia::render('Expenses/Show', [
            'expense' => array_merge($expense->toArray(), [
                'category_label' => $expense->category_label,
                'payment_method_label' => $expense->payment_method_label,
                'vat_regime_label' => $expense->vat_regime_label,
                'supplier_country_name' => $expense->supplier_country_name,
                'attachment_url' => $expense->attachment_url,
                'attachment_filename' => $expense->attachment_filename,
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified expense.
     */
    public function edit(Expense $expense): Response
    {
        // Les lignes de ventilation alimentent l'éditeur multi-comptes (FEAT-115).
        $expense->load('lines');

        return Inertia::render('Expenses/Edit', array_merge($this->formOptions(), [
            'expense' => array_merge($expense->toArray(), [
                'attachment_url' => $expense->attachment_url,
                'attachment_filename' => $expense->attachment_filename,
            ]),
        ]));
    }

    /**
     * Update the specified expense.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $donnees = $request->validated();

        DB::transaction(function () use ($expense, $donnees) {
            // ⚠️ Les anciennes lignes partent AVANT la mise à jour.
            //
            // Une dépense tire ses montants, sa catégorie et son taux de ses
            // lignes (FEAT-115). Sauvée d'abord, elle agrégeait depuis les
            // lignes de la version précédente et réécrivait aussitôt ce que
            // l'utilisateur venait de saisir : le montant revenait à l'ancien,
            // avec un message de succès. Relevé à l'usage, invisible en test
            // parce que la seule modification couverte envoyait des lignes.
            //
            // Sans lignes, `calculateAmounts` reprend son chemin normal et
            // calcule depuis la saisie, en HT comme en TTC.
            $expense->lines()->delete();
            $expense->unsetRelation('lines');

            $expense->update($donnees);

            // Réécrit la ventilation puis recalcule les agrégats depuis les lignes.
            $this->syncLines($expense, $donnees);
        });

        // Handle attachment upload
        if ($request->hasFile('attachment')) {
            $expense->clearMediaCollection('attachments');
            $expense->addMediaFromRequest('attachment')
                ->toMediaCollection('attachments');
        }

        // Handle attachment removal
        if ($request->boolean('remove_attachment')) {
            $expense->clearMediaCollection('attachments');
        }

        return redirect()
            ->route('expenses.index')
            ->with('success', __('app.expenses_flash.updated'));
    }

    /**
     * Remove the specified expense.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()
            ->route('expenses.index')
            ->with('success', __('app.expenses_flash.deleted'));
    }

    /**
     * Get summary for a period.
     */
    public function summary(Request $request): Response
    {
        $year = $request->input('year', now()->year);

        $monthlySummary = Expense::getMonthlySummary($year);
        $yearSummary = Expense::getSummary($year);

        // Get available years
        $years = Expense::selectRaw(DatabaseHelper::distinctYear('date'))
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->values();

        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        return Inertia::render('Expenses/Summary', [
            'year' => $year,
            'years' => $years,
            'monthlySummary' => $monthlySummary,
            'yearSummary' => $yearSummary,
            'categories' => Expense::getCategories(),
        ]);
    }

    /**
     * Get categories for select.
     */
    /**
     * Requête filtrée, source unique de la liste ET du récapitulatif.
     *
     * Toute évolution des filtres doit passer par ici : c'est la duplication
     * des deux constructions qui avait laissé le filtre « fournisseur »
     * s'appliquer aux lignes sans s'appliquer aux totaux.
     */
    /**
     * Réécrit les lignes de ventilation d'une dépense (FEAT-115).
     *
     * Sans ventilation explicite, on dérive une ligne unique de la catégorie et
     * du montant HT de la dépense : la saisie mono-catégorie reste identique, et
     * aucune dépense ne se retrouve sans ligne. On remplace intégralement plutôt
     * que de rapprocher ligne à ligne : une dépense en a peu, et un delete +
     * insert évite tout état intermédiaire incohérent.
     *
     * @param  array<string, mixed>  $validated
     */
    private function syncLines(Expense $expense, array $validated): void
    {
        $lines = $validated['lines'] ?? null;

        if (empty($lines)) {
            $lines = [[
                'category' => $expense->category,
                'description' => $expense->description,
                'amount_ht' => $expense->amount_ht,
                'vat_rate' => $expense->vat_rate,
                'sort_order' => 0,
                // Réception en stock du chemin simple (FEAT-116) : un seul produit.
                'product_id' => $validated['stock_product_id'] ?? null,
                'stock_quantity' => $validated['stock_quantity'] ?? null,
            ]];
        }

        $expense->lines()->delete();

        foreach (array_values($lines) as $index => $line) {
            $expense->lines()->create([
                'user_id' => $expense->user_id,
                'category' => $line['category'],
                'description' => $line['description'] ?? null,
                'amount_ht' => $line['amount_ht'],
                'vat_rate' => $line['vat_rate'],
                'sort_order' => $line['sort_order'] ?? $index,
                // Lien stock (FEAT-116) : produit reçu + quantité, facultatifs.
                'product_id' => $line['product_id'] ?? null,
                'stock_quantity' => $line['stock_quantity'] ?? null,
            ]);
        }

        // Les lignes existent : recharger la relation puis re-sauver la dépense
        // recalcule ses montants agrégés et sa catégorie majoritaire depuis elles.
        $expense->load('lines');
        $expense->save();

        // Génère/actualise les entrées de stock issues de cette dépense.
        app(StockService::class)->syncFromExpense($expense);
    }

    private function filtered(Request $request): Builder
    {
        return Expense::query()
            ->when($request->filled('category'), fn ($q) => $q->where('category', $request->input('category')))
            ->when($request->filled('year'), fn ($q) => $q->whereYear('date', $request->input('year')))
            ->when($request->filled('month'), fn ($q) => $q->whereMonth('date', $request->input('month')))
            ->when($request->filled('provider'), fn ($q) => $q->where('provider_name', 'like', '%'.$request->input('provider').'%'));
    }

    private function getCategoriesForSelect(): array
    {
        $categories = Expense::getCategories();

        return collect($categories)->map(fn ($label, $value) => [
            'value' => $value,
            'label' => $label,
        ])->values()->toArray();
    }

    /**
     * Get payment methods for select.
     */
    private function getPaymentMethodsForSelect(): array
    {
        $methods = Expense::getPaymentMethods();

        return collect($methods)->map(fn ($label, $value) => [
            'value' => $value,
            'label' => $label,
        ])->values()->toArray();
    }

    /**
     * Get months for select.
     */
    private function getMonthsForSelect(): array
    {
        return [
            ['value' => '01', 'label' => 'Janvier'],
            ['value' => '02', 'label' => 'Février'],
            ['value' => '03', 'label' => 'Mars'],
            ['value' => '04', 'label' => 'Avril'],
            ['value' => '05', 'label' => 'Mai'],
            ['value' => '06', 'label' => 'Juin'],
            ['value' => '07', 'label' => 'Juillet'],
            ['value' => '08', 'label' => 'Août'],
            ['value' => '09', 'label' => 'Septembre'],
            ['value' => '10', 'label' => 'Octobre'],
            ['value' => '11', 'label' => 'Novembre'],
            ['value' => '12', 'label' => 'Décembre'],
        ];
    }

    /**
     * Get VAT rates for select based on seller's country.
     */
    private function getVatRates(): array
    {
        $settings = BusinessSettings::getInstance();

        // Get country-specific VAT rates
        $countryRates = $settings?->getVatRates() ?? config('countries.LU.vat_rates', []);

        return $this->normalizeRates($countryRates);
    }

    /**
     * Grilles de taux des pays dont la configuration en contient une.
     *
     * Les autres États membres ne sont pas devinés : proposer une grille
     * approximative pour la Slovaquie serait pire que de laisser saisir le
     * taux lu sur la facture. Le formulaire bascule alors en saisie libre.
     *
     * @return array<string, array<int, array<string, mixed>>>
     */
    private function getVatRatesByCountry(): array
    {
        $byCountry = [];

        foreach (config('countries', []) as $code => $country) {
            if (! empty($country['vat_rates'])) {
                $byCountry[$code] = $this->normalizeRates($country['vat_rates']);
            }
        }

        return $byCountry;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rates
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRates(array $rates): array
    {
        return collect($rates)->map(fn ($rate) => [
            'value' => $rate['value'],
            'label' => $rate['label'],
            'default' => $rate['default'] ?? false,
        ])->values()->toArray();
    }

    /**
     * Régimes de TVA pour le sélecteur.
     */
    private function getVatRegimesForSelect(): array
    {
        return collect(Expense::getVatRegimes())->map(fn ($label, $value) => [
            'value' => $value,
            'label' => $label,
        ])->values()->toArray();
    }

    /**
     * Données communes aux formulaires de création et de modification.
     */
    private function formOptions(): array
    {
        return [
            'categories' => $this->getCategoriesForSelect(),
            'vatRates' => $this->getVatRates(),
            'vatRatesByCountry' => $this->getVatRatesByCountry(),
            'vatRegimes' => $this->getVatRegimesForSelect(),
            'countries' => Expense::getSupplierCountries(),
            'homeCountry' => BusinessSettings::getInstance()?->country_code ?? 'LU',
            // Le taux d'autoliquidation est celui du pays de l'entreprise, pas
            // celui du fournisseur : c'est l'acheteur qui déclare.
            'homeStandardRate' => Expense::defaultReverseChargeRate(),
            'paymentMethods' => $this->getPaymentMethodsForSelect(),
            // Produits suivis en stock (FEAT-116) : une ligne d'achat peut les
            // faire entrer en stock. Liste vide si aucun produit n'est suivi.
            'trackedProducts' => Product::where('track_stock', true)
                ->orderBy('designation')
                ->get(['id', 'designation'])
                ->map(fn ($p) => ['value' => $p->id, 'label' => $p->designation])
                ->all(),
        ];
    }
}
