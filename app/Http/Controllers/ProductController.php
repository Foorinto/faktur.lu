<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\BusinessSettings;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Rules\SalesVatRateAllowed;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
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

        // Seules les familles et les articles ordinaires sont paginés : une
        // variante n'est pas une ligne du catalogue, elle appartient à sa
        // famille. Sans cela, une famille de 45 nuances occuperait deux pages
        // et demie à elle seule.
        $products = Product::query()
            ->topLevel()
            ->ofType($type)
            ->with(['variants:id,parent_id,designation,variant_label,reference,unit_price_ht,vat_rate,is_active,track_stock,sort_order'])
            ->withCount('variants')
            ->orderBy('designation')
            ->paginate(20)
            ->withQueryString();

        // Les compteurs portent sur tout le catalogue, pas sur la page courante :
        // sinon les onglets changeraient de valeur en paginant.
        //
        // Chaque famille est comptée par une requête explicite plutôt que par un
        // GROUP BY dont on relirait les clés : une clé NULL ne se comporte pas
        // de la même façon selon le moteur, et ce catalogue vit sur MySQL
        // pendant que les tests tournent sur SQLite.
        return Inertia::render('Products/Index', [
            'products' => $products,
            'canCreate' => $this->planService->canCreateProduct($request->user()),
            'quota' => $this->quotaInfo($request),
            'units' => $this->getUnits(),
            'vatRates' => $this->getVatRates(),
            'filters' => ['type' => $type],
            // Les compteurs suivent la liste : ils dénombrent des familles et
            // des articles ordinaires, pas des variantes. Sinon l'onglet
            // annoncerait 46 là où la page en montre une.
            'typeCounts' => [
                'all' => Product::query()->topLevel()->count(),
                Product::TYPE_PRODUCT => Product::query()->topLevel()->where('type', Product::TYPE_PRODUCT)->count(),
                Product::TYPE_SERVICE => Product::query()->topLevel()->where('type', Product::TYPE_SERVICE)->count(),
                'unclassified' => Product::query()->topLevel()->whereNull('type')->count(),
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
    /**
     * Les déclinaisons d'une famille, pour le choix en deux temps.
     *
     * Déclenchée au clic, une fois, jamais à la frappe : `parent_id` est
     * indexé, la requête coûte ce qu'elle doit coûter.
     */
    public function variants(Product $product): JsonResponse
    {
        return response()->json([
            'axis' => $product->axisLabel(),
            'family' => $product->designation,
            'variants' => $product->variants()->active()->get([
                'id', 'parent_id', 'designation', 'variant_label', 'reference',
                'type', 'unit_price_ht', 'vat_rate', 'unit', 'pcn_account',
            ])->map(function (Product $v) use ($product) {
                $v->setRelation('parent', $product);
                $v->setAttribute('display_name', $v->displayName());

                return $v;
            }),
        ]);
    }

    /**
     * Crée d'un coup toutes les variantes d'une famille.
     *
     * L'action centrale de la fonctionnalité : six à huit tailles, dix à
     * cinquante coloris, trois à cinq formats. Les saisir une par une est
     * exactement la peine qu'on supprime ici.
     */
    public function storeVariants(Request $request, Product $product): RedirectResponse
    {
        abort_if($product->isVariant(), 404);

        $data = $request->validate([
            'variant_axis_label' => ['nullable', 'string', 'max:50'],
            'labels' => ['required', 'string', 'max:5000'],
        ]);

        // Une variante par ligne, les vides ignorées, les doublons écartés :
        // coller une liste depuis un tableur amène souvent des lignes vides.
        $libelles = collect(preg_split('/\r\n|\r|\n/', $data['labels']))
            ->map(fn ($l) => trim($l))
            ->filter()
            ->unique()
            ->values();

        if ($libelles->isEmpty()) {
            return back()->with('error', __('app.products.variants_none_given'));
        }

        $existants = $product->variants()->pluck('variant_label')
            ->map(fn ($l) => mb_strtolower((string) $l))
            ->all();

        $depart = (int) $product->variants()->max('sort_order');
        $crees = 0;

        DB::transaction(function () use ($product, $libelles, $existants, $depart, &$crees, $data) {
            if (! empty($data['variant_axis_label'])) {
                $product->update(['variant_axis_label' => $data['variant_axis_label']]);
            }

            foreach ($libelles as $index => $libelle) {
                if (in_array(mb_strtolower($libelle), $existants, true)) {
                    continue;
                }

                $product->variants()->create([
                    'user_id' => $product->user_id,
                    'designation' => $product->designation,
                    'variant_label' => $libelle,
                    // La référence dérive de celle de la famille : un article
                    // stockable distinct a besoin d'un code à lui.
                    'reference' => $product->reference
                        ? $product->reference.'-'.Str::slug($libelle)
                        : null,
                    'description' => $product->description,
                    'type' => $product->type,
                    'unit_price_ht' => $product->unit_price_ht,
                    'vat_rate' => $product->vat_rate,
                    'pcn_account' => $product->pcn_account,
                    'unit' => $product->unit,
                    'is_active' => $product->is_active,
                    'track_stock' => $product->track_stock,
                    'stock_alert_threshold' => $product->stock_alert_threshold,
                    // L'ordre de la liste collée est l'ordre voulu : S, M, L, XL.
                    'sort_order' => $depart + $index + 1,
                ]);

                $crees++;
            }
        });

        return redirect()->route('products.index')->with(
            'success',
            __('app.products.variants_created', ['count' => $crees])
        );
    }

    /**
     * Réapplique le prix, la TVA, l'unité et le compte de la famille à toutes
     * ses variantes.
     *
     * C'est le geste qui justifie la fonctionnalité : changer un prix une fois
     * au lieu de quarante-cinq.
     */
    public function propagateToVariants(Product $product): RedirectResponse
    {
        abort_if($product->isVariant(), 404);

        $touchees = $product->propagateToVariants();

        return back()->with('success', __('app.products.variants_propagated', ['count' => $touchees]));
    }

    public function destroy(Product $product): RedirectResponse
    {
        // ⚠️ Une famille ne se supprime pas tant qu'elle porte des variantes.
        // Emporter les variantes emporterait leurs mouvements de stock, donc
        // des écritures qui justifient un inventaire. L'utilisateur détache ou
        // supprime ses variantes d'abord : le geste reste le sien.
        if ($product->isFamily()) {
            return back()->with('error', __('app.products.delete_family_blocked', [
                'count' => $product->variants()->count(),
            ]));
        }

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

        $colonnes = ['id', 'parent_id', 'designation', 'variant_label', 'description',
            'reference', 'type', 'unit_price_ht', 'vat_rate', 'unit', 'pcn_account'];

        // ⚠️ `topLevel()` est une égalité sur colonne indexée, sans sous-requête :
        // taper « GL30 » renvoie UNE ligne au lieu des quarante-cinq nuances.
        // La recherche est donc plus légère depuis les variantes, pas plus
        // lourde. On ne renvoie jamais une variante nue : sa famille seule est
        // proposée, et le choix de la déclinaison vient après.
        $products = Product::query()
            ->active()
            ->topLevel()
            ->ofType($request->query('type'))
            ->when($term !== '', function ($query) use ($term) {
                $query->where(function ($q) use ($term) {
                    $q->where('designation', 'like', "%{$term}%")
                        ->orWhere('reference', 'like', "%{$term}%");
                });
            })
            ->withCount('variants')
            ->orderBy('designation')
            ->limit(50)
            ->get($colonnes);

        // Chercher « CLAV-775-azerty » doit trouver la variante, pas seulement
        // sa famille : c'est le code que l'utilisateur a sous les yeux sur son
        // inventaire. Requête distincte et bornée à dix, pour ne pas rallonger
        // la liste principale.
        $variantes = collect();

        if (mb_strlen($term) >= 3) {
            $variantes = Product::query()
                ->active()
                ->variantsOnly()
                ->with('parent:id,designation,variant_axis_label')
                ->where(function ($q) use ($term) {
                    $q->where('reference', 'like', "%{$term}%")
                        ->orWhere('variant_label', 'like', "%{$term}%");
                })
                ->orderBy('sort_order')
                ->limit(10)
                ->get($colonnes)
                ->map(function (Product $v) {
                    // La ligne porte le nom complet : « famille — variante ».
                    // Sans la famille, l'utilisateur ne sait pas ce qu'il choisit.
                    $v->setAttribute('display_name', $v->displayName());
                    $v->setAttribute('variants_count', 0);

                    return $v;
                });
        }

        return response()->json(['products' => $products->concat($variantes)->values()]);
    }

    /**
     * Modifie en une fois le type et/ou le taux de TVA d'une sélection.
     *
     * Le portefeuille est cloisonné par le scope global de BelongsToUser : la
     * requête filtre sur les identifiants fournis, et le scope y ajoute le
     * `user_id` de la session. Un identifiant appartenant à un autre compte ne
     * correspond donc à aucune ligne, plutôt que d'être modifié.
     */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'type' => ['nullable', Rule::in(Product::TYPES)],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100', new SalesVatRateAllowed],
        ]);

        // `type` peut valoir null volontairement (« non classé ») : on distingue
        // « absent de la requête » de « transmis à vide » par la présence de la
        // clé, sans quoi on ne pourrait jamais déclasser un article.
        $changes = [];

        if ($request->has('type')) {
            $changes['type'] = $validated['type'] ?? null;
        }

        if ($request->filled('vat_rate')) {
            $changes['vat_rate'] = $validated['vat_rate'];
        }

        if ($changes === []) {
            return back()->with('error', __('app.products.bulk_nothing_to_change'));
        }

        $affected = Product::whereIn('id', $validated['ids'])->update($changes);

        return back()->with('success', __('app.products.bulk_updated', ['count' => $affected]));
    }

    /**
     * Supprime une sélection d'articles.
     *
     * Suppression douce (le modèle utilise SoftDeletes) : une sélection ratée
     * reste rattrapable en base, ce qui compte pour une action de masse.
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $affected = Product::whereIn('id', $validated['ids'])->delete();

        return back()->with('success', __('app.products.bulk_deleted', ['count' => $affected]));
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
