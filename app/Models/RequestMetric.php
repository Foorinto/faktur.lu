<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequestMetric extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'url',
        'method',
        'response_time_ms',
        'memory_usage_mb',
        'query_count',
        'query_time_ms',
        'status_code',
        'user_id',
        'created_at',
    ];

    protected $casts = [
        'response_time_ms' => 'integer',
        'memory_usage_mb' => 'integer',
        'query_count' => 'integer',
        'query_time_ms' => 'integer',
        'status_code' => 'integer',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeSince($query, $date)
    {
        return $query->where('created_at', '>=', $date);
    }

    public function scopeErrors($query)
    {
        return $query->where('status_code', '>=', 500);
    }

    public function scopeSlow($query, int $thresholdMs = 1000)
    {
        return $query->where('response_time_ms', '>=', $thresholdMs);
    }
}
