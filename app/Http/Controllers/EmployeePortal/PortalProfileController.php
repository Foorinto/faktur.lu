<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EmployeePortal\Concerns\ResolvesEmployee;
use App\Models\HR\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PortalProfileController extends Controller
{
    use ResolvesEmployee;

    public function edit(): Response
    {
        $employee = $this->employee();
        $employee->load(['department' => fn ($q) => $q->withoutGlobalScope('user')->select('id', 'name', 'color')]);

        return Inertia::render('EmployeePortal/Profile/Edit', [
            'employee' => $employee,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $employee = $this->employee();

        $validated = $request->validate([
            'email_perso' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'phone_perso' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['nullable', 'string', 'max:5'],
            'emergency_contact' => ['nullable', 'array'],
            'bank_iban' => ['nullable', 'string', 'max:34'],
        ]);

        Employee::withoutGlobalScope('user')
            ->where('id', $employee->id)
            ->update($validated);

        return back()->with('success', __('app.employee_portal.profile_updated'));
    }
}
