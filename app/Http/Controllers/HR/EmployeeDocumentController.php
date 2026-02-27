<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\EmployeeDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class EmployeeDocumentController extends Controller
{
    public function store(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', Rule::in(EmployeeDocument::TYPES)],
            'name' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf,doc,docx', 'max:10240'],
            'expiry_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $file = $request->file('file');

        $employee->documents()->create([
            'type' => $validated['type'],
            'name' => $validated['name'],
            'file_path' => $file->store('hr/documents', 'public'),
            'original_name' => $file->getClientOriginalName(),
            'expiry_date' => $validated['expiry_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', __('app.hr.document_uploaded'));
    }

    public function destroy(Employee $employee, EmployeeDocument $employeeDocument): RedirectResponse
    {
        Storage::disk('public')->delete($employeeDocument->file_path);
        $employeeDocument->delete();

        return back()->with('success', __('app.hr.document_deleted'));
    }
}
