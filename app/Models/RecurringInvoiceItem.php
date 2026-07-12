<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringInvoiceItem extends Model
{
    protected $fillable = [
        'recurring_invoice_id',
        'title',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'discount_type',
        'discount_value',
        'vat_rate',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount_value' => 'decimal:4',
        'vat_rate' => 'decimal:2',
    ];

    public function recurringInvoice(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoice::class);
    }

    public function getTotalHtAttribute(): float
    {
        $gross = bcmul((string) $this->quantity, (string) $this->unit_price, 4);

        return (float) InvoiceItem::applyLineDiscount($gross, $this->discount_type, $this->discount_value);
    }
}
