<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceDiscount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InvoiceDiscountController extends Controller
{
    private function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['percent', 'amount'])],
            'value' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function store(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_unless($invoice->isDraft(), 403);

        $validated = $request->validate($this->rules());
        $maxOrder = $invoice->discounts()->max('sort_order') ?? -1;

        $invoice->discounts()->create([
            'label' => $validated['label'] ?? null,
            'type' => $validated['type'],
            'value' => $validated['value'],
            'sort_order' => $maxOrder + 1,
        ]);

        return back();
    }

    public function update(Request $request, Invoice $invoice, InvoiceDiscount $discount): RedirectResponse
    {
        abort_unless((int) $discount->invoice_id === (int) $invoice->id, 404);
        abort_unless($invoice->isDraft(), 403);

        $discount->update($request->validate($this->rules()));

        return back();
    }

    public function destroy(Invoice $invoice, InvoiceDiscount $discount): RedirectResponse
    {
        abort_unless((int) $discount->invoice_id === (int) $invoice->id, 404);
        abort_unless($invoice->isDraft(), 403);

        $discount->delete();

        return back();
    }
}
