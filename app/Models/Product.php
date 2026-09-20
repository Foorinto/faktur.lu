<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A reusable catalogue item (product or service) — FEAT-095.
 * Owned by a user; inserted into invoice/quote/recurring lines to avoid re-typing.
 */
class Product extends Model
{
    use BelongsToUser, HasFactory, SoftDeletes;

    public const TYPE_PRODUCT = 'product';

    public const TYPE_SERVICE = 'service';

    public const TYPES = [self::TYPE_PRODUCT, self::TYPE_SERVICE];

    /** Le libellé d'axe par défaut, quand la famille n'en a pas choisi. */
    public const AXE_PAR_DEFAUT = 'variant';

    protected $fillable = [
        'parent_id',
        'designation',
        'variant_label',
        'variant_axis_label',
        'sort_order',
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
        'sort_order' => 'integer',
    ];

    // --- Variantes (FEAT-120) ------------------------------------------------

    /**
     * Les variantes de cette famille, dans l'ordre voulu.
     *
     * Ordonnées par `sort_order` et non par désignation : des tailles se lisent
     * S, M, L, XL, quand l'alphabet donnerait L, M, S, XL.
     */
    public function variants(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function isVariant(): bool
    {
        return $this->parent_id !== null;
    }

    /**
     * Une famille est un article qui porte au moins une variante.
     *
     * Se calcule, plutôt que de vivre dans un drapeau qui se désynchronise.
     * ⚠️ Ne jamais appeler à chaque frappe d'une recherche : la sélection des
     * documents filtre sur `parent_id`, qui est indexé.
     */
    public function isFamily(): bool
    {
        return $this->relationLoaded('variants')
            ? $this->variants->isNotEmpty()
            : $this->variants()->exists();
    }

    /**
     * Le nom tel qu'il doit apparaître sur un document.
     *
     * « Extensions GL30 — nuance 12 ». Sans la variante, le client ne peut ni
     * se faire livrer, ni échanger, ni vérifier sa commande.
     */
    public function displayName(): string
    {
        if (! $this->isVariant()) {
            return (string) $this->designation;
        }

        $famille = $this->parent?->designation ?? $this->designation;

        return trim($famille.' — '.$this->variant_label);
    }

    /**
     * L'axe que porte la famille : « Nuance », « Taille », « Format »…
     *
     * Remonté depuis le parent pour une variante, qui ne le porte pas.
     */
    public function axisLabel(): string
    {
        $porteur = $this->isVariant() ? $this->parent : $this;

        return $porteur?->variant_axis_label ?: __('app.products.variant_axis_default');
    }

    /**
     * Applique le prix, la TVA, l'unité et le compte comptable de la famille à
     * toutes ses variantes.
     *
     * La propagation plutôt que l'héritage : aucune lecture existante n'a à
     * résoudre quoi que ce soit vers un parent, et une variante peut garder un
     * prix propre tant qu'on ne propage pas.
     *
     * @return int le nombre de variantes touchées
     */
    public function propagateToVariants(): int
    {
        return $this->variants()->update([
            'unit_price_ht' => $this->unit_price_ht,
            'vat_rate' => $this->vat_rate,
            'unit' => $this->unit,
            'pcn_account' => $this->pcn_account,
            'type' => $this->type,
        ]);
    }

    /**
     * Les articles proposés à la saisie d'un document : familles et articles
     * ordinaires, jamais une variante nue.
     *
     * Une égalité sur colonne indexée, sans sous-requête : taper « GL30 »
     * renvoie une ligne au lieu de quarante-cinq. La recherche est donc plus
     * légère qu'avant les variantes.
     */
    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeVariantsOnly(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
    }

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
