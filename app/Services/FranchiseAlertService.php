<?php

namespace App\Services;

use App\Models\BusinessSettings;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FranchiseAlertService
{
    /**
     * Warning threshold percentage (90% by default).
     */
    public const WARNING_THRESHOLD_PERCENTAGE = 90;

    /**
     * Tax authority contact information per country.
     */
    public const TAX_AUTHORITIES = [
        'LU' => [
            'name' => 'AED Luxembourg',
            'full_name' => 'Administration de l\'Enregistrement, des Domaines et de la TVA',
            'url' => 'https://pfi.public.lu/fr.html',
        ],
        'FR' => [
            'name' => 'SIE',
            'full_name' => 'Service des Impôts des Entreprises',
            'url' => 'https://www.impots.gouv.fr/portail/',
        ],
        'BE' => [
            'name' => 'SPF Finances',
            'full_name' => 'Service Public Fédéral Finances',
            'url' => 'https://finances.belgium.be/fr',
        ],
        'DE' => [
            'name' => 'Finanzamt',
            'full_name' => 'Finanzamt (Administration fiscale locale)',
            'url' => 'https://www.bzst.de/',
        ],
    ];

    /**
     * Get the business settings for the authenticated user.
     * Fetched on-demand to ensure we always get the latest settings.
     */
    protected function getSettings(): ?BusinessSettings
    {
        return BusinessSettings::getInstance();
    }

    /**
     * Get the yearly revenue for the last 12 months (rolling period).
     * Counts finalized invoices (not drafts) that are not credit notes.
     */
    public function getYearlyRevenue(): float
    {
        if (!$this->getSettings()) {
            return 0.0;
        }

        try {
            $startDate = Carbon::now()->subMonths(12)->startOfDay();

            return (float) Invoice::where('issued_at', '>=', $startDate)
                ->whereIn('status', [
                    Invoice::STATUS_FINALIZED,
                    Invoice::STATUS_SENT,
                    Invoice::STATUS_PAID,
                ])
                ->where('type', Invoice::TYPE_INVOICE)
                ->sum('total_ht');
        } catch (\Exception $e) {
            Log::error('FranchiseAlertService: Error calculating yearly revenue', [
                'error' => $e->getMessage(),
            ]);
            return 0.0;
        }
    }

    /**
     * Get the franchise threshold for the user's country.
     */
    public function getThreshold(): int
    {
        if (!$this->getSettings()) {
            return 35000; // Default to Luxembourg threshold
        }

        try {
            return $this->getSettings()->getFranchiseThreshold();
        } catch (\Exception $e) {
            Log::error('FranchiseAlertService: Error getting franchise threshold', [
                'error' => $e->getMessage(),
            ]);
            return 35000;
        }
    }

    /**
     * Get the remaining amount before threshold is reached.
     * Returns 0 if threshold is exceeded.
     */
    public function getRemainingAmount(): float
    {
        $threshold = $this->getThreshold();
        $revenue = $this->getYearlyRevenue();

        return max(0, $threshold - $revenue);
    }

    /**
     * Get the percentage of threshold used.
     */
    public function getPercentageUsed(): float
    {
        $threshold = $this->getThreshold();

        if ($threshold <= 0) {
            return 0.0;
        }

        $revenue = $this->getYearlyRevenue();
        $percentage = ($revenue / $threshold) * 100;

        return round($percentage, 1);
    }

    /**
     * Check if the user is approaching the threshold.
     *
     * @param int $warningPercentage The percentage at which to trigger warning (default 90%)
     */
    public function isApproachingThreshold(int $warningPercentage = self::WARNING_THRESHOLD_PERCENTAGE): bool
    {
        $percentage = $this->getPercentageUsed();

        return $percentage >= $warningPercentage && $percentage < 100;
    }

    /**
     * Check if the threshold has been exceeded.
     */
    public function isThresholdExceeded(): bool
    {
        return $this->getPercentageUsed() >= 100;
    }

    /**
     * Get the current user's country code.
     */
    public function getCountryCode(): string
    {
        return $this->getSettings()?->country_code ?? 'LU';
    }

    /**
     * Get the tax authority information for the user's country.
     */
    public function getTaxAuthority(): array
    {
        $countryCode = $this->getCountryCode();

        return self::TAX_AUTHORITIES[$countryCode] ?? self::TAX_AUTHORITIES['LU'];
    }

    /**
     * Get the legal reference for the franchise regime.
     */
    public function getLegalReference(): string
    {
        if (!$this->getSettings()) {
            return 'Art. 57 du Code de la TVA luxembourgeois';
        }

        return $this->getSettings()->getFranchiseLegalReference();
    }

    /**
     * Check if the user is in franchise regime.
     */
    public function isInFranchiseRegime(): bool
    {
        return $this->getSettings()?->isVatExempt() ?? true;
    }

    /**
     * Get the alert status based on current revenue.
     * Returns null if no alert is needed.
     */
    public function getAlertStatus(): ?string
    {
        // Only show alerts for users in franchise regime
        if (!$this->isInFranchiseRegime()) {
            return null;
        }

        if ($this->isThresholdExceeded()) {
            return 'exceeded';
        }

        if ($this->isApproachingThreshold()) {
            return 'warning';
        }

        return null;
    }

    /**
     * Get full franchise alert data for the frontend.
     */
    public function getFranchiseAlertData(): array
    {
        // Return empty data if no business settings configured
        if (!$this->getSettings()) {
            return [
                'show' => false,
                'status' => null,
                'yearly_revenue' => 0,
                'threshold' => 35000,
                'remaining_amount' => 35000,
                'percentage_used' => 0,
                'country_code' => 'LU',
                'tax_authority' => self::TAX_AUTHORITIES['LU'],
                'legal_reference' => 'Art. 57 du Code de la TVA luxembourgeois',
                'is_franchise_regime' => true,
            ];
        }

        $alertStatus = $this->getAlertStatus();

        return [
            'show' => $alertStatus !== null,
            'status' => $alertStatus,
            'yearly_revenue' => round($this->getYearlyRevenue(), 2),
            'threshold' => $this->getThreshold(),
            'remaining_amount' => round($this->getRemainingAmount(), 2),
            'percentage_used' => $this->getPercentageUsed(),
            'country_code' => $this->getCountryCode(),
            'tax_authority' => $this->getTaxAuthority(),
            'legal_reference' => $this->getLegalReference(),
            'is_franchise_regime' => $this->isInFranchiseRegime(),
        ];
    }
}
