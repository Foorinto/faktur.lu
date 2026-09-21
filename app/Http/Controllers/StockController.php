<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestion de stock (FEAT-116) : la page dédiée et ses mouvements manuels.
 *
 * Le stock courant et sa valeur sont calculés depuis le journal des mouvements,
 * jamais stockés. La page ne montre que les produits effectivement suivis.
 */
class StockController extends Controller
{
    public function __construct(private StockService $stock) {}

    public function index(): Response
    {
        // Les variantes suivent leur famille, et dans l'ordre voulu : un stock
        // rangé par ordre alphabétique mélangerait les nuances d'une même
        // famille avec le reste du catalogue.
        $products = Product::where('track_stock', true)
            ->with('parent:id,designation,variant_axis_label')
            ->orderBy('designation')
            ->orderBy('sort_order')
            ->get();

        // Le total de la famille : quarante-cinq nuances à trois unités
        // chacune, c'est cent trente-cinq pièces en rayon, et c'est ce chiffre
        // qu'on cherche avant de commander.
        $totauxFamille = $products->whereNotNull('parent_id')
            ->groupBy('parent_id')
            ->map(fn ($variantes) => [
                'quantity' => round($variantes->sum(fn (Product $v) => $v->currentStock()), 4),
                'value' => round($variantes->sum(fn (Product $v) => $v->stockValue()), 2),
                'count' => $variantes->count(),
            ]);

        $rows = $products->map(fn (Product $p) => [
            'id' => $p->id,
            'parent_id' => $p->parent_id,
            'family_total' => $totauxFamille->get($p->id),
            // ⚠️ Le nom complet : savoir que « Clavier mécanique » est bas ne
            // sert à rien, il faut savoir QUELLE déclinaison l'est.
            'designation' => $p->displayName(),
            'reference' => $p->reference,
            'unit' => $p->unit,
            'current_stock' => $p->currentStock(),
            'stock_value' => $p->stockValue(),
            'threshold' => $p->stock_alert_threshold !== null ? (float) $p->stock_alert_threshold : null,
            'is_low' => $p->isLowOnStock(),
        ]);

        return Inertia::render('Stock/Index', [
            'products' => $rows,
            'total_value' => round($rows->sum('stock_value'), 2),
            'low_count' => $rows->where('is_low', true)->count(),
        ]);
    }

    /**
     * Entrée manuelle de stock (réception, achat direct).
     */
    public function storeEntry(Request $request, Product $product): RedirectResponse
    {
        // Une famille peut ne pas suivre son propre stock tout en portant des
        // déclinaisons qui le suivent : c'est même le cas normal.
        $aDesVariantesSuivies = $product->variants()->where('track_stock', true)->exists();

        abort_unless($product->track_stock || $aDesVariantesSuivies, 404);

        $data = $request->validate([
            'quantity' => ['nullable', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
            // Répartition sur les déclinaisons : 500 souris reçues, 200 en
            // blanc et 300 en vert. Chaque ligne devient son propre mouvement,
            // sinon le stock d'une nuance resterait inconnu.
            'allocations' => ['nullable', 'array'],
            'allocations.*.product_id' => ['required', 'integer'],
            'allocations.*.quantity' => ['nullable', 'numeric', 'min:0'],
        ]);

        $repartition = collect($data['allocations'] ?? [])
            ->filter(fn ($ligne) => (float) ($ligne['quantity'] ?? 0) > 0)
            ->values();

        if ($repartition->isNotEmpty()) {
            return $this->entreeRepartie($product, $repartition, $data);
        }

        if (empty($data['quantity'])) {
            return back()->withErrors([
                'quantity' => $aDesVariantesSuivies
                    ? __('app.stock.allocation_empty')
                    : __('validation.required', ['attribute' => __('app.stock.quantity')]),
            ]);
        }

        $this->stock->recordEntry(
            $product,
            (float) $data['quantity'],
            isset($data['unit_cost']) ? (float) $data['unit_cost'] : null,
            $data['date'],
            $data['note'] ?? null,
        );

        return back()->with('success', __('app.stock.flash_entry_recorded'));
    }

    /**
     * Une réception éclatée entre les déclinaisons d'une même famille.
     *
     * Deux filets, et ils ne couvrent pas la même chose. La boucle de contrôle
     * s'exécute d'abord : une ligne fautive arrête tout avant la moindre
     * écriture, c'est ce que vérifie le test. La transaction couvre ce que le
     * contrôle ne peut pas prévoir — une erreur de base au troisième
     * mouvement, qui laisserait les deux premiers posés.
     *
     * @param  Collection<int, array{product_id: int, quantity: mixed}>  $repartition
     */
    private function entreeRepartie(Product $famille, $repartition, array $data): RedirectResponse
    {
        // Le scope global limite déjà au compte courant : une déclinaison d'un
        // autre utilisateur est introuvable, donc absente de cette liste.
        $variantes = $famille->variants()->where('track_stock', true)->get()->keyBy('id');

        foreach ($repartition as $ligne) {
            if (! $variantes->has((int) $ligne['product_id'])) {
                abort(404);
            }
        }

        DB::transaction(function () use ($repartition, $variantes, $data) {
            foreach ($repartition as $ligne) {
                $this->stock->recordEntry(
                    $variantes->get((int) $ligne['product_id']),
                    (float) $ligne['quantity'],
                    isset($data['unit_cost']) ? (float) $data['unit_cost'] : null,
                    $data['date'],
                    $data['note'] ?? null,
                );
            }
        });

        return back()->with('success', __('app.stock.flash_entry_allocated', [
            'count' => $repartition->count(),
            'total' => $repartition->sum(fn ($l) => (float) $l['quantity']),
        ]));
    }

    /**
     * Saisie d'inventaire : la quantité réelle comptée. L'écart avec le stock
     * courant devient un mouvement d'ajustement daté et motivé.
     */
    public function storeInventory(Request $request, Product $product): RedirectResponse
    {
        abort_unless($product->track_stock, 404);

        $data = $request->validate([
            'counted_quantity' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $movement = $this->stock->recordInventory(
            $product,
            (float) $data['counted_quantity'],
            $data['date'],
            $data['note'] ?? null,
        );

        return back()->with('success', $movement === null
            ? __('app.stock.flash_inventory_no_change')
            : __('app.stock.flash_inventory_recorded'));
    }

    /**
     * Historique des mouvements d'un produit (pour la traçabilité).
     */
    public function movements(Product $product): Response
    {
        abort_unless($product->track_stock, 404);

        $movements = $product->stockMovements()
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'quantity', 'type', 'source_type', 'date', 'unit_cost', 'note'])
            ->map(fn ($m) => [
                'id' => $m->id,
                'quantity' => $m->quantity,
                'type' => $m->type,
                'date' => $m->date?->toDateString(),
                'unit_cost' => $m->unit_cost !== null ? (float) $m->unit_cost : null,
                'note' => $m->note,
                // Un mouvement manuel (sans source) est corrigible ; un mouvement
                // issu d'une facture reste immuable, comme la facture elle-même.
                'is_manual' => $m->source_type === null,
            ]);

        return Inertia::render('Stock/Movements', [
            'product' => [
                'id' => $product->id,
                'designation' => $product->designation,
                'current_stock' => $product->currentStock(),
            ],
            'movements' => $movements,
        ]);
    }

    /**
     * Supprime un mouvement MANUEL saisi par erreur (entrée, ajustement,
     * inventaire). Un mouvement issu d'une facture est immuable : sa correction
     * passe par une note de crédit, jamais par une suppression rétroactive.
     */
    public function destroyMovement(Product $product, StockMovement $movement): RedirectResponse
    {
        abort_unless((int) $movement->product_id === (int) $product->id, 404);

        // Seuls les mouvements sans source (saisis à la main) sont supprimables.
        abort_unless($movement->source_type === null, 403);

        $movement->delete();

        return back()->with('success', __('app.stock.flash_movement_deleted'));
    }
}
