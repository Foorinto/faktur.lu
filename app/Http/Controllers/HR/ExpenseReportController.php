<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\Employee;
use App\Models\HR\ExpenseCategory;
use App\Models\HR\ExpenseReceipt;
use App\Models\HR\ExpenseReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseReportController extends Controller
{
    public function index(Request $request): Response
    {
        $query = ExpenseReport::query()
            ->with(['employee:id,first_name,last_name,photo_path', 'category:id,name,color', 'receipts'])
            ->ofStatus($request->input('status'))
            ->ofEmployee($request->input('employee') ? (int) $request->input('employee') : null)
            ->ofCategory($request->input('category') ? (int) $request->input('category') : null)
            ->ofDateRange($request->input('from'), $request->input('to'))
            ->latest();

        $expenses = $query->paginate(15)->withQueryString();

        $statusCounts = ExpenseReport::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $employees = Employee::orderBy('last_name')->get(['id', 'first_name', 'last_name']);
        $categories = ExpenseCategory::active()->orderBy('name')->get(['id', 'name', 'color', 'max_amount']);

        return Inertia::render('HR/Expenses/Index', [
            'expenses' => $expenses,
            'filters' => [
                'status' => $request->input('status'),
                'employee' => $request->input('employee'),
                'category' => $request->input('category'),
                'from' => $request->input('from'),
                'to' => $request->input('to'),
            ],
            'statusCounts' => $statusCounts,
            'employees' => $employees,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'vendor' => ['required', 'string', 'max:255'],
            'amount_ht' => ['required', 'numeric', 'min:0.01'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'payment_method' => ['nullable', 'string', 'in:card,cash,transfer'],
            'receipts' => ['nullable', 'array', 'max:10'],
            'receipts.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        unset($validated['receipts']);

        $expense = ExpenseReport::create($validated);

        if ($request->hasFile('receipts')) {
            foreach ($request->file('receipts') as $file) {
                $expense->receipts()->create([
                    'file_path' => $file->store('hr/expense-receipts', 'local'),
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }
        }

        return back()->with('success', __('app.hr.expense_created'));
    }

    public function update(Request $request, ExpenseReport $expenseReport): RedirectResponse
    {
        if (!in_array($expenseReport->status, ['pending', 'rejected'])) {
            return back()->with('error', __('app.hr.expense_not_editable'));
        }

        $validated = $request->validate([
            'expense_category_id' => ['required', 'exists:expense_categories,id'],
            'date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:1000'],
            'vendor' => ['required', 'string', 'max:255'],
            'amount_ht' => ['required', 'numeric', 'min:0.01'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'payment_method' => ['nullable', 'string', 'in:card,cash,transfer'],
            'receipts' => ['nullable', 'array', 'max:10'],
            'receipts.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        unset($validated['receipts']);

        if ($expenseReport->status === 'rejected') {
            $validated['status'] = 'pending';
            $validated['rejection_reason'] = null;
        }

        $expenseReport->update($validated);

        if ($request->hasFile('receipts')) {
            foreach ($request->file('receipts') as $file) {
                $expenseReport->receipts()->create([
                    'file_path' => $file->store('hr/expense-receipts', 'local'),
                    'original_name' => $file->getClientOriginalName(),
                ]);
            }
        }

        return back()->with('success', __('app.hr.expense_updated'));
    }

    public function deleteReceipt(ExpenseReport $expenseReport, ExpenseReceipt $expenseReceipt): RedirectResponse
    {
        if (!in_array($expenseReport->status, ['pending', 'rejected'])) {
            return back()->with('error', __('app.hr.expense_not_editable'));
        }

        Storage::disk('local')->delete($expenseReceipt->file_path);
        $expenseReceipt->delete();

        return back()->with('success', __('app.hr.receipt_deleted'));
    }

    public function approve(ExpenseReport $expenseReport): RedirectResponse
    {
        if (!$expenseReport->isPending()) {
            return back()->with('error', __('app.hr.expense_not_approvable'));
        }

        $expenseReport->approve();

        return back()->with('success', __('app.hr.expense_approved'));
    }

    public function reject(Request $request, ExpenseReport $expenseReport): RedirectResponse
    {
        if (!$expenseReport->isPending()) {
            return back()->with('error', __('app.hr.expense_not_rejectable'));
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $expenseReport->reject($validated['rejection_reason']);

        return back()->with('success', __('app.hr.expense_rejected'));
    }

    public function destroy(ExpenseReport $expenseReport): RedirectResponse
    {
        if (!$expenseReport->isPending()) {
            return back()->with('error', __('app.hr.expense_not_deletable'));
        }

        foreach ($expenseReport->receipts as $receipt) {
            Storage::disk('local')->delete($receipt->file_path);
        }

        $expenseReport->delete();

        return back()->with('success', __('app.hr.expense_deleted'));
    }
}
