<?php

namespace App\Http\Controllers\EmployeePortal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\EmployeePortal\Concerns\ResolvesEmployee;
use App\Models\HR\ExpenseCategory;
use App\Models\HR\ExpenseReceipt;
use App\Models\HR\ExpenseReport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PortalExpenseController extends Controller
{
    use ResolvesEmployee;

    public function index(): Response
    {
        $employee = $this->employee();

        $expenses = ExpenseReport::withoutGlobalScope('user')
            ->where('employee_id', $employee->id)
            ->with([
                'category' => fn ($q) => $q->withoutGlobalScope('user'),
                'receipts' => fn ($q) => $q->withoutGlobalScope('user'),
            ])
            ->latest()
            ->paginate(15);

        $categories = ExpenseCategory::forUser($employee->user_id)
            ->active()
            ->orderBy('name')
            ->get(['id', 'name', 'color', 'max_amount']);

        return Inertia::render('EmployeePortal/Expenses/Index', [
            'expenses' => $expenses,
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $this->employee();

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

        $expense = ExpenseReport::withoutGlobalScope('user')->create([
            ...$validated,
            'employee_id' => $employee->id,
            'user_id' => $employee->user_id,
        ]);

        if ($request->hasFile('receipts')) {
            foreach ($request->file('receipts') as $file) {
                $expense->receipts()->withoutGlobalScope('user')->create([
                    'file_path' => $file->store('hr/expense-receipts', 'public'),
                    'original_name' => $file->getClientOriginalName(),
                    'user_id' => $employee->user_id,
                ]);
            }
        }

        return back()->with('success', __('app.employee_portal.expense_created'));
    }

    public function deleteReceipt(int $expenseId, int $receiptId): RedirectResponse
    {
        $employee = $this->employee();
        $expense = ExpenseReport::withoutGlobalScope('user')->findOrFail($expenseId);
        abort_unless((int) $expense->employee_id === (int) $employee->id, 403);

        if (!in_array($expense->status, ['pending', 'rejected'])) {
            return back()->with('error', __('app.hr.expense_not_editable'));
        }

        $receipt = ExpenseReceipt::withoutGlobalScope('user')->findOrFail($receiptId);
        Storage::disk('public')->delete($receipt->file_path);
        $receipt->delete();

        return back()->with('success', __('app.hr.receipt_deleted'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $employee = $this->employee();
        $expense = ExpenseReport::withoutGlobalScope('user')->findOrFail($id);
        abort_unless((int) $expense->employee_id === (int) $employee->id, 403);

        if ($expense->status !== 'pending') {
            return back()->with('error', __('app.hr.expense_not_deletable'));
        }

        foreach ($expense->receipts()->withoutGlobalScope('user')->get() as $receipt) {
            Storage::disk('public')->delete($receipt->file_path);
        }

        $expense->delete();

        return back()->with('success', __('app.employee_portal.expense_deleted'));
    }
}
