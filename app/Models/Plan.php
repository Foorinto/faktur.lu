<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
        'price_monthly',
        'price_yearly',
        'stripe_price_id_monthly',
        'stripe_price_id_yearly',
        'limits',
        'features',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'limits' => 'array',
            'features' => 'array',
            'is_active' => 'boolean',
            'price_monthly' => 'integer',
            'price_yearly' => 'integer',
        ];
    }

    /**
     * Get the starter plan.
     */
    public static function starter(): ?self
    {
        return static::where('name', 'starter')->first();
    }

    /**
     * Get the pro plan.
     */
    public static function pro(): ?self
    {
        return static::where('name', 'pro')->first();
    }

    /**
     * Check if plan has a specific feature.
     */
    public function hasFeature(string $feature): bool
    {
        return in_array($feature, $this->features ?? []);
    }

    /**
     * Get a specific limit value.
     */
    public function getLimit(string $key): ?int
    {
        return $this->limits[$key] ?? null;
    }

    /**
     * Get monthly price in euros.
     */
    public function getPriceMonthlyEurosAttribute(): float
    {
        return $this->price_monthly / 100;
    }

    /**
     * Get yearly price in euros.
     */
    public function getPriceYearlyEurosAttribute(): float
    {
        return $this->price_yearly / 100;
    }

    /**
     * Get monthly price when paying yearly (with discount).
     */
    public function getMonthlyPriceWhenYearlyAttribute(): float
    {
        return round($this->price_yearly / 12 / 100, 2);
    }

    /**
     * Check if this is the free plan.
     */
    public function isFree(): bool
    {
        return $this->price_monthly === 0;
    }
}
