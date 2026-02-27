<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function index(): Response
    {
        $departments = Department::query()
            ->withCount('employees')
            ->with('parent:id,name')
            ->orderBy('name')
            ->get();

        return Inertia::render('HR/Departments/Index', [
            'departments' => $departments,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:departments,id'],
            'color' => ['nullable', 'string', 'max:7'],
        ]);

        Department::create($validated);

        return back()->with('success', 'Département créé.');
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'parent_id' => ['nullable', 'exists:departments,id'],
            'color' => ['nullable', 'string', 'max:7'],
        ]);

        $department->update($validated);

        return back()->with('success', 'Département mis à jour.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->employees()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer un département qui contient des employés.');
        }

        $department->delete();

        return back()->with('success', 'Département supprimé.');
    }
}
