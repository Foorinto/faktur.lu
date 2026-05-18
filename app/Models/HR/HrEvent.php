<?php

namespace App\Models\HR;

use App\Traits\Auditable;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HrEvent extends Model
{
    use HasFactory, BelongsToUser, Auditable;

    public const TYPES = ['meeting', 'training', 'team_building', 'deadline', 'other'];
    public const LOCATION_TYPES = ['room', 'address', 'video', 'none'];

    public const TYPE_COLORS = [
        'meeting' => '#9b5de5',
        'training' => '#00bbf9',
        'team_building' => '#00f5d4',
        'deadline' => '#f15bb5',
        'other' => '#fee440',
    ];

    protected $fillable = [
        'user_id',
        'created_by_employee_id',
        'title',
        'type',
        'color',
        'starts_at',
        'ends_at',
        'is_all_day',
        'location_type',
        'room_id',
        'address',
        'video_url',
        'description',
        'recurrence_rule',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_all_day' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'created_by_employee_id');
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(HrEventParticipant::class);
    }

    public function getEffectiveColorAttribute(): string
    {
        return $this->color ?? self::TYPE_COLORS[$this->type] ?? '#9b5de5';
    }

    public function scopeInRange($query, \DateTimeInterface $start, \DateTimeInterface $end)
    {
        return $query->where(function ($q) use ($start, $end) {
            $q->whereBetween('starts_at', [$start, $end])
              ->orWhereBetween('ends_at', [$start, $end])
              ->orWhere(function ($inner) use ($start, $end) {
                  $inner->where('starts_at', '<=', $start)
                        ->where('ends_at', '>=', $end);
              });
        });
    }
}
