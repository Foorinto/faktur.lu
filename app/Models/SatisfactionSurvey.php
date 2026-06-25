<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SatisfactionSurvey extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';

    /** NPS classification thresholds. */
    public const PROMOTER_MIN = 9;
    public const DETRACTOR_MAX = 6;

    protected $fillable = [
        'user_id',
        'token',
        'status',
        'nps_score',
        'comment',
        'sent_at',
        'completed_at',
        'expires_at',
    ];

    protected $casts = [
        'nps_score' => 'integer',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create a survey invitation for a user (one per user - see unique index).
     */
    public static function createForUser(User $user): self
    {
        return self::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'status' => self::STATUS_PENDING,
            'sent_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
    }

    public function isValid(): bool
    {
        return $this->status === self::STATUS_PENDING
            && $this->expires_at->isFuture();
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isExpired(): bool
    {
        return $this->status !== self::STATUS_COMPLETED
            && $this->expires_at->isPast();
    }

    public function markCompleted(int $npsScore, ?string $comment): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'nps_score' => $npsScore,
            'comment' => $comment,
            'completed_at' => now(),
        ]);
    }

    /**
     * Public URL of the survey form. The locale prefix drives the page language.
     */
    public function getSurveyUrl(string $locale): string
    {
        $locale = in_array($locale, ['fr', 'de', 'en', 'lb', 'pt'], true) ? $locale : 'fr';

        return route('survey.show', ['locale' => $locale, 'token' => $this->token]);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('expires_at', '>', now());
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }
}
