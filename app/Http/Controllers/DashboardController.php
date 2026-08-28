<?php

namespace App\Http\Controllers;

use App\Services\CashflowForecastService;
use App\Services\DashboardService;
use App\Services\FranchiseAlertService;
use App\Services\OnboardingService;
use App\Services\PlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService,
        protected FranchiseAlertService $franchiseAlertService,
        protected CashflowForecastService $cashflowForecastService,
        protected OnboardingService $onboardingService,
        protected PlanService $planService
    ) {}

    /**
     * Display the dashboard.
     */
    public function index(Request $request): Response
    {
        $year = $request->input('year', now()->year);
        $user = $request->user();

        $showChecklist = $this->onboardingService->shouldShowChecklist($user);
        $checklist = $showChecklist ? $this->onboardingService->getChecklist($user) : null;

        return Inertia::render('Dashboard', [
            'kpis' => $this->dashboardService->getKpis($year),
            'revenueChart' => $this->dashboardService->getRevenueChart($year),
            'unpaidInvoices' => $this->dashboardService->getUnpaidInvoices(5),
            'unbilledTimeByClient' => $this->dashboardService->getUnbilledTimeByClient(5),
            'recentInvoices' => $this->dashboardService->getRecentInvoices(5),
            'availableYears' => $this->dashboardService->getAvailableYears(),
            'selectedYear' => (int) $year,
            'franchiseAlert' => $this->franchiseAlertService->getFranchiseAlertData(),
            'cashflowForecast' => $this->cashflowForecastService->getForecast(90),
            'onboardingChecklist' => $checklist,
            // Quotas proches ou atteints : prévenir avant le blocage.
            'quotaAlerts' => $this->planService->getQuotaAlerts($user),
            // Encaissements par moyen, mois par mois (FEAT-114). Demandé par un
            // client payant qui voyait ici son chiffre d'affaires mensuel sans
            // savoir comment il avait été réglé.
            'encaissementsParMoyen' => $this->encaissementsParMoyen($user, (int) $year),
        ]);
    }

    /**
     * Encaissements de l'ANNÉE, par moyen de paiement (FEAT-114).
     *
     * Le chiffre d'affaires mensuel était déjà là ; ce qui manquait, c'est
     * comment il a été réglé. Un récapitulatif annuel donne la vue d'ensemble
     * qu'on vient chercher sur un tableau de bord ; chaque ligne renvoie au
     * listing des factures filtré sur son moyen pour le détail.
     *
     * Suit le sélecteur d'année, comme le récapitulatif TVA à côté : une carte
     * figée sur l'année en cours dirait autre chose que sa voisine dès qu'on
     * change d'année.
     *
     * ⚠️ Même verrou que le livre de recettes — un compte gratuit consulte
     * l'année en cours. Sans cela le tableau de bord rendrait par la bande
     * l'historique que le livre réserve aux plans payants.
     *
     * @return array{annee: int, verrouille: bool, total: float, lignes: array<int, mixed>}
     */
    private function encaissementsParMoyen(\App\Models\User $user, int $year): array
    {
        $historiqueComplet = $this->planService->hasFeature($user, 'accounting_exports');

        if (! $historiqueComplet && $year !== (int) now()->year) {
            return ['annee' => $year, 'verrouille' => true, 'total' => 0.0, 'lignes' => []];
        }

        $ventilation = app(\App\Services\VentilationEncaissements::class)
            ->surPeriode($user->id, "{$year}-01-01", "{$year}-12-31");

        return ['annee' => $year, 'verrouille' => false, ...$ventilation];
    }

    /**
     * Get KPIs data (API endpoint).
     */
    public function kpis(Request $request): JsonResponse
    {
        $year = $request->input('year', now()->year);

        return response()->json([
            'data' => $this->dashboardService->getKpis($year),
        ]);
    }

    /**
     * Get revenue chart data (API endpoint).
     */
    public function revenueChart(Request $request): JsonResponse
    {
        $year = $request->input('year', now()->year);

        return response()->json([
            'data' => $this->dashboardService->getRevenueChart($year),
        ]);
    }

    /**
     * Get unpaid invoices (API endpoint).
     */
    public function unpaidInvoices(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);

        return response()->json([
            'data' => $this->dashboardService->getUnpaidInvoices($limit),
        ]);
    }

    /**
     * Get unbilled time by client (API endpoint).
     */
    public function unbilledTime(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 10);

        return response()->json([
            'data' => $this->dashboardService->getUnbilledTimeByClient($limit),
        ]);
    }

    /**
     * Get cashflow forecast (API endpoint).
     */
    public function cashflowForecast(Request $request): JsonResponse
    {
        $days = (int) $request->input('days', 90);
        $days = in_array($days, [30, 60, 90]) ? $days : 90;

        return response()->json([
            'data' => $this->cashflowForecastService->getForecast($days),
        ]);
    }

    /**
     * Get VAT summary (API endpoint).
     */
    public function vatSummary(Request $request): JsonResponse
    {
        $year = $request->input('year', now()->year);
        $kpis = $this->dashboardService->getKpis($year);

        return response()->json([
            'data' => $kpis['vat_summary'],
        ]);
    }
}
