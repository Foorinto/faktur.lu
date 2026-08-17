<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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
    ];

    protected $casts = [
        'unit_price_ht' => 'decimal:4',
        'vat_rate' => 'decimal:2',
        'is_active' => 'boolean',
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
}
