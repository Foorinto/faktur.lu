<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\Evaluation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EvaluationController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'date' => ['required', 'date'],
            'evaluator_id' => ['required', 'exists:employees,id'],
        ]);

        $employee->evaluations()->create($validated);

        return back()->with('success', __('app.hr.evaluation_created'));
    }

    public function destroy(Employee $employee, Evaluation $evaluation): RedirectResponse
    {
        $evaluation->delete();

        return back()->with('success', __('app.hr.evaluation_deleted'));
    }
}
