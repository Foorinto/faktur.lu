<?php

namespace App\Models;

use App\Actions\CalculateQuoteTotalsAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteDiscount extends Model
{
    protected $fillable = [
        'quote_id',
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
        static::saved(function (QuoteDiscount $discount) {
            $discount->recalculateQuote();
        });

        static::deleted(function (QuoteDiscount $discount) {
            $discount->recalculateQuote();
        });
    }

    private function recalculateQuote(): void
    {
        if ($this->quote) {
            $this->quote->refresh();
            app(CalculateQuoteTotalsAction::class)->execute($this->quote);
        }
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }
}
