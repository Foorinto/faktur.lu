<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

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

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_HIGH = 'high';

    public const PRIORITIES = [
        self::PRIORITY_LOW => 'Basse',
        self::PRIORITY_NORMAL => 'Normale',
        self::PRIORITY_HIGH => 'Haute',
    ];

    protected $fillable = [
        'project_id',
        'parent_id',
        'depth',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
        'estimated_hours',
        'is_completed',
        'completed_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date:Y-m-d',
            'estimated_hours' => 'decimal:2',
            'is_completed' => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    // Relationships

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_id')->orderBy('sort_order');
    }

    public function subtasks(): HasMany
    {
        return $this->children();
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

    public function scopePriority(Builder $query, string|array|null $priority): Builder
    {
        if (empty($priority)) {
            return $query;
        }

        return $query->whereIn('priority', (array) $priority);
    }

    public function scopeCompleted(Builder $query, bool $completed = true): Builder
    {
        return $query->where('is_completed', $completed);
    }

    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->where('is_completed', false);
    }

    public function scopeForProject(Builder $query, int $projectId): Builder
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeRootTasks(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeSubtasksOf(Builder $query, int $parentId): Builder
    {
        return $query->where('parent_id', $parentId);
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNotNull('due_date')
            ->where('due_date', '<', now()->toDateString())
            ->where('is_completed', false);
    }

    // Computed Attributes

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

        if ($this->is_completed) {
            return false;
        }

        return $this->due_date->isPast();
    }

    public function getHasSubtasksAttribute(): bool
    {
        return $this->children()->exists();
    }

    public function getSubtasksCountAttribute(): int
    {
        return $this->children()->count();
    }

    public function getCompletedSubtasksCountAttribute(): int
    {
        return $this->children()->where('is_completed', true)->count();
    }

    public function getSubtaskProgressAttribute(): int
    {
        $total = $this->subtasks_count;
        if ($total === 0) {
            return 0;
        }
        return (int) round(($this->completed_subtasks_count / $total) * 100);
    }

    public function getIsRootTaskAttribute(): bool
    {
        return $this->parent_id === null;
    }

    // Helper Methods

    public function toggle(): void
    {
        if ($this->is_completed) {
            $this->markAsIncomplete();
        } else {
            $this->markAsComplete();
        }
    }

    public function markAsComplete(bool $cascade = true): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
            'status' => self::STATUS_DONE,
        ]);

        // Also complete all subtasks
        if ($cascade) {
            $this->children()->each(fn ($child) => $child->markAsComplete(true));
        }
    }

    public function markAsIncomplete(bool $cascade = false): void
    {
        $this->update([
            'is_completed' => false,
            'completed_at' => null,
            'status' => self::STATUS_BACKLOG,
        ]);

        // Optionally cascade to subtasks
        if ($cascade) {
            $this->children()->each(fn ($child) => $child->markAsIncomplete(true));
        }
    }

    /**
     * Create a subtask under this task.
     */
    public function addSubtask(array $data): Task
    {
        return $this->children()->create(array_merge($data, [
            'project_id' => $this->project_id,
            'depth' => $this->depth + 1,
            'sort_order' => $this->children()->max('sort_order') + 1,
        ]));
    }
}
