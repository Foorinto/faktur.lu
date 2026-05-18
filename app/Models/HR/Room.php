<?php

namespace App\Models\HR;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    use HasFactory, BelongsToUser;

    protected $fillable = [
        'user_id',
        'name',
        'capacity',
        'location',
        'equipment',
        'active',
    ];

    protected $casts = [
        'equipment' => 'array',
        'capacity' => 'integer',
        'active' => 'boolean',
    ];

    public function events(): HasMany
    {
        return $this->hasMany(HrEvent::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Check if the room has a conflict with an event on the given time range.
     * Excludes a specific event ID (used during updates).
     */
    public function hasConflict(\DateTimeInterface $startsAt, \DateTimeInterface $endsAt, ?int $excludeEventId = null): bool
    {
        return $this->events()
            ->when($excludeEventId, fn ($q) => $q->where('id', '!=', $excludeEventId))
            ->where(function ($q) use ($startsAt, $endsAt) {
                $q->whereBetween('starts_at', [$startsAt, $endsAt])
                  ->orWhereBetween('ends_at', [$startsAt, $endsAt])
                  ->orWhere(function ($inner) use ($startsAt, $endsAt) {
                      $inner->where('starts_at', '<=', $startsAt)
                            ->where('ends_at', '>=', $endsAt);
                  });
            })
            ->exists();
    }
}
