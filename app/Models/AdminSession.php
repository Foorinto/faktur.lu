<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id',
        'ip_address',
        'user_agent',
        'last_activity',
        'two_factor_confirmed',
        'created_at',
    ];

    protected $casts = [
        'last_activity' => 'datetime',
        'created_at' => 'datetime',
        'two_factor_confirmed' => 'boolean',
    ];

    /**
     * Check if the session has expired.
     */
    public function isExpired(): bool
    {
        $lifetime = (int) config('admin.session_lifetime', 30);
        return $this->last_activity->addMinutes($lifetime)->isPast();
    }

    /**
     * Update the last activity timestamp.
     */
    public function touchActivity(): bool
    {
        $this->last_activity = now();
        return $this->save();
    }

    /**
     * Scope to get only valid (non-expired) sessions.
     */
    public function scopeValid($query)
    {
        $lifetime = (int) config('admin.session_lifetime', 30);
        return $query->where('last_activity', '>=', now()->subMinutes($lifetime));
    }
}
