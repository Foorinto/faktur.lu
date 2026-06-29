<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringInvoice extends Model
{
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_MONTHLY = 'monthly';
    const FREQUENCY_QUARTERLY = 'quarterly';
    const FREQUENCY_YEARLY = 'yearly';

    const FREQUENCIES = [
        self::FREQUENCY_WEEKLY,
        self::FREQUENCY_MONTHLY,
        self::FREQUENCY_QUARTERLY,
        self::FREQUENCY_YEARLY,
    ];

    protected $fillable = [
        'user_id',
        'client_id',
        'title',
        'frequency',
        'next_invoice_date',
        'ends_at',
        'is_active',
        'auto_finalize',
        'auto_send',
        'payment_delay_days',
        'notes',
        'vat_mention',
        'custom_vat_mention',
        'footer_message',
        'currency',
        'invoices_generated',
        'last_invoice_id',
    ];

    protected $casts = [
        'next_invoice_date' => 'date:Y-m-d',
        'ends_at' => 'date:Y-m-d',
        'is_active' => 'boolean',
        'auto_finalize' => 'boolean',
        'auto_send' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecurringInvoiceItem::class)->orderBy('sort_order');
    }

    public function lastInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'last_invoice_id');
    }

    public function isDue(): bool
    {
        return $this->is_active
            && $this->next_invoice_date->lte(now()->startOfDay())
            && ($this->ends_at === null || $this->ends_at->gte(now()->startOfDay()));
    }

    public function calculateNextDate(): \Carbon\Carbon
    {
        return match ($this->frequency) {
            self::FREQUENCY_WEEKLY => $this->next_invoice_date->copy()->addWeek(),
            self::FREQUENCY_MONTHLY => $this->next_invoice_date->copy()->addMonth(),
            self::FREQUENCY_QUARTERLY => $this->next_invoice_date->copy()->addMonths(3),
            self::FREQUENCY_YEARLY => $this->next_invoice_date->copy()->addYear(),
        };
    }

    public function advanceToNextDate(): void
    {
        $this->update([
            'next_invoice_date' => $this->calculateNextDate(),
            'invoices_generated' => $this->invoices_generated + 1,
        ]);

        // Deactivate if past end date
        if ($this->ends_at && $this->next_invoice_date->gt($this->ends_at)) {
            $this->update(['is_active' => false]);
        }
    }

    public function totalHt(): float
    {
        return $this->items->sum(fn ($item) => $item->quantity * $item->unit_price);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->active()
            ->where('next_invoice_date', '<=', now()->startOfDay())
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now()->startOfDay());
            });
    }
}
