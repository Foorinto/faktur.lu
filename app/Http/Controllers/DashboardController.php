<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
    ) {}

    /**
     * Display the dashboard.
     */
    public function index(Request $request): Response
    {
        $year = $request->input('year', now()->year);

        return Inertia::render('Dashboard', [
            'kpis' => $this->dashboardService->getKpis($year),
            'revenueChart' => $this->dashboardService->getRevenueChart($year),
            'unpaidInvoices' => $this->dashboardService->getUnpaidInvoices(5),
            'unbilledTimeByClient' => $this->dashboardService->getUnbilledTimeByClient(5),
            'recentInvoices' => $this->dashboardService->getRecentInvoices(5),
            'availableYears' => $this->dashboardService->getAvailableYears(),
            'selectedYear' => (int) $year,
        ]);
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
