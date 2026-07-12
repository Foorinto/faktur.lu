<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    use HasFactory;

    // Available units
    public const UNIT_HOUR = 'hour';
    public const UNIT_DAY = 'day';
    public const UNIT_PIECE = 'piece';
    public const UNIT_PACKAGE = 'package';
    public const UNIT_MONTH = 'month';
    public const UNIT_WORD = 'word';
    public const UNIT_PAGE = 'page';

    protected $fillable = [
        'invoice_id',
        'title',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'discount_type',
        'discount_value',
        'vat_rate',
        'total_ht',
        'total_vat',
        'total_ttc',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'discount_value' => 'decimal:4',
        'vat_rate' => 'decimal:2',
        'total_ht' => 'decimal:4',
        'total_vat' => 'decimal:4',
        'total_ttc' => 'decimal:4',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Auto-calculate subtotal and VAT when saving
        static::saving(function (InvoiceItem $item) {
            $item->calculateAmounts();
        });

        // Recalculate invoice totals after save
        static::saved(function (InvoiceItem $item) {
            $item->invoice->refresh();
            app(\App\Actions\CalculateInvoiceTotalsAction::class)->execute($item->invoice);
        });

        // Recalculate invoice totals after delete
        static::deleted(function (InvoiceItem $item) {
            if ($item->invoice) {
                $item->invoice->refresh();
                app(\App\Actions\CalculateInvoiceTotalsAction::class)->execute($item->invoice);
            }
        });
    }

    /**
     * Get the invoice that owns the item.
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * Calculate subtotal and VAT amounts.
     */
    public function calculateAmounts(): void
    {
        // Gross line total (before discount)
        $gross = bcmul((string) $this->quantity, (string) $this->unit_price, 4);

        // Apply the line discount (percent or fixed amount), if any
        $this->total_ht = self::applyLineDiscount($gross, $this->discount_type, $this->discount_value);

        // Calculate VAT amount on the discounted base
        $vatMultiplier = bcdiv((string) $this->vat_rate, '100', 4);
        $this->total_vat = bcmul($this->total_ht, $vatMultiplier, 4);

        // Calculate total TTC
        $this->total_ttc = bcadd($this->total_ht, $this->total_vat, 4);
    }

    /**
     * Apply a line-level discount to a gross amount.
     *
     * - 'amount' : fixed discount, capped at the gross (never below 0).
     * - 'percent': percentage discount, capped at 100 %.
     * A discount only applies when $value > 0. Returns the net HT (string, scale 4).
     */
    public static function applyLineDiscount(string $gross, ?string $type, mixed $value): string
    {
        $value = (string) ($value ?? '0');

        if (bccomp($value, '0', 4) !== 1) {
            return $gross; // no discount
        }

        if ($type === 'amount') {
            $discount = bccomp($value, $gross, 4) === 1 ? $gross : $value; // cap at gross
        } else {
            $pct = bccomp($value, '100', 4) === 1 ? '100' : $value; // cap at 100 %
            $discount = bcmul($gross, bcdiv($pct, '100', 6), 4);
        }

        $net = bcsub($gross, $discount, 4);

        return bccomp($net, '0', 4) === -1 ? '0' : $net; // never negative
    }

    /**
     * Get formatted unit price for display.
     */
    public function getFormattedUnitPriceAttribute(): string
    {
        return number_format((float) $this->unit_price, 2, ',', ' ') . ' €';
    }

    /**
     * Get formatted total HT for display.
     */
    public function getFormattedTotalHtAttribute(): string
    {
        return number_format((float) $this->total_ht, 2, ',', ' ') . ' €';
    }

    /**
     * Get formatted VAT amount for display.
     */
    public function getFormattedTotalVatAttribute(): string
    {
        return number_format((float) $this->total_vat, 2, ',', ' ') . ' €';
    }

    /**
     * Get formatted total TTC for display.
     */
    public function getFormattedTotalTtcAttribute(): string
    {
        return number_format((float) $this->total_ttc, 2, ',', ' ') . ' €';
    }

    /**
     * Get all available units with labels (for form selectors).
     */
    public static function getUnits(): array
    {
        return [
            self::UNIT_HOUR => __('app.units.hour'),
            self::UNIT_DAY => __('app.units.day'),
            self::UNIT_PIECE => __('app.units.piece'),
            self::UNIT_PACKAGE => __('app.units.package'),
            self::UNIT_MONTH => __('app.units.month'),
            self::UNIT_WORD => __('app.units.word'),
            self::UNIT_PAGE => __('app.units.page'),
        ];
    }

    /**
     * Get unit labels with singular and plural forms.
     */
    public static function getUnitLabels(): array
    {
        return [
            self::UNIT_HOUR => __('app.unit_labels.hour'),
            self::UNIT_DAY => __('app.unit_labels.day'),
            self::UNIT_PIECE => __('app.unit_labels.piece'),
            self::UNIT_PACKAGE => __('app.unit_labels.package'),
            self::UNIT_MONTH => __('app.unit_labels.month'),
            self::UNIT_WORD => __('app.unit_labels.word'),
            self::UNIT_PAGE => __('app.unit_labels.page'),
        ];
    }

    /**
     * Get the unit label (with correct singular/plural).
     */
    public function getUnitLabelAttribute(): ?string
    {
        if (!$this->unit) {
            return null;
        }

        $labels = self::getUnitLabels();
        if (!isset($labels[$this->unit])) {
            return $this->unit;
        }

        $qty = (float) $this->quantity;
        return $qty <= 1 ? $labels[$this->unit]['singular'] : $labels[$this->unit]['plural'];
    }

    /**
     * Get formatted quantity with unit.
     */
    public function getFormattedQuantityAttribute(): string
    {
        $qty = (float) $this->quantity;

        // Format: remove unnecessary decimals
        if ($qty == (int) $qty) {
            $formatted = (string) (int) $qty;
        } else {
            // Keep up to 2 decimals, remove trailing zeros
            $formatted = rtrim(rtrim(number_format($qty, 2, ',', ' '), '0'), ',');
        }

        if ($this->unit) {
            return $formatted . ' ' . $this->unit_label;
        }

        return $formatted;
    }
}
