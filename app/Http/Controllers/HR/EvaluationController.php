<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\Evaluation;
use App\Services\EvaluationPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class EvaluationController extends Controller
{
    public function show(Employee $employee, Evaluation $evaluation): Response
    {
        $evaluation->load('evaluator:id,first_name,last_name');
        $employee->load('department:id,name,color');

        return Inertia::render('HR/Evaluations/Show', [
            'employee' => $employee,
            'evaluation' => $evaluation,
        ]);
    }

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

    public function pdf(Employee $employee, Evaluation $evaluation, EvaluationPdfService $service): HttpResponse
    {
        $evaluation->load('evaluator:id,first_name,last_name');
        $employee->load('department:id,name,color');

        return $service->download($evaluation, $employee);
    }

    public function destroy(Employee $employee, Evaluation $evaluation): RedirectResponse
    {
        $evaluation->delete();

        return redirect()
            ->route('hr.employees.evaluations', $employee)
            ->with('success', __('app.hr.evaluation_deleted'));
    }
}
