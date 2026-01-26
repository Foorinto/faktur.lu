<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    use HasFactory;

    // Available units (same as InvoiceItem)
    public const UNIT_HOUR = 'hour';
    public const UNIT_DAY = 'day';
    public const UNIT_PIECE = 'piece';
    public const UNIT_PACKAGE = 'package';
    public const UNIT_MONTH = 'month';
    public const UNIT_WORD = 'word';
    public const UNIT_PAGE = 'page';

    protected $fillable = [
        'quote_id',
        'title',
        'description',
        'quantity',
        'unit',
        'unit_price',
        'vat_rate',
        'total_ht',
        'total_vat',
        'total_ttc',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
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
        static::saving(function (QuoteItem $item) {
            $item->calculateAmounts();
        });

        // Recalculate quote totals after save
        static::saved(function (QuoteItem $item) {
            $item->quote->refresh();
            app(\App\Actions\CalculateQuoteTotalsAction::class)->execute($item->quote);
        });

        // Recalculate quote totals after delete
        static::deleted(function (QuoteItem $item) {
            if ($item->quote) {
                $item->quote->refresh();
                app(\App\Actions\CalculateQuoteTotalsAction::class)->execute($item->quote);
            }
        });
    }

    /**
     * Get the quote that owns the item.
     */
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    /**
     * Calculate subtotal and VAT amounts.
     */
    public function calculateAmounts(): void
    {
        // Calculate total HT
        $this->total_ht = bcmul((string) $this->quantity, (string) $this->unit_price, 4);

        // Calculate VAT amount
        $vatMultiplier = bcdiv((string) $this->vat_rate, '100', 4);
        $this->total_vat = bcmul($this->total_ht, $vatMultiplier, 4);

        // Calculate total TTC
        $this->total_ttc = bcadd($this->total_ht, $this->total_vat, 4);
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
            self::UNIT_HOUR => 'heure(s)',
            self::UNIT_DAY => 'jour(s)',
            self::UNIT_PIECE => 'unité(s)',
            self::UNIT_PACKAGE => 'forfait',
            self::UNIT_MONTH => 'mois',
            self::UNIT_WORD => 'mot(s)',
            self::UNIT_PAGE => 'page(s)',
        ];
    }

    /**
     * Get unit labels with singular and plural forms.
     */
    public static function getUnitLabels(): array
    {
        return [
            self::UNIT_HOUR => ['singular' => 'heure', 'plural' => 'heures'],
            self::UNIT_DAY => ['singular' => 'jour', 'plural' => 'jours'],
            self::UNIT_PIECE => ['singular' => 'unité', 'plural' => 'unités'],
            self::UNIT_PACKAGE => ['singular' => 'forfait', 'plural' => 'forfaits'],
            self::UNIT_MONTH => ['singular' => 'mois', 'plural' => 'mois'],
            self::UNIT_WORD => ['singular' => 'mot', 'plural' => 'mots'],
            self::UNIT_PAGE => ['singular' => 'page', 'plural' => 'pages'],
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
