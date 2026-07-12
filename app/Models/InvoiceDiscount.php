<?php

namespace App\Models;

use App\Actions\CalculateInvoiceTotalsAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceDiscount extends Model
{
    protected $fillable = [
        'invoice_id',
        'label',
        'type',
        'value',
        'sort_order',
    ];

    protected $casts = [
        'value' => 'decimal:4',
    ];

    protected static function booted(): void
    {
        static::saved(function (InvoiceDiscount $discount) {
            $discount->recalculateInvoice();
        });

        static::deleted(function (InvoiceDiscount $discount) {
            $discount->recalculateInvoice();
        });
    }

    private function recalculateInvoice(): void
    {
        if ($this->invoice) {
            $this->invoice->refresh();
            app(CalculateInvoiceTotalsAction::class)->execute($this->invoice);
        }
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
