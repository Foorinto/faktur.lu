<?php

namespace App\Models;

use App\Helpers\DatabaseHelper;
use App\Traits\Auditable;
use App\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Expense extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, BelongsToUser, Auditable;

    public const CATEGORY_HARDWARE = 'hardware';
    public const CATEGORY_SOFTWARE = 'software';
    public const CATEGORY_HOSTING = 'hosting';
    public const CATEGORY_OFFICE = 'office';
    public const CATEGORY_TRAVEL = 'travel';
    public const CATEGORY_TRAINING = 'training';
    public const CATEGORY_PROFESSIONAL_SERVICES = 'professional_services';
    public const CATEGORY_TELECOMMUNICATIONS = 'telecommunications';
    public const CATEGORY_OTHER = 'other';

    public const PAYMENT_CASH = 'cash';
    public const PAYMENT_CARD = 'card';
    public const PAYMENT_TRANSFER = 'transfer';
    public const PAYMENT_CHECK = 'check';

    protected $fillable = [
        'date',
        'provider_name',
        'category',
        'amount_ht',
        'vat_rate',
        'amount_vat',
        'amount_ttc',
        'description',
        'is_deductible',
        'payment_method',
        'reference',
    ];

    protected $casts = [
        'date' => 'date',
        'amount_ht' => 'decimal:4',
        'amount_vat' => 'decimal:4',
        'amount_ttc' => 'decimal:4',
        'vat_rate' => 'decimal:2',
        'is_deductible' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        // Auto-calculate VAT and TTC when saving
        static::saving(function (Expense $expense) {
            $expense->calculateAmounts();
        });
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachments')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'])
            ->singleFile();
    }

    /**
     * Calculate VAT and TTC amounts.
     */
    public function calculateAmounts(): void
    {
        $amountHt = (string) $this->amount_ht;
        $vatRate = (string) $this->vat_rate;

        $vatMultiplier = bcdiv($vatRate, '100', 4);
        $this->amount_vat = bcmul($amountHt, $vatMultiplier, 4);
        $this->amount_ttc = bcadd($amountHt, $this->amount_vat, 4);
    }

    /**
     * Get all available categories.
     */
    public static function getCategories(): array
    {
        return [
            self::CATEGORY_HARDWARE => __('app.expense_categories.hardware'),
            self::CATEGORY_SOFTWARE => __('app.expense_categories.software'),
            self::CATEGORY_HOSTING => __('app.expense_categories.hosting'),
            self::CATEGORY_OFFICE => __('app.expense_categories.office'),
            self::CATEGORY_TRAVEL => __('app.expense_categories.travel'),
            self::CATEGORY_TRAINING => __('app.expense_categories.training'),
            self::CATEGORY_PROFESSIONAL_SERVICES => __('app.expense_categories.professional_services'),
            self::CATEGORY_TELECOMMUNICATIONS => __('app.expense_categories.telecommunications'),
            self::CATEGORY_OTHER => __('app.expense_categories.other'),
        ];
    }

    /**
     * Get all available payment methods.
     */
    public static function getPaymentMethods(): array
    {
        return [
            self::PAYMENT_CARD => __('app.payment_methods.card'),
            self::PAYMENT_TRANSFER => __('app.payment_methods.transfer'),
            self::PAYMENT_CASH => __('app.payment_methods.cash'),
            self::PAYMENT_CHECK => __('app.payment_methods.check'),
        ];
    }

    /**
     * Get the category label.
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::getCategories()[$this->category] ?? $this->category;
    }

    /**
     * Get the payment method label.
     */
    public function getPaymentMethodLabelAttribute(): ?string
    {
        if (!$this->payment_method) {
            return null;
        }
        return self::getPaymentMethods()[$this->payment_method] ?? $this->payment_method;
    }

    /**
     * Get the attachment URL if exists.
     */
    public function getAttachmentUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('attachments');
        return $media ? $media->getUrl() : null;
    }

    /**
     * Get the attachment filename if exists.
     */
    public function getAttachmentFilenameAttribute(): ?string
    {
        $media = $this->getFirstMedia('attachments');
        return $media ? $media->file_name : null;
    }

    /**
     * Check if expense has an attachment.
     */
    public function hasAttachment(): bool
    {
        return $this->getFirstMedia('attachments') !== null;
    }

    /**
     * Scope for filtering by category.
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for filtering by date range.
     */
    public function scopeDateBetween(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope for filtering by month.
     */
    public function scopeForMonth(Builder $query, int $year, int $month): Builder
    {
        return $query->whereYear('date', $year)->whereMonth('date', $month);
    }

    /**
     * Scope for filtering by year.
     */
    public function scopeForYear(Builder $query, int $year): Builder
    {
        return $query->whereYear('date', $year);
    }

    /**
     * Scope for deductible expenses only.
     */
    public function scopeDeductible(Builder $query): Builder
    {
        return $query->where('is_deductible', true);
    }

    /**
     * Get summary statistics for a period.
     */
    public static function getSummary(?int $year = null, ?int $month = null): array
    {
        $query = static::query();

        if ($year) {
            $query->whereYear('date', $year);
        }
        if ($month) {
            $query->whereMonth('date', $month);
        }

        $totals = $query->selectRaw('
            SUM(amount_ht) as total_ht,
            SUM(amount_vat) as total_vat,
            SUM(amount_ttc) as total_ttc,
            COUNT(*) as count
        ')->first();

        $byCategory = static::query()
            ->when($year, fn ($q) => $q->whereYear('date', $year))
            ->when($month, fn ($q) => $q->whereMonth('date', $month))
            ->selectRaw('category, SUM(amount_ht) as total_ht, SUM(amount_vat) as total_vat, COUNT(*) as count')
            ->groupBy('category')
            ->get()
            ->keyBy('category')
            ->toArray();

        return [
            'total_ht' => $totals->total_ht ?? 0,
            'total_vat' => $totals->total_vat ?? 0,
            'total_ttc' => $totals->total_ttc ?? 0,
            'count' => $totals->count ?? 0,
            'by_category' => $byCategory,
        ];
    }

    /**
     * Get monthly summary for a year.
     */
    public static function getMonthlySummary(int $year): array
    {
        $monthExpr = DatabaseHelper::month('date');

        return static::query()
            ->whereYear('date', $year)
            ->selectRaw("
                {$monthExpr} as month,
                SUM(amount_ht) as total_ht,
                SUM(amount_vat) as total_vat,
                SUM(amount_ttc) as total_ttc,
                COUNT(*) as count
            ")
            ->groupByRaw($monthExpr)
            ->orderByRaw($monthExpr)
            ->get()
            ->keyBy('month')
            ->toArray();
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
            $q->where('provider_name', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }
}
