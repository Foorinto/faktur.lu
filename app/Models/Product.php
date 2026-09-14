<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A reusable catalogue item (product or service) — FEAT-095.
 * Owned by a user; inserted into invoice/quote/recurring lines to avoid re-typing.
 */
class Product extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser;

    public const TYPE_PRODUCT = 'product';

    public const TYPE_SERVICE = 'service';

    public const TYPES = [self::TYPE_PRODUCT, self::TYPE_SERVICE];

    protected $fillable = [
        'designation',
        'description',
        'reference',
        'type',
        'unit_price_ht',
        'vat_rate',
        'pcn_account',
        'unit',
        'is_active',
        'track_stock',
        'stock_alert_threshold',
    ];

    protected $casts = [
        'unit_price_ht' => 'decimal:4',
        'vat_rate' => 'decimal:2',
        'is_active' => 'boolean',
        'track_stock' => 'boolean',
        'stock_alert_threshold' => 'decimal:4',
    ];

    /**
     * Scope to only active catalogue items.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Filtre par famille d'article.
     *
     * `unclassified` vise les articles antérieurs à l'introduction du champ :
     * ils portent `null` et doivent rester atteignables, sans quoi ils
     * disparaîtraient de la liste dès qu'un filtre est actif.
     */
    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        if ($type === null || $type === '') {
            return $query;
        }

        if ($type === 'unclassified') {
            return $query->whereNull('type');
        }

        return $query->where('type', $type);
    }

    /**
     * Seuls les articles de type « produit » peuvent être suivis en stock.
     *
     * Une prestation de service n'a pas de stock : la garder suivable
     * n'aurait pas de sens et polluerait les alertes.
     */
    public function canTrackStock(): bool
    {
        return $this->type === self::TYPE_PRODUCT;
    }

    /**
     * Mouvements de stock du produit (FEAT-116).
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Stock courant : la somme des quantités signées, éventuellement arrêtée à
     * une date. Le stock est un journal, jamais un compteur mutable.
     */
    public function currentStock(?string $asOfDate = null): float
    {
        return (float) $this->stockMovements()
            ->when($asOfDate, fn ($q) => $q->whereDate('date', '<=', $asOfDate))
            ->sum('quantity');
    }

    /**
     * Le produit est-il sous son seuil d'alerte ?
     *
     * Sans suivi ni seuil, jamais d'alerte : on ne réclame l'attention que
     * lorsque l'utilisateur l'a explicitement demandée en posant un seuil.
     */
    public function isLowOnStock(): bool
    {
        if (! $this->track_stock || $this->stock_alert_threshold === null) {
            return false;
        }

        return $this->currentStock() <= (float) $this->stock_alert_threshold;
    }

    /**
     * Coût moyen pondéré des entrées, arrêté à une date.
     *
     * CMP = coût total des entrées / quantité totale entrée. Retenu au
     * Luxembourg et bien plus simple que FIFO. Zéro s'il n'y a aucune entrée
     * valorisée.
     */
    public function weightedAverageCost(?string $asOfDate = null): float
    {
        $entries = $this->stockMovements()
            ->where('type', StockMovement::TYPE_ENTREE)
            ->whereNotNull('unit_cost')
            ->when($asOfDate, fn ($q) => $q->whereDate('date', '<=', $asOfDate))
            ->get(['quantity', 'unit_cost']);

        $totalQty = (float) $entries->sum('quantity');

        if ($totalQty <= 0) {
            return 0.0;
        }

        $totalCost = $entries->reduce(
            fn ($carry, $m) => $carry + ((float) $m->quantity * (float) $m->unit_cost),
            0.0
        );

        return round($totalCost / $totalQty, 4);
    }

    /**
     * Valeur du stock à une date : quantité courante au coût moyen pondéré.
     *
     * C'est une donnée comptable (stock à la clôture), pas un simple affichage.
     */
    public function stockValue(?string $asOfDate = null): float
    {
        return round($this->currentStock($asOfDate) * $this->weightedAverageCost($asOfDate), 2);
    }
}
