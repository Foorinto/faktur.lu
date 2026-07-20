<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
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
        $products = Product::query()
            ->orderBy('designation')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products,
            'canCreate' => $this->planService->canCreateProduct($request->user()),
            'quota' => $this->quotaInfo($request),
            'units' => $this->getUnits(),
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
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('designation', 'like', "%{$term}%")
                        ->orWhere('reference', 'like', "%{$term}%");
                });
            })
            ->orderBy('designation')
            ->limit(20)
            ->get(['id', 'designation', 'description', 'reference', 'unit_price_ht', 'vat_rate', 'unit']);

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
    private function getVatRates(): array
    {
        return [17, 14, 8, 3, 0];
    }
}
