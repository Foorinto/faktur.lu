<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Services\HRDashboardService;
use Inertia\Inertia;
use Inertia\Response;

class HRDashboardController extends Controller
{
    public function index(HRDashboardService $service): Response
    {
        return Inertia::render('HR/Dashboard', [
            'widgets' => $service->getWidgets(),
        ]);
    }
}
