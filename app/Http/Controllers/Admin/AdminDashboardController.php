<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminStatsService;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __construct(
        protected AdminStatsService $statsService
    ) {}

    /**
     * Display the admin dashboard.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Dashboard', [
            'userStats' => $this->statsService->getUserStats(),
            'sectorStats' => $this->statsService->getSectorStats(),
            'invoiceStats' => $this->statsService->getInvoiceStats(),
            'revenueStats' => $this->statsService->getRevenueStats(),
            'userGrowthChart' => $this->statsService->getUserGrowthChart(12),
            'revenueChart' => $this->statsService->getRevenueChart(12),
            'recentUsers' => $this->statsService->getRecentUsers(5),
            'topUsers' => $this->statsService->getTopUsers(5),
        ]);
    }
}
