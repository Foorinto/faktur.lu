<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EmployeePortal\Concerns\ResolvesEmployee;
use App\Models\HR\Evaluation;
use App\Services\EvaluationPdfService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class PortalEvaluationController extends Controller
{
    use ResolvesEmployee;

    public function index(): Response
    {
        $employee = $this->employee();

        $evaluations = $employee->evaluations()
            ->withoutGlobalScope('user')
            ->with(['evaluator' => fn ($q) => $q->withoutGlobalScope('user')->select('id', 'first_name', 'last_name')])
            ->latest('date')
            ->get();

        return Inertia::render('EmployeePortal/Evaluations/Index', [
            'evaluations' => $evaluations,
        ]);
    }

    public function show(int $id): Response
    {
        $employee = $this->employee();
        $evaluation = Evaluation::withoutGlobalScope('user')->findOrFail($id);
        abort_unless($evaluation->employee_id === $employee->id, 403);

        $evaluation->load(['evaluator' => fn ($q) => $q->withoutGlobalScope('user')->select('id', 'first_name', 'last_name')]);
        $employee->load(['department' => fn ($q) => $q->withoutGlobalScope('user')->select('id', 'name', 'color')]);

        return Inertia::render('EmployeePortal/Evaluations/Show', [
            'employee' => $employee,
            'evaluation' => $evaluation,
        ]);
    }

    public function pdf(int $id, EvaluationPdfService $service): HttpResponse
    {
        $employee = $this->employee();
        $evaluation = Evaluation::withoutGlobalScope('user')->findOrFail($id);
        abort_unless($evaluation->employee_id === $employee->id, 403);

        $evaluation->load(['evaluator' => fn ($q) => $q->withoutGlobalScope('user')->select('id', 'first_name', 'last_name')]);
        $employee->load(['department' => fn ($q) => $q->withoutGlobalScope('user')->select('id', 'name', 'color')]);

        return $service->download($evaluation, $employee);
    }
}
