<?php

namespace App\Models\HR;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'employee_id',
        'evaluator_id',
        'title',
        'description',
        'date',
        'acknowledged_at',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'acknowledged_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'evaluator_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class, 'evaluation_id');
    }
}
