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
        'payment_methods',
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
        'payment_methods' => 'array',
        'issued_at' => 'date:Y-m-d',
        'due_at' => 'date:Y-m-d',
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
        'retention_release_date' => 'date:Y-m-d',
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
            //
            // `paid` n'est pas terminal (FEAT-114). Ce garde protège le
            // DOCUMENT légal — lignes, montants, client, numérotation — comme
            // l'exige l'article 63 LIVA. Le statut de paiement, lui, est une
            // information de gestion SUR le document : le PDF ne le mentionne
            // nulle part, et `paid_at` est déjà modifiable après règlement.
            //
            // Une facture peut donc redevenir due : chèque impayé, virement
            // rejeté, carte contestée, ou simple erreur de saisie sur un
            // paiement en plusieurs fois. Refuser ces cas obligeait à mentir
            // sur l'état réel de la créance.
            //
            // Aucune interface n'expose de bouton « dé-encaisser » : le seul
            // chemin passe par la suppression ou la correction d'un
            // encaissement, donc par un acte comptable explicite.
            $allowedStatusTransitions = [
                'finalized' => ['sent', 'paid', 'cancelled'],
                'sent' => ['paid', 'cancelled'],
                'paid' => ['sent', 'cancelled'],
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
                        throw new ImmutableInvoiceException(__('app.error_invoice_status_transition'));
                    }
                }
            }
        });

        // Prevent deletion of finalized invoices
        static::deleting(function (Invoice $invoice) {
            if ($invoice->status !== self::STATUS_DRAFT) {
                throw new ImmutableInvoiceException(__('app.error_invoice_delete_finalized'));
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

    public function discounts(): HasMany
    {
        return $this->hasMany(InvoiceDiscount::class)->orderBy('sort_order');
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
     * Encaissements reçus sur cette facture (FEAT-114).
     *
     * Plusieurs sont possibles, de moyens différents : « des fois un paiement
     * en espèces pour une partie et le reste par virement ».
     */
    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->orderBy('paid_at');
    }

    /**
     * Somme encaissée à ce jour.
     */
    public function amountPaid(): float
    {
        return round((float) $this->payments()->sum('amount'), 2);
    }

    /**
     * Reste dû. Jamais négatif : un trop-perçu se lit dans les encaissements,
     * il ne se soustrait pas d'un solde qui deviendrait absurde.
     */
    public function amountDue(): float
    {
        return max(0.0, round((float) $this->total_ttc - $this->amountPaid(), 2));
    }

    /**
     * Partiellement réglée : quelque chose est arrivé, mais pas tout.
     *
     * ⚠️ Ce n'est PAS un statut. Le statut de la facture reste la source de
     * vérité pour « payée » — treize fichiers en dépendent, des exports
     * comptables au portail comptable en passant par l'archivage PDF. Ajouter
     * un sixième statut aurait obligé à tous les relire pour un gain nul :
     * une facture partiellement réglée reste une facture due.
     */
    public function isPartiallyPaid(): bool
    {
        return ! $this->isPaid() && $this->amountPaid() > 0;
    }

    /**
     * Recalcule le statut à partir des encaissements.
     *
     * Appelé après chaque ajout ou suppression d'encaissement. La comparaison
     * se fait au centime près : `total_ttc` est un décimal et une addition de
     * flottants peut manquer l'égalité de quelques millièmes.
     */
    public function refreshPaymentStatus(): void
    {
        $encaisse = $this->amountPaid();
        $du = round((float) $this->total_ttc, 2);

        if ($encaisse >= $du - 0.001 && $du > 0) {
            $dernier = $this->payments()->orderByDesc('paid_at')->first();

            $this->update([
                'status' => self::STATUS_PAID,
                'paid_at' => $dernier?->paid_at ?? now(),
            ]);

            return;
        }

        // Retour en arrière : la somme est redescendue sous le total — un
        // encaissement supprimé, un montant corrigé, un chèque revenu impayé.
        // La facture redevient due.
        //
        // On ne touche qu'à ce cas : une facture annulée ou en brouillon garde
        // son statut, et une facture jamais encaissée n'a rien à changer.
        if ($this->status === self::STATUS_PAID) {
            $this->update([
                'status' => self::STATUS_SENT,
                'paid_at' => null,
            ]);
        }
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
     * Moyens de paiement à imprimer sur cette facture (FEAT-098).
     *
     * Trois niveaux, du plus précis au plus général :
     *   1. ce que porte la facture, quand l'utilisateur l'a précisé ;
     *   2. à défaut, le réglage d'entreprise, figé dans l'instantané pour une
     *      facture finalisée et lu en direct pour un brouillon ;
     *   3. à défaut de tout, le virement, qui était la valeur codée en dur.
     *
     * `null` sur la facture signifie « rien de précisé », jamais « aucun
     * moyen » : c'est ce qui garantit que les factures antérieures à cette
     * colonne se rendent exactement comme avant.
     *
     * @return array<int, string>
     */
    public function effectivePaymentMethods(): array
    {
        foreach ([$this->payment_methods, $this->seller['default_payment_methods'] ?? null] as $source) {
            if (! is_array($source)) {
                continue;
            }

            $methods = array_values(array_filter(
                array_map(fn ($m) => trim((string) $m), $source),
                fn ($m) => $m !== ''
            ));

            if ($methods !== []) {
                return $methods;
            }
        }

        return ['transfer'];
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
        // VAT breakdown with global discounts ventilated per rate (net basis).
        $totals = app(\App\Services\DocumentTotalsCalculator::class)->compute($this->items, $this->discounts);

        return collect($totals['rates'])
            ->map(fn ($r) => [
                'rate' => (float) $r['rate'],
                'base' => $r['net_base'],
                'amount' => $r['vat'],
            ])
            ->values()
            ->toArray();
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
     * Use this for any classification logic (Peppol XML, accounting exports, etc.) - NEVER do
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
     * The text is translated in the current locale - set by the PDF/Peppol service before rendering.
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

        // Country-specific legal reference takes priority (e.g. FR art. 293 B vs LU art. 57).
        // Le Luxembourg conserve le texte traduit existant (comportement inchangé).
        $sellerCountry = $this->seller['country_code'] ?? null;
        if ($sellerCountry && $sellerCountry !== 'LU') {
            $countryMention = config("countries.{$sellerCountry}.vat_mentions.{$mentionType}");
            if ($countryMention) {
                return $countryMention;
            }
        }

        // Predefined mention - translated in the current locale
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
