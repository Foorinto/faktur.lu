<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Interaction extends Model
{
    use HasFactory, BelongsToUser, Auditable;

    protected $fillable = [
        'client_id',
        'type',
        'content',
        'contacted_at',
    ];

    protected $casts = [
        'contacted_at' => 'datetime',
    ];

    public const TYPES = ['note', 'call', 'email', 'meeting', 'other'];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function scopeLatest(Builder $query): Builder
    {
        return $query->orderByDesc('contacted_at');
    }
}
