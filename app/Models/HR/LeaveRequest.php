<?php

namespace App\Models\HR;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class LeaveRequest extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser;

    public const STATUSES = ['pending', 'approved', 'rejected', 'cancelled'];

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days_count',
        'reason',
        'justification_path',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
        'days_count' => 'decimal:1',
        'approved_at' => 'datetime',
    ];

    private const DATE_ONLY_FIELDS = ['start_date', 'end_date'];

    public function setAttribute($key, $value)
    {
        if (in_array($key, self::DATE_ONLY_FIELDS) && $value !== null && $value !== '') {
            $this->attributes[$key] = \Carbon\Carbon::parse($value)->format('Y-m-d');
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    // Relationships

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'approved_by');
    }

    // Scopes

    public function scopeOfStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function scopeForEmployee(Builder $query, int $employeeId): Builder
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeOverlapping(Builder $query, $startDate, $endDate, ?int $excludeId = null): Builder
    {
        return $query->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate)
            ->whereNotIn('status', ['rejected', 'cancelled'])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId));
    }

    // Methods

    public function approve(?int $approvedByEmployeeId = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedByEmployeeId,
            'approved_at' => now(),
        ]);

        $this->updateBalance(add: true);
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    public function cancel(): void
    {
        $wasApproved = $this->status === 'approved';

        $this->update(['status' => 'cancelled']);

        if ($wasApproved) {
            $this->updateBalance(add: false);
        }
    }

    public function reactivate(): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $this->updateBalance(add: true);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    private function updateBalance(bool $add): void
    {
        $balance = LeaveBalance::firstOrCreate(
            [
                'employee_id' => $this->employee_id,
                'leave_type_id' => $this->leave_type_id,
                'year' => $this->start_date->year,
                'user_id' => $this->user_id,
            ],
            [
                'initial_days' => $this->leaveType->default_days_per_year ?? 0,
                'used_days' => 0,
            ]
        );

        if ($add) {
            $balance->increment('used_days', $this->days_count);
        } else {
            $balance->decrement('used_days', $this->days_count);
        }
    }
}
