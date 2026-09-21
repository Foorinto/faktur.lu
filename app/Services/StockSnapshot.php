<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Collection;

/**
 * L'état du stock d'un lot d'articles, en trois requêtes quel que soit le
 * nombre d'articles.
 *
 * ⚠️ Pourquoi cette classe existe. `Product::currentStock()` et
 * `stockValue()` interrogent la base à chaque appel : parfait pour un article,
 * ruineux pour une page qui en liste cent. Les variantes ont aggravé le
 * problème, puisque le total d'une famille appelle le stock de chacune de ses
 * nuances. La page Stock était passée de 34 à 81 requêtes pour neuf articles,
 * et les alertes du tableau de bord de 8 à 22.
 *
 * Les règles de calcul sont volontairement identiques à celles du modèle, et
 * `StockSnapshotAgreesWithProductTest` le vérifie : deux chemins qui
 * divergeraient afficheraient deux stocks différents pour le même article.
 */
class StockSnapshot
{
    /** @var array<int, float> stock propre par identifiant d'article */
    private array $stocks = [];

    /** @var array<int, float> coût moyen pondéré par identifiant d'article */
    private array $couts = [];

    /** @var array<int, array<int>> identifiants des déclinaisons, par famille */
    private array $declinaisons = [];

    /**
     * @param  Collection<int, Product>  $produits
     */
    public function __construct(Collection $produits)
    {
        $ids = $produits->pluck('id')->map(fn ($id) => (int) $id);

        if ($ids->isEmpty()) {
            return;
        }

        // Les déclinaisons des articles donnés, même celles absentes de la
        // liste : le total d'une famille les couvre toutes, sans quoi une
        // alerte de seuil se déclencherait sur un stock incomplet.
        Product::query()
            ->whereIn('parent_id', $ids)
            ->get(['id', 'parent_id'])
            ->each(function ($v) {
                $this->declinaisons[(int) $v->parent_id][] = (int) $v->id;
            });

        $tous = $ids->concat(collect($this->declinaisons)->flatten())->unique()->values()->all();

        // Un GROUP BY plutôt qu'une somme par article.
        StockMovement::query()
            ->selectRaw('product_id, SUM(quantity) as total')
            ->whereIn('product_id', $tous)
            ->groupBy('product_id')
            ->get()
            ->each(fn ($l) => $this->stocks[(int) $l->product_id] = (float) $l->total);

        // Le coût moyen pondéré ne compte que les entrées valorisées, comme
        // `Product::weightedAverageCost()`.
        StockMovement::query()
            ->selectRaw('product_id, SUM(quantity) as qte, SUM(quantity * unit_cost) as cout')
            ->where('type', StockMovement::TYPE_ENTREE)
            ->whereNotNull('unit_cost')
            ->whereIn('product_id', $tous)
            ->groupBy('product_id')
            ->get()
            ->each(function ($l) {
                $qte = (float) $l->qte;
                $this->couts[(int) $l->product_id] = $qte > 0 ? round((float) $l->cout / $qte, 4) : 0.0;
            });
    }

    /** Le stock de l'article lui-même, hors déclinaisons. */
    public function stock(int $productId): float
    {
        return $this->stocks[$productId] ?? 0.0;
    }

    /** La valeur de l'article lui-même, hors déclinaisons. */
    public function valeur(int $productId): float
    {
        return round($this->stock($productId) * ($this->couts[$productId] ?? 0.0), 2);
    }

    /** @return array<int> les déclinaisons connues de cette famille */
    public function declinaisonsDe(int $productId): array
    {
        return $this->declinaisons[$productId] ?? [];
    }

    public function estUneFamille(int $productId): bool
    {
        return $this->declinaisonsDe($productId) !== [];
    }

    /** Le stock de tout ce que la famille couvre : le sien et celui de ses nuances. */
    public function stockDeLaFamille(int $productId): float
    {
        $total = $this->stock($productId);

        foreach ($this->declinaisonsDe($productId) as $id) {
            $total += $this->stock($id);
        }

        return round($total, 4);
    }

    public function valeurDeLaFamille(int $productId): float
    {
        $total = $this->valeur($productId);

        foreach ($this->declinaisonsDe($productId) as $id) {
            $total += $this->valeur($id);
        }

        return round($total, 2);
    }

    /**
     * Sous le seuil d'alerte. Même règle que `Product::isLowOnStock()` : une
     * famille se juge sur tout ce qu'elle couvre.
     */
    public function estSousLeSeuil(Product $produit): bool
    {
        if (! $produit->track_stock || $produit->stock_alert_threshold === null) {
            return false;
        }

        $id = (int) $produit->id;

        $stock = $this->estUneFamille($id) ? $this->stockDeLaFamille($id) : $this->stock($id);

        return $stock <= (float) $produit->stock_alert_threshold;
    }
}
