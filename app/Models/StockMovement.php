<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Un mouvement de stock (FEAT-116).
 *
 * Le stock est un journal : chaque entrée, sortie, ajustement ou inventaire est
 * une ligne immuable, et le stock courant est la somme des quantités signées.
 * On ne modifie ni ne supprime un mouvement passé — on en ajoute un inverse.
 */
class StockMovement extends Model
{
    use HasFactory, BelongsToUser;

    /** Entrée de marchandise (réception, achat) : quantité positive. */
    public const TYPE_ENTREE = 'entree';

    /** Sortie (vente à l'émission d'une facture) : quantité négative. */
    public const TYPE_SORTIE = 'sortie';

    /** Correction manuelle motivée : l'écart d'un inventaire. */
    public const TYPE_AJUSTEMENT = 'ajustement';

    /** Inventaire d'ouverture ou de recomptage : pose une quantité de départ. */
    public const TYPE_INVENTAIRE = 'inventaire';

    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
        'type',
        'source_type',
        'source_id',
        'date',
        'unit_cost',
        'note',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_cost' => 'decimal:4',
        'date' => 'date:Y-m-d',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Origine du mouvement (facture, note de crédit, dépense…), si elle existe.
     *
     * Relation polymorphe sans contrainte FK : la source peut disparaître sans
     * effacer l'historique du stock.
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }
}
