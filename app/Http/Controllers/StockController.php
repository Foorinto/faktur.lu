<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\StockMovement;
use App\Services\StockService;
use App\Services\StockSnapshot;
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

        // ⚠️ Trois requêtes pour tout le stock de la page. Appeler
        // `currentStock()` et `stockValue()` article par article en coûtait une
        // dizaine par ligne, et la page grossissait avec le catalogue.
        $etat = new StockSnapshot($products);

        // Le dernier coût saisi par article, proposé d'office à la prochaine
        // entrée ; et les entrées manuelles restées sans coût, à valoriser.
        $ids = $products->pluck('id')->all();
        $derniersCouts = StockMovement::query()
            ->whereIn('product_id', $ids)
            ->where('type', StockMovement::TYPE_ENTREE)
            ->whereNotNull('unit_cost')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get(['product_id', 'unit_cost'])
            ->unique('product_id')
            ->keyBy('product_id');
        $sansCout = StockMovement::query()
            ->whereIn('product_id', $ids)
            ->where('type', StockMovement::TYPE_ENTREE)
            ->whereNull('unit_cost')
            ->whereNull('source_type')
            ->selectRaw('product_id, count(*) as n')
            ->groupBy('product_id')
            ->pluck('n', 'product_id');

        $rows = $products->map(function (Product $p) use ($etat, $derniersCouts, $sansCout) {
            $id = (int) $p->id;
            $estFamille = $etat->estUneFamille($id);

            return [
                'id' => $id,
                'parent_id' => $p->parent_id,
                'variants_count' => count($etat->declinaisonsDe($id)),
                // Le stock propre de la famille, celui qui n'est rattaché à
                // aucune déclinaison. Zéro la plupart du temps.
                'unallocated_stock' => $estFamille ? $etat->stock($id) : null,
                // ⚠️ Le nom complet : savoir que « Clavier mécanique » est bas
                // ne sert à rien, il faut savoir QUELLE déclinaison l'est.
                'designation' => $p->displayName(),
                'reference' => $p->reference,
                'unit' => $p->unit,
                // La colonne annonce tout ce que la famille couvre : « combien
                // de souris ai-je » appelle 350, pas le reliquat qu'on a oublié
                // de ventiler. Le reliquat se nomme à part, en dessous.
                'current_stock' => $estFamille ? $etat->stockDeLaFamille($id) : $etat->stock($id),
                'stock_value' => $estFamille ? $etat->valeurDeLaFamille($id) : $etat->valeur($id),
                'threshold' => $p->stock_alert_threshold !== null ? (float) $p->stock_alert_threshold : null,
                'is_low' => $etat->estSousLeSeuil($p),
                // ⚠️ Ce que vaut l'article lui-même : les totaux de la page se
                // font là-dessus, jamais sur la colonne affichée, qui inclut
                // déjà les déclinaisons.
                'own_value' => $etat->valeur($id),
                'last_unit_cost' => $derniersCouts->has($id) ? (float) $derniersCouts->get($id)->unit_cost : null,
                'unvalued_entries' => (int) ($sansCout[$id] ?? 0),
            ];
        });

        return Inertia::render('Stock/Index', [
            'products' => $rows,
            // ⚠️ Sur la valeur propre : sommer la colonne affichée compterait
            // chaque déclinaison deux fois, une dans sa ligne et une dans celle
            // de sa famille.
            'total_value' => round($rows->sum('own_value'), 2),
            'low_count' => $rows->where('is_low', true)->count(),
        ]);
    }

    /**
     * Noms des champs dans les messages d'erreur : sans eux, Laravel écrit
     * « Le champ unit cost est obligatoire ».
     *
     * @return array<string, string>
     */
    private function nomsDesChamps(): array
    {
        return [
            'quantity' => __('app.stock.quantity'),
            'counted_quantity' => __('app.stock.counted_quantity'),
            'unit_cost' => __('app.stock.unit_cost'),
            'date' => __('app.date'),
            'note' => __('app.stock.note'),
            'product_ids' => __('app.stock.product'),
        ];
    }

    /**
     * Messages qui, sans cela, laisseraient un mot technique à l'écran
     * (« antérieure ou égale au today »).
     *
     * @return array<string, string>
     */
    private function messagesDeValidation(): array
    {
        return [
            'date.before_or_equal' => __('app.stock.date_in_future'),
        ];
    }

    public function storeEntry(Request $request, Product $product): RedirectResponse
    {
        // Une famille peut ne pas suivre son propre stock tout en portant des
        // déclinaisons qui le suivent : c'est même le cas normal.
        $aDesVariantesSuivies = $product->variants()->where('track_stock', true)->exists();

        abort_unless($product->track_stock || $aDesVariantesSuivies, 404);

        $data = $request->validate([
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
            // Ventilation facultative : on saisit d'abord ce qu'on a reçu, on
            // le répartit ensuite si on veut tenir le stock à la déclinaison.
            'allocations' => ['nullable', 'array'],
            'allocations.*.product_id' => ['required', 'integer'],
            'allocations.*.quantity' => ['nullable', 'numeric', 'min:0'],
            'allocations.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ], $this->messagesDeValidation(), $this->nomsDesChamps());

        $total = (float) $data['quantity'];

        $repartition = collect($data['allocations'] ?? [])
            ->filter(fn ($ligne) => (float) ($ligne['quantity'] ?? 0) > 0)
            ->values();

        if ($repartition->isEmpty()) {
            // Rien de ventilé : tout reste sur l'article, comme avant. C'est le
            // choix de qui ne veut pas tenir le détail par déclinaison.
            abort_unless($product->track_stock, 404);

            $this->stock->recordEntry(
                $product,
                $total,
                isset($data['unit_cost']) ? (float) $data['unit_cost'] : null,
                $data['date'],
                $data['note'] ?? null,
            );

            return back()->with('success', __('app.stock.flash_entry_recorded'));
        }

        return $this->entreeRepartie($product, $repartition, $total, $data);
    }

    /**
     * Une réception ventilée entre les déclinaisons d'une même famille.
     *
     * Le reliquat, s'il en reste, va sur la famille : on reçoit parfois un
     * carton dont on ne connaît pas encore le détail. Il apparaît comme « non
     * ventilé » et se répartira plus tard.
     *
     * Deux filets, et ils ne couvrent pas la même chose. La boucle de contrôle
     * s'exécute d'abord : une ligne fautive arrête tout avant la moindre
     * écriture, c'est ce que vérifie le test. La transaction couvre ce que le
     * contrôle ne peut pas prévoir — une erreur de base au troisième
     * mouvement, qui laisserait les deux premiers posés.
     *
     * @param  Collection<int, array<string, mixed>>  $repartition
     */
    private function entreeRepartie(Product $famille, $repartition, float $total, array $data): RedirectResponse
    {
        // Le scope global limite déjà au compte courant : une déclinaison d'un
        // autre utilisateur est introuvable, donc absente de cette liste.
        $variantes = $famille->variants()->where('track_stock', true)->get()->keyBy('id');

        foreach ($repartition as $ligne) {
            if (! $variantes->has((int) $ligne['product_id'])) {
                abort(404);
            }
        }

        $reparti = round($repartition->sum(fn ($l) => (float) $l['quantity']), 4);
        $reliquat = round($total - $reparti, 4);

        if ($reliquat < 0) {
            return back()->withErrors([
                'quantity' => __('app.stock.allocation_over', [
                    'allocated' => $reparti,
                    'total' => $total,
                ]),
            ]);
        }

        // Le reliquat n'a nulle part où aller si la famille ne suit pas son
        // propre stock : mieux vaut le dire que de le perdre en silence.
        if ($reliquat > 0 && ! $famille->track_stock) {
            return back()->withErrors([
                'quantity' => __('app.stock.allocation_incomplete', ['remainder' => $reliquat]),
            ]);
        }

        $coutCommun = isset($data['unit_cost']) ? (float) $data['unit_cost'] : null;

        DB::transaction(function () use ($repartition, $variantes, $data, $famille, $reliquat, $coutCommun) {
            foreach ($repartition as $ligne) {
                // Un coût propre à la déclinaison l'emporte : une taille XL ne
                // s'achète pas au prix d'une S.
                $cout = isset($ligne['unit_cost']) && $ligne['unit_cost'] !== null && $ligne['unit_cost'] !== ''
                    ? (float) $ligne['unit_cost']
                    : $coutCommun;

                $this->stock->recordEntry(
                    $variantes->get((int) $ligne['product_id']),
                    (float) $ligne['quantity'],
                    $cout,
                    $data['date'],
                    $data['note'] ?? null,
                );
            }

            if ($reliquat > 0) {
                $this->stock->recordEntry($famille, $reliquat, $coutCommun, $data['date'], $data['note'] ?? null);
            }
        });

        return back()->with('success', $reliquat > 0
            ? __('app.stock.flash_entry_allocated_partial', [
                'count' => $repartition->count(),
                'allocated' => $reparti,
                'remainder' => $reliquat,
            ])
            : __('app.stock.flash_entry_allocated', [
                'count' => $repartition->count(),
                'total' => $reparti,
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
        ], $this->messagesDeValidation(), $this->nomsDesChamps());

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
    /**
     * L'historique d'un article, déclinaisons comprises.
     *
     * Sur une famille, l'écran réunit ce qui est entré et sorti de toutes ses
     * nuances : « combien de souris ai-je reçu ce mois-ci » ne se répond pas en
     * ouvrant quatre pages. Chaque ligne nomme l'article concerné, et le
     * tableau de tête donne le stock de chaque déclinaison.
     */
    public function movements(Product $product): Response
    {
        $variantes = $product->variants()->where('track_stock', true)->get();

        // Une famille peut ne pas suivre son propre stock tout en portant des
        // déclinaisons qui le suivent.
        abort_unless($product->track_stock || $variantes->isNotEmpty(), 404);

        $concernes = $variantes->pluck('id')->push($product->id)->all();
        $noms = $variantes->keyBy('id')->map(fn (Product $v) => $v->displayName());

        $movements = StockMovement::query()
            ->whereIn('product_id', $concernes)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(200)
            ->get(['id', 'product_id', 'quantity', 'type', 'source_type', 'date', 'unit_cost', 'note'])
            ->map(fn ($m) => [
                'id' => $m->id,
                // Le mouvement porte son propre article : la suppression doit
                // viser la déclinaison, pas la famille qu'on regarde.
                'product_id' => (int) $m->product_id,
                'product_name' => $noms->get((int) $m->product_id) ?? $product->displayName(),
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
                'designation' => $product->displayName(),
                'current_stock' => $product->currentStock(),
                'total_stock' => $product->stockDeLaFamille(),
                'total_value' => $product->valeurDeLaFamille(),
            ],
            'variants' => $variantes->map(fn (Product $v) => [
                'id' => $v->id,
                'designation' => $v->variant_label,
                'reference' => $v->reference,
                'current_stock' => $v->currentStock(),
                'stock_value' => $v->stockValue(),
                'is_low' => $v->isLowOnStock(),
            ])->values(),
            'movements' => $movements,
            // Entrées manuelles sans coût sur l'article et ses déclinaisons : le
            // bouton « Valoriser » n'apparaît que s'il a quelque chose à faire.
            'unvalued_count' => StockMovement::query()
                ->whereIn('product_id', $concernes)
                ->where('type', StockMovement::TYPE_ENTREE)
                ->whereNull('unit_cost')
                ->whereNull('source_type')
                ->count(),
        ]);
    }

    /**
     * Corriger un mouvement saisi à la main : quantité, coût, date, note.
     *
     * Retour de terrain (2026-09-23) : se tromper obligeait à effacer et
     * ressaisir. Même règle que la suppression : un mouvement issu d'une
     * facture ou d'une dépense reste immuable, comme sa source. La quantité
     * d'un ajustement vient d'un comptage : on ne la retouche pas, on refait
     * un inventaire, qui recalcule l'écart.
     */
    public function updateMovement(Request $request, Product $product, StockMovement $movement): RedirectResponse
    {
        abort_unless((int) $movement->product_id === (int) $product->id, 404);
        abort_unless($movement->source_type === null, 403);

        $estUneEntree = $movement->type === StockMovement::TYPE_ENTREE;

        $data = $request->validate($estUneEntree ? [
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ] : [
            'date' => ['required', 'date', 'before_or_equal:today'],
            'note' => ['nullable', 'string', 'max:255'],
        ], $this->messagesDeValidation(), $this->nomsDesChamps());

        $movement->update($estUneEntree ? [
            'quantity' => abs((float) $data['quantity']),
            'unit_cost' => isset($data['unit_cost']) && $data['unit_cost'] !== '' ? (float) $data['unit_cost'] : null,
            'date' => $data['date'],
            'note' => $data['note'] ?? null,
        ] : [
            'date' => $data['date'],
            'note' => $data['note'] ?? null,
        ]);

        return back()->with('success', __('app.stock.flash_movement_updated'));
    }

    /**
     * Donner un coût unitaire, d'un coup, aux entrées manuelles qui n'en ont
     * pas, pour exactement les articles cochés. C'est l'écran qui décide de la
     * famille (cocher une famille coche ses déclinaisons sans coût, FEAT-129) :
     * une déclinaison au coût différent se décoche et se valorise à part. Les
     * coûts déjà saisis ne bougent pas : pour eux, la correction ligne à ligne.
     */
    public function valueEntries(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer'],
            'unit_cost' => ['required', 'numeric', 'min:0'],
        ], $this->messagesDeValidation(), $this->nomsDesChamps());

        // Le scope global limite au compte courant : un article d'un autre
        // compte est simplement absent.
        $articles = Product::whereIn('id', $data['product_ids'])->get(['id']);
        abort_if($articles->isEmpty(), 404);

        $valorisees = StockMovement::query()
            ->whereIn('product_id', $articles->pluck('id')->all())
            ->where('type', StockMovement::TYPE_ENTREE)
            ->whereNull('unit_cost')
            ->whereNull('source_type')
            ->update(['unit_cost' => (float) $data['unit_cost']]);

        return back()->with('success', __('app.stock.flash_entries_valued', [
            'count' => $valorisees,
            'cost' => number_format((float) $data['unit_cost'], 2, ',', ' ').' €',
        ]));
    }

    public function destroyMovement(Product $product, StockMovement $movement): RedirectResponse
    {
        abort_unless((int) $movement->product_id === (int) $product->id, 404);

        // Seuls les mouvements sans source (saisis à la main) sont supprimables.
        abort_unless($movement->source_type === null, 403);

        $movement->delete();

        return back()->with('success', __('app.stock.flash_movement_deleted'));
    }
}
