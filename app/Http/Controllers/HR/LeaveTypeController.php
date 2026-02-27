<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\LeaveType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeaveTypeController extends Controller
{
    public function index(): Response
    {
        $leaveTypes = LeaveType::query()
            ->withCount('leaveRequests')
            ->orderBy('name')
            ->get();

        return Inertia::render('HR/LeaveTypes/Index', [
            'leaveTypes' => $leaveTypes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:7'],
            'default_days_per_year' => ['required', 'integer', 'min:0'],
            'requires_justification' => ['boolean'],
            'is_paid' => ['boolean'],
        ]);

        LeaveType::create($validated);

        return back()->with('success', 'Type de congé créé.');
    }

    public function update(Request $request, LeaveType $leaveType): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:7'],
            'default_days_per_year' => ['required', 'integer', 'min:0'],
            'requires_justification' => ['boolean'],
            'is_paid' => ['boolean'],
        ]);

        $leaveType->update($validated);

        return back()->with('success', 'Type de congé mis à jour.');
    }

    public function destroy(LeaveType $leaveType): RedirectResponse
    {
        if ($leaveType->leaveRequests()->count() > 0) {
            return back()->with('error', 'Impossible de supprimer un type de congé utilisé.');
        }

        $leaveType->delete();

        return back()->with('success', 'Type de congé supprimé.');
    }
}
