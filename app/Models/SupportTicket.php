<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'user_id',
        'assigned_admin_id',
        'subject',
        'category',
        'status',
        'priority',
        'first_response_at',
        'resolved_at',
        'user_last_read_at',
    ];

    protected function casts(): array
    {
        return [
            'first_response_at' => 'datetime',
            'resolved_at' => 'datetime',
            'user_last_read_at' => 'datetime',
        ];
    }

    /**
     * Available categories.
     */
    public const CATEGORIES = [
        'general' => 'Question générale',
        'technical' => 'Problème technique',
        'billing' => 'Facturation',
        'suggestion' => 'Suggestion',
        'other' => 'Autre',
    ];

    /**
     * Available statuses.
     */
    public const STATUSES = [
        'new' => 'Nouveau',
        'open' => 'Ouvert',
        'in_progress' => 'En cours',
        'waiting' => 'En attente',
        'resolved' => 'Résolu',
        'closed' => 'Fermé',
    ];

    /**
     * Available priorities.
     */
    public const PRIORITIES = [
        'low' => 'Basse',
        'normal' => 'Normale',
        'high' => 'Haute',
        'urgent' => 'Urgente',
    ];

    /**
     * Generate a unique reference for a new ticket.
     */
    public static function generateReference(): string
    {
        $year = date('Y');
        $lastTicket = self::whereYear('created_at', $year)
            ->orderByDesc('id')
            ->first();

        $number = $lastTicket
            ? intval(substr($lastTicket->reference, -4)) + 1
            : 1;

        return sprintf('TKT-%s-%04d', $year, $number);
    }

    /**
     * Get the user who created the ticket.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all messages for this ticket.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'ticket_id');
    }

    /**
     * Get only public messages (not internal notes).
     */
    public function publicMessages(): HasMany
    {
        return $this->messages()->where('is_internal', false);
    }

    /**
     * Get the latest message.
     */
    public function latestMessage()
    {
        return $this->hasOne(SupportMessage::class, 'ticket_id')->latestOfMany();
    }

    /**
     * Check if the ticket is open for replies.
     */
    public function isOpen(): bool
    {
        return !in_array($this->status, ['resolved', 'closed']);
    }

    /**
     * Check if the ticket has unread admin messages for the user.
     */
    public function hasUnreadMessages(): bool
    {
        $lastRead = $this->user_last_read_at ?? $this->created_at;

        return $this->messages()
            ->where('is_internal', false)
            ->where('sender_type', '!=', User::class)
            ->where('created_at', '>', $lastRead)
            ->exists();
    }

    /**
     * Get the count of unread messages for the user.
     */
    public function getUnreadCountAttribute(): int
    {
        $lastRead = $this->user_last_read_at ?? $this->created_at;

        return $this->messages()
            ->where('is_internal', false)
            ->where('sender_type', '!=', User::class)
            ->where('created_at', '>', $lastRead)
            ->count();
    }

    /**
     * Mark the ticket as read by the user.
     */
    public function markAsRead(): void
    {
        $this->update(['user_last_read_at' => now()]);
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeStatus($query, ?string $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }

    /**
     * Scope: Filter by category.
     */
    public function scopeCategory($query, ?string $category)
    {
        if ($category) {
            return $query->where('category', $category);
        }
        return $query;
    }

    /**
     * Scope: Filter by priority.
     */
    public function scopePriority($query, ?string $priority)
    {
        if ($priority) {
            return $query->where('priority', $priority);
        }
        return $query;
    }

    /**
     * Scope: Search by subject, reference, or user email.
     */
    public function scopeSearch($query, ?string $search)
    {
        if ($search) {
            return $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('user', fn($q) => $q->where('email', 'like', "%{$search}%"));
            });
        }
        return $query;
    }

    /**
     * Get priority color class.
     */
    public function getPriorityColorAttribute(): string
    {
        return match($this->priority) {
            'urgent' => 'text-red-600 bg-red-100',
            'high' => 'text-orange-600 bg-orange-100',
            'normal' => 'text-blue-600 bg-blue-100',
            'low' => 'text-slate-600 bg-slate-100',
            default => 'text-slate-600 bg-slate-100',
        };
    }

    /**
     * Get status color class.
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'new' => 'text-purple-600 bg-purple-100',
            'open' => 'text-blue-600 bg-blue-100',
            'in_progress' => 'text-yellow-600 bg-yellow-100',
            'waiting' => 'text-orange-600 bg-orange-100',
            'resolved' => 'text-emerald-600 bg-emerald-100',
            'closed' => 'text-slate-600 bg-slate-100',
            default => 'text-slate-600 bg-slate-100',
        };
    }
}
