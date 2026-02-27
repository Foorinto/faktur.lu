<?php

namespace App\Models\HR;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'name',
        'color',
        'default_days_per_year',
        'requires_justification',
        'is_paid',
    ];

    protected $casts = [
        'default_days_per_year' => 'integer',
        'requires_justification' => 'boolean',
        'is_paid' => 'boolean',
    ];

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
