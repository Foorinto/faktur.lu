<?php

namespace App\Models;

use App\Traits\BelongsToUser;
use App\Traits\HasRecurrenceSchedule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RecurringInvoice extends Model
{
    use BelongsToUser, HasRecurrenceSchedule;

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

    public function discounts(): HasMany
    {
        return $this->hasMany(RecurringInvoiceDiscount::class)->orderBy('sort_order');
    }

    public function lastInvoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'last_invoice_id');
    }

    public static function nextDateColumn(): string
    {
        return 'next_invoice_date';
    }

    public static function generatedCountColumn(): string
    {
        return 'invoices_generated';
    }

    public function totalHt(): float
    {
        return $this->items->sum(fn ($item) => $item->quantity * $item->unit_price);
    }
}
