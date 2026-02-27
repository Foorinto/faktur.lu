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
            ->with(['department:id,name,color', 'manager:id,first_name,last_name'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get([
                'id', 'first_name', 'last_name', 'photo_path',
                'job_title', 'department_id', 'phone', 'email_pro',
                'manager_id',
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
            ->get();

        // Build hierarchy tree and flatten depth-first (managers appear before their subordinates)
        $byId = $employees->keyBy('id');
        $children = [];
        $roots = [];

        foreach ($employees as $emp) {
            if ($emp->manager_id && $byId->has($emp->manager_id)) {
                $children[$emp->manager_id][] = $emp;
            } else {
                $roots[] = $emp;
            }
        }

        $sortByName = fn ($a, $b) => strcmp($a->last_name, $b->last_name) ?: strcmp($a->first_name, $b->first_name);
        usort($roots, $sortByName);
        foreach ($children as &$group) {
            usort($group, $sortByName);
        }

        $ordered = collect();
        $traverse = function (array $nodes) use (&$traverse, &$ordered, &$children) {
            foreach ($nodes as $emp) {
                $ordered->push($emp);
                if (isset($children[$emp->id])) {
                    $traverse($children[$emp->id]);
                }
            }
        };
        $traverse($roots);

        return $service->download($ordered);
    }
}
