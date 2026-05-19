<?php

namespace App\Models;

use App\Models\HR\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectEmployee extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'employee_id',
        'active',
        'added_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'added_at' => 'datetime',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
