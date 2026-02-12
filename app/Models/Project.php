<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser, Auditable;

    public const STATUS_BACKLOG = 'backlog';
    public const STATUS_NEXT = 'next';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_WAITING_FOR = 'waiting_for';
    public const STATUS_DONE = 'done';

    public const STATUSES = [
        self::STATUS_BACKLOG => 'Backlog',
        self::STATUS_NEXT => 'À faire',
        self::STATUS_IN_PROGRESS => 'En cours',
        self::STATUS_WAITING_FOR => 'En attente',
        self::STATUS_DONE => 'Terminé',
    ];

    public const COLORS = [
        '#9b5de5' => 'Violet',
        '#00bbf9' => 'Bleu',
        '#f15bb5' => 'Rose',
        '#00f5d4' => 'Turquoise',
        '#fee440' => 'Jaune',
        '#ff6b6b' => 'Rouge',
        '#4ecdc4' => 'Cyan',
        '#45b7d1' => 'Azur',
    ];

    protected $fillable = [
        'user_id',
        'client_id',
        'title',
        'description',
        'status',
        'color',
        'due_date',
        'budget_hours',
        'sort_order',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date:Y-m-d',
            'budget_hours' => 'decimal:2',
            'is_archived' => 'boolean',
        ];
    }

    // Relationships

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('sort_order');
    }

    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    // Scopes

    public function scopeStatus(Builder $query, string|array|null $status): Builder
    {
        if (empty($status)) {
            return $query;
        }

        return $query->whereIn('status', (array) $status);
    }

    public function scopeArchived(Builder $query, bool $archived = true): Builder
    {
        return $query->where('is_archived', $archived);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_archived', false);
    }

    public function scopeForClient(Builder $query, int|null $clientId): Builder
    {
        if (empty($clientId)) {
            return $query;
        }

        return $query->where('client_id', $clientId);
    }

    public function scopeSearch(Builder $query, string|null $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->whereNotIn('status', [self::STATUS_DONE]);
    }

    // Computed Attributes

    public function getCompletionPercentageAttribute(): int
    {
        $total = $this->tasks()->count();

        if ($total === 0) {
            return 0;
        }

        $completed = $this->tasks()->where('is_completed', true)->count();

        return (int) round(($completed / $total) * 100);
    }

    public function getTotalTimeSpentAttribute(): int
    {
        return $this->timeEntries()->sum('duration_seconds');
    }

    public function getTotalTimeSpentFormattedAttribute(): string
    {
        $seconds = $this->total_time_spent;
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);

        return sprintf('%dh%02d', $hours, $minutes);
    }

    public function getIsOverdueAttribute(): bool
    {
        if (empty($this->due_date)) {
            return false;
        }

        if ($this->status === self::STATUS_DONE) {
            return false;
        }

        return $this->due_date->isPast();
    }

    public function getTasksCountAttribute(): int
    {
        return $this->tasks()->count();
    }

    public function getCompletedTasksCountAttribute(): int
    {
        return $this->tasks()->where('is_completed', true)->count();
    }

    // Helper Methods

    public function canBeDeleted(): bool
    {
        return $this->timeEntries()->count() === 0;
    }

    public function archive(): void
    {
        $this->update(['is_archived' => true]);
    }

    public function unarchive(): void
    {
        $this->update(['is_archived' => false]);
    }

    public function markAsDone(): void
    {
        $this->update(['status' => self::STATUS_DONE]);
    }
}
