<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\BusinessSettings;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function __construct(
        protected PlanService $planService
    ) {}

    /**
     * List the user's catalogue.
     */
    public function index(Request $request): Response
    {
        $type = $request->input('type');

        $products = Product::query()
            ->ofType($type)
            ->orderBy('designation')
            ->paginate(20)
            ->withQueryString();

        // Les compteurs par famille sont calculés sur tout le catalogue, pas sur
        // la page courante : sinon les onglets changeraient de valeur en
        // paginant. `unclassified` compte les articles antérieurs au champ.
        $counts = Product::query()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        return Inertia::render('Products/Index', [
            'products' => $products,
            'canCreate' => $this->planService->canCreateProduct($request->user()),
            'quota' => $this->quotaInfo($request),
            'units' => $this->getUnits(),
            'filters' => ['type' => $type],
            'typeCounts' => [
                'all' => $counts->sum(),
                Product::TYPE_PRODUCT => (int) ($counts[Product::TYPE_PRODUCT] ?? 0),
                Product::TYPE_SERVICE => (int) ($counts[Product::TYPE_SERVICE] ?? 0),
                'unclassified' => (int) ($counts[''] ?? $counts[null] ?? 0),
            ],
        ]);
    }

    /**
     * Show the create form.
     */
    public function create(): Response
    {
        return Inertia::render('Products/Create', [
            'units' => $this->getUnits(),
            'vatRates' => $this->getVatRates(),
        ]);
    }

    /**
     * Persist a new catalogue item.
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        Product::create($request->validated());

        return redirect()
            ->route('products.index')
            ->with('success', __('app.products.flash_created'));
    }

    /**
     * Show the edit form.
     */
    public function edit(Product $product): Response
    {
        return Inertia::render('Products/Edit', [
            'product' => $product,
            'units' => $this->getUnits(),
            'vatRates' => $this->getVatRates(),
        ]);
    }

    /**
     * Update a catalogue item.
     */
    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $product->update($request->validated());

        return redirect()
            ->route('products.index')
            ->with('success', __('app.products.flash_updated'));
    }

    /**
     * Delete a catalogue item.
     */
    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()
            ->route('products.index')
            ->with('success', __('app.products.flash_deleted'));
    }

    /**
     * Autocomplete endpoint used when filling an invoice/quote line (FEAT-095, tranche 3).
     */
    public function search(Request $request): JsonResponse
    {
        $term = trim((string) $request->query('q', ''));

        $products = Product::query()
            ->active()
            // Filtre facultatif : l'autocomplétion des lignes de facture peut
            // s'y appuyer plus tard sans que son appel actuel change.
            ->ofType($request->query('type'))
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('designation', 'like', "%{$term}%")
                        ->orWhere('reference', 'like', "%{$term}%");
                });
            })
            ->orderBy('designation')
            ->limit(50)
            ->get(['id', 'designation', 'description', 'reference', 'type', 'unit_price_ht', 'vat_rate', 'unit']);

        return response()->json(['products' => $products]);
    }

    /**
     * Quota info for the index UI (null limit = unlimited).
     */
    private function quotaInfo(Request $request): array
    {
        $plan = $this->planService->getUserPlan($request->user());
        $limit = $plan->getLimit('max_products');

        return [
            'limit' => $limit,
            'used' => $request->user()->products()->count(),
        ];
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    private function getUnits(): array
    {
        $units = [];
        foreach (InvoiceItem::getUnits() as $value => $label) {
            $units[] = ['value' => $value, 'label' => $label];
        }

        return $units;
    }

    /**
     * Standard Luxembourg VAT rates (custom values still allowed via manual entry).
     *
     * @return array<int, float>
     */
    /**
     * Taux de TVA proposés dans le catalogue.
     * Suit le régime et le pays de l'entreprise, comme sur les factures.
     *
     * @return array<int, int>
     */
    private function getVatRates(): array
    {
        $settings = BusinessSettings::getInstance();

        // En franchise de TVA, seul le 0 % est applicable.
        if ($settings?->isVatExempt() ?? true) {
            return [0];
        }

        $rates = $settings?->getVatRates() ?: config('countries.LU.vat_rates', []);
        $values = array_map(fn ($rate) => (int) ($rate['value'] ?? $rate), $rates);

        // 0 % reste proposé (autoliquidation, export, opérations exonérées).
        if (! in_array(0, $values, true)) {
            $values[] = 0;
        }

        return array_values(array_unique($values));
    }
}
