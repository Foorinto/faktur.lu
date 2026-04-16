<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DripEmail extends Model
{
    protected $fillable = [
        'user_id',
        'email_key',
        'status',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function wasSent(int $userId, string $emailKey): bool
    {
        return static::where('user_id', $userId)
            ->where('email_key', $emailKey)
            ->where('status', 'sent')
            ->exists();
    }

    public static function record(int $userId, string $emailKey, string $status = 'sent'): static
    {
        return static::create([
            'user_id' => $userId,
            'email_key' => $emailKey,
            'status' => $status,
            'sent_at' => now(),
        ]);
    }
}
