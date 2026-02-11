<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupportMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'sender_type',
        'sender_id',
        'content',
        'is_internal',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
        ];
    }

    /**
     * Get the ticket this message belongs to.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class, 'ticket_id');
    }

    /**
     * Get the sender (polymorphic: User or admin).
     */
    public function sender(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get all attachments for this message.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(SupportAttachment::class, 'message_id');
    }

    /**
     * Check if the message is from an admin.
     */
    public function isFromAdmin(): bool
    {
        return $this->sender_type === 'admin' || $this->sender_type === AdminSession::class;
    }

    /**
     * Check if the message is from the user.
     */
    public function isFromUser(): bool
    {
        return $this->sender_type === User::class;
    }

    /**
     * Get the sender name for display.
     */
    public function getSenderNameAttribute(): string
    {
        if ($this->isFromAdmin()) {
            return 'Support';
        }

        if ($this->sender) {
            return $this->sender->name ?? $this->sender->email ?? 'Utilisateur';
        }

        return 'Utilisateur';
    }
}
