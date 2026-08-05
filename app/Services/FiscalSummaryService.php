<?php

namespace App\Services;

use App\Helpers\DatabaseHelper;
use App\Models\BusinessSettings;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;

class FiscalSummaryService
{
    /**
     * Form 152 category labels (Luxembourg).
     */
    private const FORM152_MAP = [
        'hardware' => 'Matériel et équipements',
        'software' => 'Logiciels et abonnements',
        'hosting' => 'Hébergement et services cloud',
        'office' => 'Fournitures de bureau',
        'travel' => 'Frais de déplacement',
        'training' => 'Frais de formation',
        'professional_services' => 'Honoraires et commissions',
        'telecommunications' => 'Télécommunications',
        'other' => 'Charges diverses',
    ];

    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * Get complete fiscal summary for a year.
     */
    public function getSummary(int $year): array
    {
        $revenue = $this->getRevenueSummary($year);
        $expenses = $this->getExpenseSummary($year);
        $vat = $this->getVatSummary($year);
        $settings = BusinessSettings::first();

        return [
            'year' => $year,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'vat' => $vat,
            'net_profit' => round($revenue['total_ht'] - $expenses['total_ht'], 2),
            'business' => [
                'company_name' => $settings?->company_name,
                'matricule' => $settings?->matricule,
                'vat_number' => $settings?->vat_number,
            ],
        ];
    }

    /**
     * Get available years for the year selector.
     */
    public function getAvailableYears(): array
    {
        return $this->dashboardService->getAvailableYears();
    }

    /**
     * Revenue summary with monthly breakdown.
     */
    protected function getRevenueSummary(int $year): array
    {
        $invoices = Invoice::forYear($year)
            ->finalized()
            ->invoicesOnly();

        $totals = (clone $invoices)->selectRaw('
            COALESCE(SUM(total_ht), 0) as total_ht,
            COALESCE(SUM(total_vat), 0) as total_vat,
            COALESCE(SUM(total_ttc), 0) as total_ttc,
            COUNT(*) as invoice_count
        ')->first();

        // Monthly breakdown
        $monthExpr = DatabaseHelper::month('issued_at');
        $monthly = (clone $invoices)
            ->selectRaw("{$monthExpr} as month, COALESCE(SUM(total_ht), 0) as total_ht")
            ->groupByRaw($monthExpr)
            ->pluck('total_ht', 'month')
            ->toArray();

        $byMonth = [];
        for ($m = 1; $m <= 12; $m++) {
            $byMonth[$m] = round((float) ($monthly[$m] ?? 0), 2);
        }

        // VAT breakdown by rate
        $invoiceIds = (clone $invoices)->pluck('id')->toArray();
        $vatBreakdown = $this->getVatBreakdownByRate($invoiceIds);

        return [
            'total_ht' => round((float) $totals->total_ht, 2),
            'total_vat' => round((float) $totals->total_vat, 2),
            'total_ttc' => round((float) $totals->total_ttc, 2),
            'invoice_count' => (int) $totals->invoice_count,
            'by_month' => $byMonth,
            'vat_breakdown' => $vatBreakdown,
        ];
    }

    /**
     * Expense summary with category breakdown.
     */
    protected function getExpenseSummary(int $year): array
    {
        $totalHt = (float) Expense::forYear($year)->sum('amount_ht');
        $totalVatDeductible = (float) Expense::forYear($year)
            ->deductible()
            ->sum('amount_vat');
        $totalTtc = (float) Expense::forYear($year)->sum('amount_ttc');

        // By category
        //
        // Les catégories créées par l'utilisateur n'ont pas de ligne au
        // formulaire 152 : on retombe sur LEUR libellé, jamais sur la clé
        // technique. Une catégorie « Loyer » doit s'écrire « Loyer » dans
        // l'export, pas `loyer_a1b2`.
        $userLabels = Expense::categoryMap(activeOnly: false);

        $byCategory = Expense::forYear($year)
            ->selectRaw('category, SUM(amount_ht) as total_ht, SUM(amount_vat) as total_vat, COUNT(*) as count')
            ->groupBy('category')
            ->get()
            ->mapWithKeys(function ($item) use ($userLabels) {
                $cat = $item->category;

                return [$cat => [
                    'total_ht' => round((float) $item->total_ht, 2),
                    'total_vat' => round((float) $item->total_vat, 2),
                    'count' => (int) $item->count,
                    'form152_label' => self::FORM152_MAP[$cat] ?? $userLabels[$cat] ?? $cat,
                ]];
            })
            ->toArray();

        return [
            'total_ht' => round($totalHt, 2),
            'total_vat_deductible' => round($totalVatDeductible, 2),
            'total_ttc' => round($totalTtc, 2),
            'by_category' => $byCategory,
        ];
    }

    /**
     * VAT summary (collected vs deductible).
     */
    protected function getVatSummary(int $year): array
    {
        return $this->dashboardService->getVatSummary($year);
    }

    /**
     * VAT breakdown by rate from invoice items.
     */
    protected function getVatBreakdownByRate(array $invoiceIds): array
    {
        if (empty($invoiceIds)) {
            return [];
        }

        return InvoiceItem::whereIn('invoice_id', $invoiceIds)
            ->select('vat_rate')
            ->selectRaw('SUM(total_ht) as total_ht')
            ->selectRaw('SUM(total_vat) as total_vat')
            ->groupBy('vat_rate')
            ->orderByDesc('vat_rate')
            ->get()
            ->map(fn ($item) => [
                'rate' => (float) $item->vat_rate,
                'base' => round((float) $item->total_ht, 2),
                'amount' => round((float) $item->total_vat, 2),
            ])
            ->toArray();
    }
}
