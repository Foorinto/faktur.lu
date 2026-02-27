<?php

namespace App\Models\HR;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OnboardingTemplate extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'name',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(OnboardingTemplateItem::class)->orderBy('position');
    }
}
