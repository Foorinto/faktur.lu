<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeDocument;
use App\Models\HR\Evaluation;
use App\Services\EvaluationPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class EvaluationController extends Controller
{
    public function show(Employee $employee, Evaluation $evaluation): Response
    {
        $evaluation->load(['evaluator:id,first_name,last_name', 'documents']);
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

    public function uploadDocument(Request $request, Employee $employee, Evaluation $evaluation): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('file');
        $name = $request->input('name') ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

        $evaluation->documents()->create([
            'employee_id' => $employee->id,
            'user_id' => auth()->id(),
            'type' => 'evaluation',
            'name' => $name,
            'file_path' => $file->store('hr/evaluations', 'public'),
            'original_name' => $file->getClientOriginalName(),
        ]);

        return back()->with('success', 'Document ajouté à l\'évaluation.');
    }

    public function deleteDocument(Employee $employee, Evaluation $evaluation, EmployeeDocument $document): RedirectResponse
    {
        abort_unless((int) $document->evaluation_id === (int) $evaluation->id, 403);

        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Document supprimé.');
    }

    public function destroy(Employee $employee, Evaluation $evaluation): RedirectResponse
    {
        // Delete associated documents files
        foreach ($evaluation->documents as $doc) {
            Storage::disk('public')->delete($doc->file_path);
        }

        $evaluation->delete();

        return redirect()
            ->route('hr.employees.evaluations', $employee)
            ->with('success', __('app.hr.evaluation_deleted'));
    }
}
