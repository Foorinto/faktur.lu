<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Department;
use App\Models\HR\Employee;
use App\Services\TrombinoscoPdfService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class TrombinoscopeController extends Controller
{
    public function index(Request $request): Response
    {
        $employees = Employee::query()
            ->active()
            ->search($request->input('search'))
            ->ofDepartment($request->input('department') ? (int) $request->input('department') : null)
            ->with('department:id,name,color')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get([
                'id', 'first_name', 'last_name', 'photo_path',
                'job_title', 'department_id', 'phone', 'email_pro',
            ]);

        $departments = Department::query()->orderBy('name')->get(['id', 'name', 'color']);

        return Inertia::render('HR/Trombinoscope', [
            'employees' => $employees,
            'departments' => $departments,
            'filters' => [
                'search' => $request->input('search'),
                'department' => $request->input('department'),
            ],
        ]);
    }

    public function pdf(Request $request, TrombinoscoPdfService $service): HttpResponse
    {
        $employees = Employee::query()
            ->active()
            ->ofDepartment($request->input('department') ? (int) $request->input('department') : null)
            ->with('department:id,name,color')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return $service->download($employees);
    }
}
