<?php

namespace App\Models\HR;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingTemplateItem extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'onboarding_template_id',
        'title',
        'position',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    public function template(): BelongsTo
    {
        return $this->belongsTo(OnboardingTemplate::class, 'onboarding_template_id');
    }
}
