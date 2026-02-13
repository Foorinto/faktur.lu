<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MonitoringService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminMonitoringController extends Controller
{
    public function __construct(
        protected MonitoringService $monitoringService
    ) {}

    public function index(Request $request): Response
    {
        $period = $request->get('period', '24h');

        return Inertia::render('Admin/Monitoring/Index', [
            'metrics' => $this->monitoringService->getOverview($period),
            'timeSeries' => $this->monitoringService->getTimeSeriesData($period),
            'thresholds' => config('monitoring.thresholds'),
            'period' => $period,
        ]);
    }

    public function refresh(Request $request)
    {
        $period = $request->get('period', '24h');

        return response()->json([
            'metrics' => $this->monitoringService->getOverview($period),
            'timeSeries' => $this->monitoringService->getTimeSeriesData($period),
        ]);
    }
}
