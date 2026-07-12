<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecurringInvoiceDiscount extends Model
{
    protected $fillable = [
        'recurring_invoice_id',
        'label',
        'type',
        'value',
        'sort_order',
    ];

    protected $casts = [
        'value' => 'decimal:4',
    ];

    public function recurringInvoice(): BelongsTo
    {
        return $this->belongsTo(RecurringInvoice::class);
    }
}
