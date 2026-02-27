<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Department;
use App\Models\HR\ExpenseCategory;
use App\Models\HR\LeaveType;
use App\Models\HR\OnboardingTemplate;
use App\Services\HRDashboardService;
use Inertia\Inertia;
use Inertia\Response;

class HRDashboardController extends Controller
{
    public function index(HRDashboardService $service): Response
    {
        return Inertia::render('HR/Dashboard', [
            'widgets' => $service->getWidgets(),
            'settingsCounts' => [
                'departments' => Department::count(),
                'leaveTypes' => LeaveType::count(),
                'expenseCategories' => ExpenseCategory::count(),
                'onboardingTemplates' => OnboardingTemplate::count(),
            ],
        ]);
    }
}
