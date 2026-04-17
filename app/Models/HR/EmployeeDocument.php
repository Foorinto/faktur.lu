<?php

namespace App\Models\HR;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDocument extends Model
{
    use BelongsToUser;

    public const TYPES = ['contract', 'amendment', 'payslip', 'id_card', 'certificate', 'evaluation', 'other'];

    protected $fillable = [
        'employee_id',
        'evaluation_id',
        'type',
        'name',
        'file_path',
        'original_name',
        'expiry_date',
        'notes',
    ];

    protected $casts = [
        'expiry_date' => 'date:Y-m-d',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class);
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }
}
