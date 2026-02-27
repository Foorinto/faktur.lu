<?php

namespace App\Models\HR;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnboardingTask extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'employee_id',
        'title',
        'completed_at',
        'position',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }
}
