<?php

namespace App\Models;

use App\Exceptions\ImmutableInvoiceException;
use App\Traits\Auditable;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes, BelongsToUser, Auditable;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_FINALIZED = 'finalized';
    public const STATUS_SENT = 'sent';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';

    public const TYPE_INVOICE = 'invoice';
    public const TYPE_CREDIT_NOTE = 'credit_note';

    /**
     * Credit note reason constants.
     */
    public const CREDIT_NOTE_REASONS = [
        'billing_error' => 'Erreur de facturation',
        'return' => 'Retour de marchandise',
        'commercial_discount' => 'Remise commerciale',
        'cancellation' => 'Annulation de la facture',
        'other' => 'Autre',
    ];

    protected $fillable = [
        'client_id',
        'title',
        'number',
        'sequence_number',
        'status',
        'type',
        'credit_note_for',
        'credit_note_reason',
        'seller_snapshot',
        'buyer_snapshot',
        'total_ht',
        'total_vat',
        'total_ttc',
        'issued_at',
        'due_at',
        'finalized_at',
        'sent_at',
        'paid_at',
        'archived_at',
        'archive_format',
        'archive_checksum',
        'archive_path',
        'archive_expires_at',
        'notes',
        'footer_message',
        'vat_mention',
        'custom_vat_mention',
        'payment_reference',
        'currency',
        'exclude_from_reminders',
        'retention_guarantee_rate',
        'retention_guarantee_amount',
        'retention_release_date',
    ];

    protected $casts = [
        'seller_snapshot' => 'array',
        'buyer_snapshot' => 'array',
        'issued_at' => 'date',
        'due_at' => 'date',
        'finalized_at' => 'datetime',
        'sent_at' => 'datetime',
        'paid_at' => 'datetime',
        'archived_at' => 'datetime',
        'archive_expires_at' => 'datetime',
        'total_ht' => 'decimal:4',
        'total_vat' => 'decimal:4',
        'total_ttc' => 'decimal:4',
        'exclude_from_reminders' => 'boolean',
        'retention_guarantee_rate' => 'decimal:2',
        'retention_guarantee_amount' => 'decimal:4',
        'retention_release_date' => 'date',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Prevent modifications to finalized invoices
        static::updating(function (Invoice $invoice) {
            $originalStatus = $invoice->getOriginal('status');

            // Allow status transitions for non-draft invoices
            $allowedStatusTransitions = [
                'finalized' => ['sent', 'paid', 'cancelled'],
                'sent' => ['paid', 'cancelled'],
            ];

            // If invoice was already finalized and trying to change more than just status
            if ($originalStatus !== self::STATUS_DRAFT) {
                $changedAttributes = $invoice->getDirty();

                // Only allow status changes and archive metadata
                $allowedChanges = [
                    'status', 'sent_at', 'paid_at', 'updated_at',
                    'archived_at', 'archive_format', 'archive_checksum', 'archive_path', 'archive_expires_at',
                ];
                $disallowedChanges = array_diff(array_keys($changedAttributes), $allowedChanges);

                if (!empty($disallowedChanges)) {
                    throw new ImmutableInvoiceException();
                }

                // Validate status transition
                if (isset($changedAttributes['status'])) {
                    $newStatus = $changedAttributes['status'];
                    $allowed = $allowedStatusTransitions[$originalStatus] ?? [];

                    if (!in_array($newStatus, $allowed)) {
                        throw new ImmutableInvoiceException("Transition de statut non autorisée: {$originalStatus} → {$newStatus}");
                    }
                }
            }
        });

        // Prevent deletion of finalized invoices
        static::deleting(function (Invoice $invoice) {
            if ($invoice->status !== self::STATUS_DRAFT) {
                throw new ImmutableInvoiceException('Impossible de supprimer une facture finalisée.');
            }
        });
    }

    /**
     * Get the client that owns the invoice.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the invoice items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    /**
     * Get the original invoice if this is a credit note.
     */
    public function originalInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'credit_note_for');
    }

    /**
     * Get the credit note for this invoice.
     */
    public function creditNote(): HasOne
    {
        return $this->hasOne(Invoice::class, 'credit_note_for');
    }

    /**
     * Get all credit notes for this invoice.
     */
    public function creditNotes(): HasMany
    {
        return $this->hasMany(Invoice::class, 'credit_note_for');
    }

    /**
     * Get all emails sent for this invoice.
     */
    public function emails(): HasMany
    {
        return $this->hasMany(InvoiceEmail::class)->orderByDesc('sent_at');
    }

    /**
     * Get the latest Peppol transmission for this invoice.
     */
    public function peppolTransmission(): HasOne
    {
        return $this->hasOne(PeppolTransmission::class)->latestOfMany();
    }

    /**
     * Get all Peppol transmissions for this invoice.
     */
    public function peppolTransmissions(): HasMany
    {
        return $this->hasMany(PeppolTransmission::class)->orderByDesc('created_at');
    }

    /**
     * Check if the invoice is immutable (cannot be modified).
     */
    public function isImmutable(): bool
    {
        return $this->status !== self::STATUS_DRAFT;
    }

    /**
     * Check if the invoice is a draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Check if the invoice is finalized.
     */
    public function isFinalized(): bool
    {
        return in_array($this->status, [
            self::STATUS_FINALIZED,
            self::STATUS_SENT,
            self::STATUS_PAID,
        ]);
    }

    /**
     * Check if the invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if the invoice is a credit note.
     */
    public function isCreditNote(): bool
    {
        return $this->type === self::TYPE_CREDIT_NOTE;
    }

    /**
     * Check if the invoice has a credit note.
     */
    public function hasCreditNote(): bool
    {
        return $this->creditNote()->exists();
    }

    /**
     * Check if the invoice is archived.
     */
    public function isArchived(): bool
    {
        return $this->archived_at !== null;
    }

    /**
     * Get archive status info.
     */
    public function getArchiveStatusAttribute(): ?array
    {
        if (!$this->isArchived()) {
            return null;
        }

        return [
            'archived_at' => $this->archived_at,
            'format' => $this->archive_format,
            'checksum' => $this->archive_checksum,
            'expires_at' => $this->archive_expires_at,
            'days_until_expiry' => $this->archive_expires_at?->diffInDays(now()),
        ];
    }

    /**
     * Check if a credit note can be created for this invoice.
     */
    public function canCreateCreditNote(): bool
    {
        return $this->isFinalized()
            && !$this->isCreditNote()
            && !$this->hasCreditNote()
            && $this->status !== self::STATUS_CANCELLED;
    }

    /**
     * Get the seller information (from snapshot if finalized, from settings if draft).
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
     * Get the buyer information (from snapshot if finalized, from client if draft).
     */
    public function getBuyerAttribute(): array
    {
        if ($this->buyer_snapshot) {
            return $this->buyer_snapshot;
        }

        return $this->client ? $this->client->toSnapshot() : [];
    }

    /**
     * Get the display number (draft placeholder or actual number).
     */
    public function getDisplayNumberAttribute(): string
    {
        return $this->number ?? 'BROUILLON';
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
     * Get the effective footer message (invoice-specific or global default).
     */
    public function getEffectiveFooterMessageAttribute(): ?string
    {
        // If the invoice has a specific footer message, use it.
        if ($this->footer_message !== null && $this->footer_message !== '') {
            return $this->footer_message;
        }

        // Otherwise, fall back to the global default only if the user explicitly
        // set one. No hardcoded "thank you" fallback: an empty footer means the
        // PDF shows no footer message at all.
        $settings = BusinessSettings::getInstance();
        $default = $settings?->default_invoice_footer;

        return ($default !== null && $default !== '') ? $default : null;
    }

    /**
     * Get the effective VAT mention KEY (immutable classification, not user-facing text).
     * Returns one of: 'franchise', 'reverse_charge', 'intra_eu', 'export', 'other', or null.
     * Use this for any classification logic (Peppol XML, accounting exports, etc.) — NEVER do
     * string matching on getEffectiveVatMentionAttribute() because that one is locale-translated.
     */
    public function getEffectiveVatMentionTypeAttribute(): ?string
    {
        $mentionType = $this->vat_mention;

        if (!$mentionType) {
            $settings = BusinessSettings::getInstance();
            $mentionType = $settings?->default_vat_mention;

            if (!$mentionType && $settings?->isVatExempt()) {
                $mentionType = 'franchise';
            }
        }

        if (!$mentionType || $mentionType === 'none') {
            return null;
        }

        return $mentionType;
    }

    /**
     * Get the effective VAT mention text (invoice-specific or global default).
     * The text is translated in the current locale — set by the PDF/Peppol service before rendering.
     */
    public function getEffectiveVatMentionAttribute(): ?string
    {
        $mentionType = $this->effective_vat_mention_type;

        if (!$mentionType) {
            return null;
        }

        // Custom user-supplied text bypasses translations
        if ($mentionType === 'other') {
            return $this->custom_vat_mention
                ?? BusinessSettings::getInstance()?->default_custom_vat_mention;
        }

        // Predefined mention — translated in the current locale
        $translationKey = "invoice.vat_mentions.{$mentionType}";
        $translated = __($translationKey);

        // Fallback on the legacy hardcoded constant if the translation key is missing
        if ($translated === $translationKey) {
            return BusinessSettings::VAT_MENTIONS[$mentionType] ?? null;
        }

        return $translated;
    }

    /**
     * Scope for draft invoices.
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope for finalized invoices (not draft).
     */
    public function scopeFinalized(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_FINALIZED,
            self::STATUS_SENT,
            self::STATUS_PAID,
        ]);
    }

    /**
     * Scope for unpaid invoices.
     */
    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_FINALIZED,
            self::STATUS_SENT,
        ]);
    }

    /**
     * Scope for paid invoices.
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope for overdue invoices.
     */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query->unpaid()
            ->whereNotNull('due_at')
            ->where('due_at', '<', now()->startOfDay());
    }

    /**
     * Scope to filter by year.
     */
    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->whereYear('issued_at', $year);
    }

    /**
     * Scope to filter by client.
     */
    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope for regular invoices (not credit notes).
     */
    public function scopeInvoicesOnly(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_INVOICE);
    }

    /**
     * Scope for credit notes only.
     */
    public function scopeCreditNotesOnly(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_CREDIT_NOTE);
    }

    /**
     * Scope for global search.
     */
    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        if (empty($search)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search) {
            $q->where('number', 'like', "%{$search}%")
              ->orWhere('title', 'like', "%{$search}%")
              ->orWhereHas('client', fn (Builder $c) => $c->where('name', 'like', "%{$search}%"));
        });
    }
}
