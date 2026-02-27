<?php

namespace App\Models\HR;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'initial_days',
        'used_days',
    ];

    protected $casts = [
        'year' => 'integer',
        'initial_days' => 'decimal:1',
        'used_days' => 'decimal:1',
    ];

    protected $appends = ['remaining_days'];

    protected function remainingDays(): Attribute
    {
        return Attribute::make(
            get: fn () => round($this->initial_days - $this->used_days, 1),
        );
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
