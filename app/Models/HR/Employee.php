<?php

namespace App\Models\HR;

use App\Traits\Auditable;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser, Auditable;

    public const STATUSES = ['active', 'long_leave', 'terminated'];
    public const CONTRACT_TYPES = ['CDI', 'CDD', 'Stage', 'Freelance'];

    protected $fillable = [
        'photo_path',
        'first_name',
        'last_name',
        'email_pro',
        'email_perso',
        'phone',
        'phone_perso',
        'birth_date',
        'nationality',
        'address',
        'city',
        'postal_code',
        'country',
        'contract_type',
        'contract_start',
        'contract_end',
        'trial_end_date',
        'job_title',
        'department_id',
        'manager_id',
        'work_location',
        'salary_gross',
        'salary_currency',
        'benefits',
        'bank_iban',
        'emergency_contact',
        'status',
        'termination_date',
        'termination_reason',
    ];

    protected $casts = [
        'birth_date' => 'date:Y-m-d',
        'contract_start' => 'date:Y-m-d',
        'contract_end' => 'date:Y-m-d',
        'trial_end_date' => 'date:Y-m-d',
        'termination_date' => 'date:Y-m-d',
        'salary_gross' => 'decimal:2',
        'benefits' => 'array',
        'emergency_contact' => 'array',
    ];

    protected $appends = ['full_name'];

    // Date fields stored as pure dates (no timezone conversion)
    private const DATE_ONLY_FIELDS = ['birth_date', 'contract_start', 'contract_end', 'trial_end_date', 'termination_date'];

    public function setAttribute($key, $value)
    {
        if (in_array($key, self::DATE_ONLY_FIELDS) && $value !== null && $value !== '') {
            $this->attributes[$key] = \Carbon\Carbon::parse($value)->format('Y-m-d');
            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    // Accessors

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->first_name . ' ' . $this->last_name,
        );
    }

    // Relationships

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'manager_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'manager_id');
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(EmployeeDocument::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    // Scopes

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email_pro', 'like', "%{$search}%")
              ->orWhere('job_title', 'like', "%{$search}%");
        });
    }

    public function scopeOfStatus(Builder $query, ?string $status): Builder
    {
        return $status ? $query->where('status', $status) : $query;
    }

    public function scopeOfDepartment(Builder $query, ?int $departmentId): Builder
    {
        return $departmentId ? $query->where('department_id', $departmentId) : $query;
    }

    public function scopeOfContractType(Builder $query, ?string $contractType): Builder
    {
        return $contractType ? $query->where('contract_type', $contractType) : $query;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    // Helpers

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isOnTrial(): bool
    {
        return $this->trial_end_date && $this->trial_end_date->isFuture();
    }
}
