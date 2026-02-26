<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reminder extends Model
{
    use HasFactory, BelongsToUser, Auditable;

    protected $fillable = [
        'client_id',
        'title',
        'description',
        'remind_at',
        'is_completed',
    ];

    protected $casts = [
        'remind_at' => 'datetime',
        'is_completed' => 'boolean',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('is_completed', false)
            ->where('remind_at', '>=', now())
            ->orderBy('remind_at');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->where('is_completed', false)
            ->where('remind_at', '<', now())
            ->orderBy('remind_at');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('is_completed', true)
            ->orderByDesc('remind_at');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('is_completed', false)
            ->orderBy('remind_at');
    }
}
