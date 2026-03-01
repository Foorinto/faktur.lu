<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Http\Requests\HR\StoreEmployeeRequest;
use App\Http\Requests\HR\UpdateEmployeeRequest;
use App\Models\HR\Department;
use App\Models\HR\Employee;
use App\Models\HR\ExpenseReport;
use App\Models\HR\LeaveRequest;
use App\Models\HR\LeaveType;
use App\Models\HR\OnboardingTemplate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EmployeeController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Employee::query()
            ->search($request->input('search'))
            ->ofStatus($request->input('status'))
            ->ofDepartment($request->input('department') ? (int) $request->input('department') : null)
            ->ofContractType($request->input('contract_type'))
            ->with('department:id,name,color')
            ->orderBy('last_name')
            ->orderBy('first_name');

        $employees = $query->paginate(15)->withQueryString();

        $statusCounts = Employee::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $departments = Department::query()->orderBy('name')->get(['id', 'name', 'color']);

        return Inertia::render('HR/Employees/Index', [
            'employees' => $employees,
            'filters' => [
                'search' => $request->input('search'),
                'status' => $request->input('status'),
                'department' => $request->input('department'),
                'contract_type' => $request->input('contract_type'),
            ],
            'statusCounts' => $statusCounts,
            'departments' => $departments,
            'contractTypes' => Employee::CONTRACT_TYPES,
            'statuses' => Employee::STATUSES,
        ]);
    }

    public function create(): Response
    {
        $departments = Department::query()->orderBy('name')->get(['id', 'name']);
        $employees = Employee::where('status', '!=', 'terminated')->orderBy('last_name')->get(['id', 'first_name', 'last_name']);

        return Inertia::render('HR/Employees/Create', [
            'departments' => $departments,
            'managers' => $employees,
            'contractTypes' => Employee::CONTRACT_TYPES,
            'statuses' => Employee::STATUSES,
            'countries' => $this->getCountries(),
            'nationalities' => $this->getNationalities(),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('hr/photos', 'public');
        }
        unset($data['photo']);

        $employee = Employee::create($data);

        return redirect()
            ->route('hr.employees.show', $employee)
            ->with('success', 'Employé créé avec succès.');
    }

    public function show(Employee $employee): Response
    {
        $employee->load([
            'department:id,name,color',
            'manager:id,first_name,last_name',
            'leaveBalances' => fn ($q) => $q->where('year', now()->year)->with('leaveType:id,name,color'),
            'leaveRequests' => fn ($q) => $q->with('leaveType:id,name,color')->latest()->limit(10),
        ]);

        return Inertia::render('HR/Employees/Show', [
            'employee' => $employee,
            'activeTab' => 'info',
            'countries' => $this->getCountries(),
            'nationalities' => $this->getNationalities(),
        ]);
    }

    public function leaves(Employee $employee): Response
    {
        $year = now()->year;

        $employee->load([
            'department:id,name,color',
            'manager:id,first_name,last_name',
            'leaveBalances' => fn ($q) => $q->where('year', $year)->with('leaveType:id,name,color'),
        ]);

        $leaveRequests = $employee->leaveRequests()
            ->with('leaveType:id,name,color')
            ->latest()
            ->paginate(15);

        $leaveTypes = LeaveType::query()->orderBy('name')->get();

        // Jours en attente par type de congé pour cet employé
        $pendingDays = LeaveRequest::forEmployee($employee->id)
            ->where('status', 'pending')
            ->whereYear('start_date', $year)
            ->selectRaw('leave_type_id, SUM(days_count) as total')
            ->groupBy('leave_type_id')
            ->pluck('total', 'leave_type_id');

        return Inertia::render('HR/Employees/Show', [
            'employee' => $employee,
            'leaveRequests' => $leaveRequests,
            'leaveTypes' => $leaveTypes,
            'activeTab' => 'leaves',
            'pendingDays' => $pendingDays,
        ]);
    }

    public function expenses(Employee $employee): Response
    {
        $employee->load([
            'department:id,name,color',
            'manager:id,first_name,last_name',
            'leaveBalances' => fn ($q) => $q->where('year', now()->year)->with('leaveType:id,name,color'),
        ]);

        $expenseReports = ExpenseReport::where('employee_id', $employee->id)
            ->with('category:id,name,color')
            ->latest('date')
            ->limit(20)
            ->get();

        return Inertia::render('HR/Employees/Show', [
            'employee' => $employee,
            'expenseReports' => $expenseReports,
            'activeTab' => 'expenses',
        ]);
    }

    public function documents(Employee $employee): Response
    {
        $employee->load([
            'department:id,name,color',
            'manager:id,first_name,last_name',
            'leaveBalances' => fn ($q) => $q->where('year', now()->year)->with('leaveType:id,name,color'),
        ]);

        $documents = $employee->documents()->latest()->get();

        return Inertia::render('HR/Employees/Show', [
            'employee' => $employee,
            'documents' => $documents,
            'activeTab' => 'documents',
        ]);
    }

    public function evaluations(Employee $employee): Response
    {
        $employee->load([
            'department:id,name,color',
            'manager:id,first_name,last_name',
            'leaveBalances' => fn ($q) => $q->where('year', now()->year)->with('leaveType:id,name,color'),
        ]);

        $evaluations = $employee->evaluations()
            ->with('evaluator:id,first_name,last_name')
            ->latest('date')
            ->get();

        $evaluators = Employee::active()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        return Inertia::render('HR/Employees/Show', [
            'employee' => $employee,
            'evaluations' => $evaluations,
            'evaluators' => $evaluators,
            'activeTab' => 'evaluations',
        ]);
    }

    public function onboarding(Employee $employee): Response
    {
        $employee->load([
            'department:id,name,color',
            'manager:id,first_name,last_name',
            'leaveBalances' => fn ($q) => $q->where('year', now()->year)->with('leaveType:id,name,color'),
        ]);

        $onboardingTasks = $employee->onboardingTasks()
            ->orderBy('position')
            ->get();

        $onboardingTemplates = OnboardingTemplate::orderBy('name')->get(['id', 'name']);

        return Inertia::render('HR/Employees/Show', [
            'employee' => $employee,
            'onboardingTasks' => $onboardingTasks,
            'onboardingTemplates' => $onboardingTemplates,
            'activeTab' => 'onboarding',
        ]);
    }

    public function edit(Employee $employee): Response
    {
        $employee->load('department:id,name', 'manager:id,first_name,last_name');

        $departments = Department::query()->orderBy('name')->get(['id', 'name']);
        $employees = Employee::where('status', '!=', 'terminated')
            ->where('id', '!=', $employee->id)
            ->orderBy('last_name')
            ->get(['id', 'first_name', 'last_name']);

        return Inertia::render('HR/Employees/Edit', [
            'employee' => $employee,
            'departments' => $departments,
            'managers' => $employees,
            'contractTypes' => Employee::CONTRACT_TYPES,
            'statuses' => Employee::STATUSES,
            'countries' => $this->getCountries(),
            'nationalities' => $this->getNationalities(),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('photo')) {
            if ($employee->photo_path) {
                Storage::disk('public')->delete($employee->photo_path);
            }
            $data['photo_path'] = $request->file('photo')->store('hr/photos', 'public');
        }
        unset($data['photo']);

        $employee->update($data);

        return back()->with('success', 'Employé mis à jour.');
    }

    public function activatePortal(Employee $employee): RedirectResponse
    {
        if ($employee->hasPortalAccess()) {
            return back()->with('error', __('app.employee_portal.already_activated'));
        }

        if (!$employee->email_pro) {
            return back()->with('error', __('app.employee_portal.email_required'));
        }

        $user = User::where('email', $employee->email_pro)->first();

        if (!$user) {
            $user = User::create([
                'name' => $employee->full_name,
                'email' => $employee->email_pro,
                'password' => Hash::make(Str::random(32)),
                'email_verified_at' => now(),
                'is_active' => true,
                'locale' => auth()->user()->locale ?? 'fr',
            ]);
        } elseif (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }

        $employee->update([
            'account_id' => $user->id,
            'portal_activated_at' => now(),
        ]);

        Password::sendResetLink(['email' => $user->email]);

        return back()->with('success', __('app.employee_portal.activated'));
    }

    public function deactivatePortal(Employee $employee): RedirectResponse
    {
        if (!$employee->hasPortalAccess()) {
            return back()->with('error', __('app.employee_portal.not_activated'));
        }

        $employee->update([
            'account_id' => null,
            'portal_activated_at' => null,
        ]);

        return back()->with('success', __('app.employee_portal.deactivated'));
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        if ($employee->photo_path) {
            Storage::disk('public')->delete($employee->photo_path);
        }

        $employee->delete();

        return redirect()
            ->route('hr.employees.index')
            ->with('success', 'Employé supprimé.');
    }

    private function getCountries(): array
    {
        return [
            ['code' => 'LU', 'name' => 'Luxembourg'],
            ['code' => 'BE', 'name' => 'Belgique'],
            ['code' => 'FR', 'name' => 'France'],
            ['code' => 'DE', 'name' => 'Allemagne'],
            ['code' => 'NL', 'name' => 'Pays-Bas'],
            ['code' => 'AT', 'name' => 'Autriche'],
            ['code' => 'BG', 'name' => 'Bulgarie'],
            ['code' => 'CY', 'name' => 'Chypre'],
            ['code' => 'CZ', 'name' => 'Tchéquie'],
            ['code' => 'DK', 'name' => 'Danemark'],
            ['code' => 'EE', 'name' => 'Estonie'],
            ['code' => 'ES', 'name' => 'Espagne'],
            ['code' => 'FI', 'name' => 'Finlande'],
            ['code' => 'GR', 'name' => 'Grèce'],
            ['code' => 'HR', 'name' => 'Croatie'],
            ['code' => 'HU', 'name' => 'Hongrie'],
            ['code' => 'IE', 'name' => 'Irlande'],
            ['code' => 'IT', 'name' => 'Italie'],
            ['code' => 'LT', 'name' => 'Lituanie'],
            ['code' => 'LV', 'name' => 'Lettonie'],
            ['code' => 'MT', 'name' => 'Malte'],
            ['code' => 'PL', 'name' => 'Pologne'],
            ['code' => 'PT', 'name' => 'Portugal'],
            ['code' => 'RO', 'name' => 'Roumanie'],
            ['code' => 'SE', 'name' => 'Suède'],
            ['code' => 'SI', 'name' => 'Slovénie'],
            ['code' => 'SK', 'name' => 'Slovaquie'],
            ['code' => 'GB', 'name' => 'Royaume-Uni'],
            ['code' => 'CH', 'name' => 'Suisse'],
            ['code' => 'US', 'name' => 'États-Unis'],
            ['code' => 'CA', 'name' => 'Canada'],
            ['code' => 'BR', 'name' => 'Brésil'],
            ['code' => 'OTHER', 'name' => 'Autre pays'],
        ];
    }

    private function getNationalities(): array
    {
        return [
            ['code' => 'LU', 'name' => 'Luxembourgeoise'],
            ['code' => 'PT', 'name' => 'Portugaise'],
            ['code' => 'FR', 'name' => 'Française'],
            ['code' => 'BE', 'name' => 'Belge'],
            ['code' => 'DE', 'name' => 'Allemande'],
            ['code' => 'IT', 'name' => 'Italienne'],
            ['code' => 'ES', 'name' => 'Espagnole'],
            ['code' => 'NL', 'name' => 'Néerlandaise'],
            ['code' => 'GB', 'name' => 'Britannique'],
            ['code' => 'PL', 'name' => 'Polonaise'],
            ['code' => 'RO', 'name' => 'Roumaine'],
            ['code' => 'AT', 'name' => 'Autrichienne'],
            ['code' => 'BG', 'name' => 'Bulgare'],
            ['code' => 'HR', 'name' => 'Croate'],
            ['code' => 'CY', 'name' => 'Chypriote'],
            ['code' => 'CZ', 'name' => 'Tchèque'],
            ['code' => 'DK', 'name' => 'Danoise'],
            ['code' => 'EE', 'name' => 'Estonienne'],
            ['code' => 'FI', 'name' => 'Finlandaise'],
            ['code' => 'GR', 'name' => 'Grecque'],
            ['code' => 'HU', 'name' => 'Hongroise'],
            ['code' => 'IE', 'name' => 'Irlandaise'],
            ['code' => 'LT', 'name' => 'Lituanienne'],
            ['code' => 'LV', 'name' => 'Lettonne'],
            ['code' => 'MT', 'name' => 'Maltaise'],
            ['code' => 'SE', 'name' => 'Suédoise'],
            ['code' => 'SI', 'name' => 'Slovène'],
            ['code' => 'SK', 'name' => 'Slovaque'],
            ['code' => 'CH', 'name' => 'Suisse'],
            ['code' => 'US', 'name' => 'Américaine'],
            ['code' => 'CA', 'name' => 'Canadienne'],
            ['code' => 'BR', 'name' => 'Brésilienne'],
            ['code' => 'OTHER', 'name' => 'Autre'],
        ];
    }
}
