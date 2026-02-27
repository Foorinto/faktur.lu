<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\HR\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseCategoryController extends Controller
{
    public function index(): Response
    {
        $categories = ExpenseCategory::query()
            ->withCount('expenseReports')
            ->orderBy('name')
            ->get();

        return Inertia::render('HR/ExpenseCategories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:7'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        ExpenseCategory::create($validated);

        return back()->with('success', __('app.hr.expense_category_created'));
    }

    public function update(Request $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:7'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $expenseCategory->update($validated);

        return back()->with('success', __('app.hr.expense_category_updated'));
    }

    public function destroy(ExpenseCategory $expenseCategory): RedirectResponse
    {
        if ($expenseCategory->expenseReports()->count() > 0) {
            return back()->with('error', __('app.hr.expense_category_in_use'));
        }

        $expenseCategory->delete();

        return back()->with('success', __('app.hr.expense_category_deleted'));
    }
}
