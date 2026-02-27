<?php

namespace App\Models\HR;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'name',
        'color',
        'icon',
        'max_amount',
        'is_active',
    ];

    protected $casts = [
        'max_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function expenseReports(): HasMany
    {
        return $this->hasMany(ExpenseReport::class);
    }
}
