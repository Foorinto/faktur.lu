<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeEntry extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser, Auditable;

    protected $fillable = [
        'client_id',
        'project_id',
        'task_id',
        'invoice_id',
        'project_name',
        'description',
        'started_at',
        'stopped_at',
        'duration_seconds',
        'hourly_rate',
        'is_billed',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'duration_seconds' => 'integer',
        'hourly_rate' => 'decimal:4',
        'is_billed' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Calculate duration when stopping timer
        static::saving(function (TimeEntry $entry) {
            if ($entry->started_at && $entry->stopped_at && $entry->isDirty('stopped_at')) {
                $entry->duration_seconds = $entry->started_at->diffInSeconds($entry->stopped_at);
            }
        });
    }

    /**
     * Get the client that owns the time entry.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the invoice associated with this time entry.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the project associated with this time entry.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the task associated with this time entry.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Check if the timer is running.
     */
    public function isRunning(): bool
    {
        return $this->started_at !== null && $this->stopped_at === null;
    }

    /**
     * Get the duration formatted as hours:minutes:seconds.
     */
    public function getDurationFormattedAttribute(): string
    {
        $totalSeconds = $this->getCurrentDurationSeconds();
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;
        return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
    }

    /**
     * Get the duration in decimal hours.
     */
    public function getDurationHoursAttribute(): float
    {
        return round($this->getCurrentDurationSeconds() / 3600, 2);
    }

    /**
     * Get the current duration in seconds (including running time).
     */
    public function getCurrentDurationSeconds(): int
    {
        if ($this->isRunning()) {
            return $this->started_at->diffInSeconds(now());
        }
        return $this->duration_seconds ?? 0;
    }

    /**
     * Get the calculated amount based on duration and hourly rate.
     */
    public function getAmountAttribute(): float
    {
        if (!$this->hourly_rate) {
            return 0;
        }
        return round($this->duration_hours * (float) $this->hourly_rate, 2);
    }

    /**
     * Start the timer.
     */
    public function start(): self
    {
        $this->started_at = now();
        $this->stopped_at = null;
        $this->duration_seconds = 0;
        $this->save();

        return $this;
    }

    /**
     * Stop the timer.
     */
    public function stop(): self
    {
        if ($this->isRunning()) {
            $this->stopped_at = now();
            $this->duration_seconds = $this->started_at->diffInSeconds($this->stopped_at);
            $this->save();
        }

        return $this;
    }

    /**
     * Mark as billed.
     */
    public function markAsBilled(Invoice $invoice): self
    {
        $this->invoice_id = $invoice->id;
        $this->is_billed = true;
        $this->save();

        return $this;
    }

    /**
     * Get the effective hourly rate (own, from client, or from global settings).
     */
    public function getEffectiveHourlyRate(): ?float
    {
        // 1. Own rate on time entry
        if ($this->hourly_rate) {
            return (float) $this->hourly_rate;
        }

        // 2. Client's default rate
        if ($this->client && $this->client->default_hourly_rate) {
            return (float) $this->client->default_hourly_rate;
        }

        // 3. Global default rate from business settings
        $settings = BusinessSettings::getInstance();
        if ($settings && $settings->default_hourly_rate) {
            return (float) $settings->default_hourly_rate;
        }

        return null;
    }

    /**
     * Scope for unbilled entries.
     */
    public function scopeUnbilled(Builder $query): Builder
    {
        return $query->where('is_billed', false);
    }

    /**
     * Scope for billed entries.
     */
    public function scopeBilled(Builder $query): Builder
    {
        return $query->where('is_billed', true);
    }

    /**
     * Scope for a specific client.
     */
    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope for entries between dates.
     */
    public function scopeBetween(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereDate('started_at', '>=', $startDate)
            ->whereDate('started_at', '<=', $endDate);
    }

    /**
     * Scope for running timers.
     */
    public function scopeRunning(Builder $query): Builder
    {
        return $query->whereNotNull('started_at')->whereNull('stopped_at');
    }

    /**
     * Scope for stopped entries.
     */
    public function scopeStopped(Builder $query): Builder
    {
        return $query->whereNotNull('stopped_at');
    }

    /**
     * Scope for this week.
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('started_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    /**
     * Scope for this month.
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('started_at', now()->month)
            ->whereYear('started_at', now()->year);
    }

    /**
     * Get summary statistics for a period.
     */
    public static function getSummary(?int $clientId = null, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = static::query()->stopped();

        if ($clientId) {
            $query->forClient($clientId);
        }

        if ($startDate && $endDate) {
            $query->between($startDate, $endDate);
        }

        $totalSeconds = $query->sum('duration_seconds');
        $unbilledSeconds = (clone $query)->unbilled()->sum('duration_seconds');
        $billedSeconds = (clone $query)->billed()->sum('duration_seconds');

        return [
            'total_seconds' => $totalSeconds,
            'total_formatted' => self::formatSeconds($totalSeconds),
            'total_hours' => round($totalSeconds / 3600, 2),
            'unbilled_seconds' => $unbilledSeconds,
            'unbilled_formatted' => self::formatSeconds($unbilledSeconds),
            'unbilled_hours' => round($unbilledSeconds / 3600, 2),
            'billed_seconds' => $billedSeconds,
            'billed_formatted' => self::formatSeconds($billedSeconds),
            'billed_hours' => round($billedSeconds / 3600, 2),
            'count' => $query->count(),
        ];
    }

    /**
     * Format seconds to hours:minutes:seconds.
     */
    public static function formatSeconds(int $seconds): string
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;
        return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
    }

    /**
     * Parse hours:minutes to seconds.
     */
    public static function parseToSeconds(string $duration): int
    {
        if (preg_match('/^(\d+):(\d{2})$/', $duration, $matches)) {
            return ((int) $matches[1] * 3600) + ((int) $matches[2] * 60);
        }

        // Try decimal hours (e.g., 1.5)
        if (is_numeric($duration)) {
            return (int) ((float) $duration * 3600);
        }

        return 0;
    }
}
