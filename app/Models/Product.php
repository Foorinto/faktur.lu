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

    protected $fillable = [
        'designation',
        'description',
        'reference',
        'unit_price_ht',
        'vat_rate',
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
}
