<?php

namespace App\Services;

use App\Helpers\DatabaseHelper;
use App\Models\BusinessSettings;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceItem;

class FiscalSummaryService
{
    use \App\Support\VentileLaTvaParTaux;

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
            'foreign_vat' => $this->getForeignVatSummary($year),
            'net_profit' => round($revenue['total_ht'] - $expenses['total_ht'], 2),
            'business' => [
                'company_name' => $settings?->company_name,
                'matricule' => $settings?->matricule,
                'vat_number' => $settings?->vat_number,
            ],
        ];
    }

    /**
     * TVA étrangère récupérable, ventilée par pays.
     *
     * Une TVA facturée par un fournisseur d'un autre État membre ne se déduit
     * pas au Luxembourg — faktur.lu l'écarte donc de la TVA déductible, et le
     * montant disparaissait de la vue de l'utilisateur. Elle n'est pourtant pas
     * perdue : elle se récupère par la procédure de remboursement de la
     * directive 2008/9/CE.
     *
     * Encore faut-il savoir combien, et auprès de qui. La demande se dépose
     * PAR PAYS DE REMBOURSEMENT : on ne réclame pas une somme à l'Union, mais
     * un montant à l'Allemagne et un autre à la France. D'où cette ventilation,
     * qui n'est pas une présentation mais la forme même du formulaire.
     *
     * Le seuil décide de l'opportunité. En dessous de 50 € pour une année
     * complète, l'État de remboursement n'examine pas la demande : afficher le
     * montant sans dire s'il est atteignable laisserait entreprendre une
     * démarche vouée au refus.
     */
    protected function getForeignVatSummary(int $year): array
    {
        /** Minimum pour une demande portant sur une année civile complète (art. 17 de la directive 2008/9/CE). */
        $seuilAnnuel = 50;

        $lignes = Expense::forYear($year)
            ->where('vat_regime', Expense::REGIME_FOREIGN_VAT)
            ->selectRaw('supplier_country, SUM(amount_vat) as tva, COUNT(*) as achats')
            ->groupBy('supplier_country')
            ->get();

        $noms = collect(Expense::getSupplierCountries())->keyBy('code');

        $parPays = $lignes
            ->map(fn ($l) => [
                'code' => $l->supplier_country,
                'pays' => $noms[$l->supplier_country]['name'] ?? $l->supplier_country,
                'tva' => round((float) $l->tva, 2),
                'achats' => (int) $l->achats,
                'recuperable' => round((float) $l->tva, 2) >= $seuilAnnuel,
            ])
            ->sortByDesc('tva')
            ->values()
            ->all();

        $recuperable = collect($parPays)->where('recuperable', true)->sum('tva');

        return [
            'par_pays' => $parPays,
            'total' => round(collect($parPays)->sum('tva'), 2),
            'total_recuperable' => round($recuperable, 2),
            'seuil_annuel' => $seuilAnnuel,
            // La demande se dépose au plus tard le 30 septembre de l'année
            // suivant la période : sans cette date, le montant ne dit pas quand
            // agir, et une créance qu'on oublie de réclamer est une créance
            // perdue.
            'date_limite' => sprintf('30/09/%d', $year + 1),
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

        // VAT breakdown by rate.
        // ⚠️ Les lignes ET les remises : une remise globale n'existe pas dans
        // les lignes, et la sommer sans elle gonflait la base déclarée.
        $vatBreakdown = $this->ventilationTvaParTaux(
            (clone $invoices)->with(['items', 'discounts'])->get()
        );

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

        // La ventilation distingue la TVA payée de la TVA récupérable.
        //
        // Elle ne remontait que la première, sous un intitulé qui annonçait la
        // seconde : une TVA étrangère de 19 € s'affichait comme déductible sur
        // sa ligne alors que le total, lui, l'excluait. Les lignes ne
        // s'additionnaient donc pas au total affiché juste en dessous.
        $byCategory = Expense::forYear($year)
            ->selectRaw('category, SUM(amount_ht) as total_ht, SUM(amount_vat) as total_vat, SUM(CASE WHEN is_deductible = 1 THEN amount_vat ELSE 0 END) as total_vat_deductible, COUNT(*) as count')
            ->groupBy('category')
            ->get()
            ->mapWithKeys(function ($item) use ($userLabels) {
                $cat = $item->category;

                $vat = round((float) $item->total_vat, 2);
                $deductible = round((float) $item->total_vat_deductible, 2);

                return [$cat => [
                    'total_ht' => round((float) $item->total_ht, 2),
                    'total_vat' => $vat,
                    'total_vat_deductible' => $deductible,
                    'total_vat_non_deductible' => round($vat - $deductible, 2),
                    'count' => (int) $item->count,
                    // Deux libellés distincts, et il faut les deux : celui que
                    // l'utilisateur a donné à sa catégorie, et la ligne du
                    // formulaire 152 sur laquelle elle se reporte. Une
                    // catégorie renommée « Loyer et charges » doit s'afficher
                    // ainsi, même si elle se déclare en « Fournitures de
                    // bureau ».
                    'label' => $userLabels[$cat] ?? Expense::builtInCategories()[$cat] ?? $cat,
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

}
