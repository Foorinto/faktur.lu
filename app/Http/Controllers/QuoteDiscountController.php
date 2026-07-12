<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use App\Models\QuoteDiscount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuoteDiscountController extends Controller
{
    private function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:255'],
            'type' => ['required', Rule::in(['percent', 'amount'])],
            'value' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function store(Request $request, Quote $quote): RedirectResponse
    {
        abort_unless($quote->canEdit(), 403);

        $validated = $request->validate($this->rules());
        $maxOrder = $quote->discounts()->max('sort_order') ?? -1;

        $quote->discounts()->create([
            'label' => $validated['label'] ?? null,
            'type' => $validated['type'],
            'value' => $validated['value'],
            'sort_order' => $maxOrder + 1,
        ]);

        return back();
    }

    public function update(Request $request, Quote $quote, QuoteDiscount $discount): RedirectResponse
    {
        abort_unless((int) $discount->quote_id === (int) $quote->id, 404);
        abort_unless($quote->canEdit(), 403);

        $discount->update($request->validate($this->rules()));

        return back();
    }

    public function destroy(Quote $quote, QuoteDiscount $discount): RedirectResponse
    {
        abort_unless((int) $discount->quote_id === (int) $quote->id, 404);
        abort_unless($quote->canEdit(), 403);

        $discount->delete();

        return back();
    }
}
