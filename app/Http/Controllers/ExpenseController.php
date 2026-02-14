<?php

namespace App\Http\Controllers;

use App\Helpers\DatabaseHelper;
use App\Http\Requests\Api\V1\StoreExpenseRequest;
use App\Http\Requests\Api\V1\UpdateExpenseRequest;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    /**
     * Display a listing of expenses.
     */
    public function index(Request $request): Response
    {
        $query = Expense::query();

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        // Filter by year
        if ($request->filled('year')) {
            $query->whereYear('date', $request->input('year'));
        }

        // Filter by month
        if ($request->filled('month')) {
            $query->whereMonth('date', $request->input('month'));
        }

        // Filter by provider
        if ($request->filled('provider')) {
            $query->where('provider_name', 'like', '%' . $request->input('provider') . '%');
        }

        $expenses = $query
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        // Get summary for current filters
        $summaryQuery = Expense::query();
        if ($request->filled('category')) {
            $summaryQuery->where('category', $request->input('category'));
        }
        if ($request->filled('year')) {
            $summaryQuery->whereYear('date', $request->input('year'));
        }
        if ($request->filled('month')) {
            $summaryQuery->whereMonth('date', $request->input('month'));
        }

        $summary = $summaryQuery->selectRaw('
            SUM(amount_ht) as total_ht,
            SUM(amount_vat) as total_vat,
            SUM(amount_ttc) as total_ttc,
            COUNT(*) as count
        ')->first();

        // Get available years for filter
        $years = Expense::selectRaw(DatabaseHelper::distinctYear('date'))
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->values();

        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'summary' => [
                'total_ht' => $summary->total_ht ?? 0,
                'total_vat' => $summary->total_vat ?? 0,
                'total_ttc' => $summary->total_ttc ?? 0,
                'count' => $summary->count ?? 0,
            ],
            'filters' => [
                'category' => $request->input('category'),
                'year' => $request->input('year'),
                'month' => $request->input('month'),
                'provider' => $request->input('provider'),
            ],
            'categories' => $this->getCategoriesForSelect(),
            'years' => $years,
            'months' => $this->getMonthsForSelect(),
        ]);
    }

    /**
     * Show the form for creating a new expense.
     */
    public function create(): Response
    {
        return Inertia::render('Expenses/Create', [
            'categories' => $this->getCategoriesForSelect(),
            'vatRates' => $this->getVatRates(),
            'paymentMethods' => $this->getPaymentMethodsForSelect(),
        ]);
    }

    /**
     * Store a newly created expense.
     */
    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $expense = Expense::create($request->validated());

        // Handle attachment upload
        if ($request->hasFile('attachment')) {
            $expense->addMediaFromRequest('attachment')
                ->toMediaCollection('attachments');
        }

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Dépense enregistrée.');
    }

    /**
     * Display the specified expense.
     */
    public function show(Expense $expense): Response
    {
        return Inertia::render('Expenses/Show', [
            'expense' => array_merge($expense->toArray(), [
                'category_label' => $expense->category_label,
                'payment_method_label' => $expense->payment_method_label,
                'attachment_url' => $expense->attachment_url,
                'attachment_filename' => $expense->attachment_filename,
            ]),
        ]);
    }

    /**
     * Show the form for editing the specified expense.
     */
    public function edit(Expense $expense): Response
    {
        return Inertia::render('Expenses/Edit', [
            'expense' => array_merge($expense->toArray(), [
                'attachment_url' => $expense->attachment_url,
                'attachment_filename' => $expense->attachment_filename,
            ]),
            'categories' => $this->getCategoriesForSelect(),
            'vatRates' => $this->getVatRates(),
            'paymentMethods' => $this->getPaymentMethodsForSelect(),
        ]);
    }

    /**
     * Update the specified expense.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $expense->update($request->validated());

        // Handle attachment upload
        if ($request->hasFile('attachment')) {
            $expense->clearMediaCollection('attachments');
            $expense->addMediaFromRequest('attachment')
                ->toMediaCollection('attachments');
        }

        // Handle attachment removal
        if ($request->boolean('remove_attachment')) {
            $expense->clearMediaCollection('attachments');
        }

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Dépense mise à jour.');
    }

    /**
     * Remove the specified expense.
     */
    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()
            ->route('expenses.index')
            ->with('success', 'Dépense supprimée.');
    }

    /**
     * Get summary for a period.
     */
    public function summary(Request $request): Response
    {
        $year = $request->input('year', now()->year);

        $monthlySummary = Expense::getMonthlySummary($year);
        $yearSummary = Expense::getSummary($year);

        // Get available years
        $years = Expense::selectRaw(DatabaseHelper::distinctYear('date'))
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->values();

        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        return Inertia::render('Expenses/Summary', [
            'year' => $year,
            'years' => $years,
            'monthlySummary' => $monthlySummary,
            'yearSummary' => $yearSummary,
            'categories' => Expense::getCategories(),
        ]);
    }

    /**
     * Get categories for select.
     */
    private function getCategoriesForSelect(): array
    {
        $categories = Expense::getCategories();
        return collect($categories)->map(fn ($label, $value) => [
            'value' => $value,
            'label' => $label,
        ])->values()->toArray();
    }

    /**
     * Get payment methods for select.
     */
    private function getPaymentMethodsForSelect(): array
    {
        $methods = Expense::getPaymentMethods();
        return collect($methods)->map(fn ($label, $value) => [
            'value' => $value,
            'label' => $label,
        ])->values()->toArray();
    }

    /**
     * Get months for select.
     */
    private function getMonthsForSelect(): array
    {
        return [
            ['value' => '01', 'label' => 'Janvier'],
            ['value' => '02', 'label' => 'Février'],
            ['value' => '03', 'label' => 'Mars'],
            ['value' => '04', 'label' => 'Avril'],
            ['value' => '05', 'label' => 'Mai'],
            ['value' => '06', 'label' => 'Juin'],
            ['value' => '07', 'label' => 'Juillet'],
            ['value' => '08', 'label' => 'Août'],
            ['value' => '09', 'label' => 'Septembre'],
            ['value' => '10', 'label' => 'Octobre'],
            ['value' => '11', 'label' => 'Novembre'],
            ['value' => '12', 'label' => 'Décembre'],
        ];
    }

    /**
     * Get VAT rates for select based on seller's country.
     */
    private function getVatRates(): array
    {
        $settings = \App\Models\BusinessSettings::getInstance();

        // Get country-specific VAT rates
        $countryRates = $settings?->getVatRates() ?? config('countries.LU.vat_rates', []);

        $rates = [];
        foreach ($countryRates as $rate) {
            $rates[] = [
                'value' => $rate['value'],
                'label' => $rate['label'],
                'default' => $rate['default'] ?? false,
            ];
        }

        return $rates;
    }
}
