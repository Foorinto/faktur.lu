<?php

namespace App\Models\HR;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseReport extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser;

    public const STATUSES = ['pending', 'approved', 'rejected'];

    public const PAYMENT_METHODS = ['card', 'cash', 'transfer'];

    protected $fillable = [
        'employee_id',
        'expense_category_id',
        'date',
        'description',
        'vendor',
        'amount_ht',
        'vat_rate',
        'amount_vat',
        'amount_ttc',
        'payment_method',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'amount_ht' => 'decimal:4',
        'vat_rate' => 'decimal:2',
        'amount_vat' => 'decimal:4',
        'amount_ttc' => 'decimal:4',
        'approved_at' => 'datetime',
    ];

    private const DATE_ONLY_FIELDS = ['date'];

    public function setAttribute($key, $value)
    {
        if (in_array($key, self::DATE_ONLY_FIELDS) && $value !== null && $value !== '') {
            $this->attributes[$key] = \Carbon\Carbon::parse($value)->format('Y-m-d');
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    protected static function booted(): void
    {
        static::saving(function (ExpenseReport $expense) {
            if ($expense->amount_ht !== null && $expense->vat_rate !== null) {
                $expense->amount_vat = round($expense->amount_ht * $expense->vat_rate / 100, 4);
                $expense->amount_ttc = round($expense->amount_ht + $expense->amount_vat, 4);
            }
        });
    }

    // Relationships

    public function receipts(): HasMany
    {
        return $this->hasMany(ExpenseReceipt::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
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

    public function scopeOfEmployee(Builder $query, ?int $employeeId): Builder
    {
        return $employeeId ? $query->where('employee_id', $employeeId) : $query;
    }

    public function scopeOfCategory(Builder $query, ?int $categoryId): Builder
    {
        return $categoryId ? $query->where('expense_category_id', $categoryId) : $query;
    }

    public function scopeOfDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn ($q) => $q->where('date', '>=', $from))
            ->when($to, fn ($q) => $q->where('date', '<=', $to));
    }

    // Methods

    public function approve(?int $approvedByEmployeeId = null): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $approvedByEmployeeId,
            'approved_at' => now(),
        ]);
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
