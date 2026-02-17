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

class Quote extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser, Auditable;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SENT = 'sent';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_DECLINED = 'declined';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_CONVERTED = 'converted';

    protected $fillable = [
        'client_id',
        'reference',
        'status',
        'valid_until',
        'seller_snapshot',
        'buyer_snapshot',
        'total_ht',
        'total_vat',
        'total_ttc',
        'sent_at',
        'accepted_at',
        'declined_at',
        'converted_to_invoice_id',
        'notes',
        'vat_mention',
        'custom_vat_mention',
        'footer_message',
        'currency',
    ];

    protected $casts = [
        'seller_snapshot' => 'array',
        'buyer_snapshot' => 'array',
        'valid_until' => 'date',
        'sent_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'total_ht' => 'decimal:4',
        'total_vat' => 'decimal:4',
        'total_ttc' => 'decimal:4',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Generate reference on creation
        static::creating(function (Quote $quote) {
            if (empty($quote->reference)) {
                $quote->reference = static::generateReference();
            }

            // Default validity: 30 days from now
            if (empty($quote->valid_until)) {
                $quote->valid_until = now()->addDays(30);
            }
        });

        // Prevent deletion of converted quotes
        static::deleting(function (Quote $quote) {
            if ($quote->status === self::STATUS_CONVERTED) {
                throw new \InvalidArgumentException('Impossible de supprimer un devis converti en facture.');
            }
        });
    }

    /**
     * Generate a unique reference for the quote.
     * Format: DEV-{YEAR}-{SEQUENCE}
     */
    public static function generateReference(): string
    {
        $year = now()->year;
        $prefix = "DEV-{$year}-";

        // Get the last quote of the year across ALL users (ignore user scope)
        // This is needed because the reference must be globally unique
        $lastQuote = static::withoutGlobalScope('user')
            ->withTrashed()
            ->where('reference', 'like', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(reference, -3) AS UNSIGNED) DESC')
            ->first();

        if ($lastQuote) {
            // Extract the number and increment
            $lastNumber = (int) substr($lastQuote->reference, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Get the client that owns the quote.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the quote items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('sort_order');
    }

    /**
     * Get the invoice created from this quote.
     */
    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'converted_to_invoice_id');
    }

    /**
     * Check if the quote is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if the quote is sent.
     */
    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Check if the quote is accepted.
     */
    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Check if the quote is declined.
     */
    public function isDeclined(): bool
    {
        return $this->status === self::STATUS_DECLINED;
    }

    /**
     * Check if the quote is expired.
     */
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED;
    }

    /**
     * Check if the quote is converted.
     */
    public function isConverted(): bool
    {
        return $this->status === self::STATUS_CONVERTED;
    }

    /**
     * Check if the quote can be edited.
     */
    public function canEdit(): bool
    {
        return in_array($this->status, [
            self::STATUS_DRAFT,
            self::STATUS_SENT,
        ]);
    }

    /**
     * Check if the quote can be converted to an invoice.
     */
    public function canConvert(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    /**
     * Check if the quote can be marked as sent.
     */
    public function canMarkAsSent(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if the quote can be accepted.
     */
    public function canAccept(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Check if the quote can be declined.
     */
    public function canDecline(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Check if the validity has passed.
     */
    public function hasExpired(): bool
    {
        return $this->valid_until && $this->valid_until->isPast();
    }

    /**
     * Get the seller information (from snapshot if exists, from settings if not).
     */
    public function getSellerAttribute(): array
    {
        if ($this->seller_snapshot) {
            return $this->seller_snapshot;
        }

        $settings = BusinessSettings::getInstance();
        return $settings ? $settings->toSnapshot() : [];
    }

    /**
     * Get the buyer information (from snapshot if exists, from client if not).
     */
    public function getBuyerAttribute(): array
    {
        if ($this->buyer_snapshot) {
            return $this->buyer_snapshot;
        }

        return $this->client ? $this->client->toSnapshot() : [];
    }

    /**
     * Get the display reference.
     */
    public function getDisplayReferenceAttribute(): string
    {
        return $this->reference ?? 'BROUILLON';
    }

    /**
     * Check if seller is VAT exempt based on snapshot.
     */
    public function isSellerVatExempt(): bool
    {
        $seller = $this->seller;
        return ($seller['vat_regime'] ?? 'franchise') === 'franchise';
    }

    /**
     * Get the VAT mention text for this quote.
     * Returns custom mention if set, otherwise the predefined mention text.
     */
    public function getVatMentionText(): ?string
    {
        // If custom mention is set and vat_mention is 'other', return custom text
        if ($this->vat_mention === 'other' && $this->custom_vat_mention) {
            return $this->custom_vat_mention;
        }

        // If no mention is set, return null
        if (empty($this->vat_mention) || $this->vat_mention === 'none') {
            return null;
        }

        // Get the mention text from VatCalculationService
        $settings = BusinessSettings::getInstance();
        $vatService = app(\App\Services\VatCalculationService::class);

        return $vatService->getVatMentionText($this->vat_mention, $settings);
    }

    /**
     * Get the VAT breakdown by rate.
     */
    public function getVatBreakdownAttribute(): array
    {
        $breakdown = [];

        foreach ($this->items as $item) {
            $rate = (string) $item->vat_rate;

            if (!isset($breakdown[$rate])) {
                $breakdown[$rate] = [
                    'rate' => $item->vat_rate,
                    'base' => 0,
                    'amount' => 0,
                ];
            }

            $breakdown[$rate]['base'] = bcadd($breakdown[$rate]['base'], $item->total_ht, 4);
            $breakdown[$rate]['amount'] = bcadd($breakdown[$rate]['amount'], $item->total_vat, 4);
        }

        return array_values($breakdown);
    }

    /**
     * Scope for draft quotes.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope for sent quotes.
     */
    public function scopeSent(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope for accepted quotes.
     */
    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    /**
     * Scope for declined quotes.
     */
    public function scopeDeclined(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DECLINED);
    }

    /**
     * Scope for converted quotes.
     */
    public function scopeConverted(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONVERTED);
    }

    /**
     * Scope for expired quotes.
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    /**
     * Scope for pending quotes (sent but not yet accepted/declined).
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_SENT);
    }

    /**
     * Scope to filter by year.
     */
    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->whereYear('created_at', $year);
    }

    /**
     * Scope to filter by client.
     */
    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }
}
