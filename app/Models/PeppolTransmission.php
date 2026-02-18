<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PeppolTransmission extends Model
{
    use HasFactory, BelongsToUser;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';

    public const STATUSES = [
        self::STATUS_PENDING => 'En attente',
        self::STATUS_PROCESSING => 'En cours',
        self::STATUS_SENT => 'Envoyé',
        self::STATUS_DELIVERED => 'Livré',
        self::STATUS_FAILED => 'Échoué',
    ];

    protected $fillable = [
        'user_id',
        'invoice_id',
        'status',
        'document_id',
        'recipient_id',
        'recipient_scheme',
        'error_message',
        'response_data',
        'sent_at',
        'delivered_at',
    ];

    protected $casts = [
        'response_data' => 'array',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    /**
     * Get the invoice associated with this transmission.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Get the user who initiated this transmission.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Status check methods

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isDelivered(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isSuccessful(): bool
    {
        return in_array($this->status, [self::STATUS_SENT, self::STATUS_DELIVERED]);
    }

    // State transition methods

    public function markAsProcessing(): void
    {
        $this->update(['status' => self::STATUS_PROCESSING]);
    }

    public function markAsSent(string $documentId, array $responseData = []): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'document_id' => $documentId,
            'response_data' => $responseData,
            'sent_at' => now(),
        ]);
    }

    public function markAsDelivered(): void
    {
        $this->update([
            'status' => self::STATUS_DELIVERED,
            'delivered_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorMessage, array $responseData = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'response_data' => array_merge($this->response_data ?? [], $responseData),
        ]);
    }

    // Accessors

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'gray',
            self::STATUS_PROCESSING => 'blue',
            self::STATUS_SENT => 'amber',
            self::STATUS_DELIVERED => 'green',
            self::STATUS_FAILED => 'red',
            default => 'gray',
        };
    }

    // Scopes

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeDelivered($query)
    {
        return $query->where('status', self::STATUS_DELIVERED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeSuccessful($query)
    {
        return $query->whereIn('status', [self::STATUS_SENT, self::STATUS_DELIVERED]);
    }
}
