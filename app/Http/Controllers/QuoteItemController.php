<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\V1\StoreQuoteItemRequest;
use App\Http\Requests\Api\V1\UpdateQuoteItemRequest;
use App\Models\Quote;
use App\Models\QuoteItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class QuoteItemController extends Controller
{
    /**
     * Store a new quote item.
     */
    public function store(StoreQuoteItemRequest $request, Quote $quote): RedirectResponse
    {
        $maxOrder = $quote->items()->max('sort_order') ?? -1;

        QuoteItem::create([
            'quote_id' => $quote->id,
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'quantity' => $request->validated('quantity'),
            'unit' => $request->validated('unit'),
            'unit_price' => $request->validated('unit_price'),
            'vat_rate' => $request->validated('vat_rate'),
            'sort_order' => $request->validated('sort_order') ?? ($maxOrder + 1),
        ]);

        return back()->with('success', 'Ligne ajoutée.');
    }

    /**
     * Update the specified quote item.
     */
    public function update(UpdateQuoteItemRequest $request, Quote $quote, QuoteItem $item): RedirectResponse
    {
        $item->update($request->validated());

        return back()->with('success', 'Ligne mise à jour.');
    }

    /**
     * Move the specified quote item up or down.
     */
    public function move(Request $request, Quote $quote, QuoteItem $item): RedirectResponse
    {
        if (!$quote->canEdit()) {
            return back()->with('error', 'Impossible de modifier ce devis.');
        }

        $direction = $request->input('direction');

        if (!in_array($direction, ['up', 'down'])) {
            return back()->with('error', 'Direction invalide.');
        }

        $items = $quote->items()->orderBy('sort_order')->get();
        $currentIndex = $items->search(fn($i) => $i->id === $item->id);

        if ($currentIndex === false) {
            return back()->with('error', 'Ligne non trouvée.');
        }

        $targetIndex = $direction === 'up' ? $currentIndex - 1 : $currentIndex + 1;

        if ($targetIndex < 0 || $targetIndex >= $items->count()) {
            return back();
        }

        // Swap sort_order with the adjacent item
        $targetItem = $items[$targetIndex];
        $tempOrder = $item->sort_order;
        $item->sort_order = $targetItem->sort_order;
        $targetItem->sort_order = $tempOrder;

        // Save without triggering totals recalculation
        $item->saveQuietly();
        $targetItem->saveQuietly();

        return back();
    }

    /**
     * Remove the specified quote item.
     */
    public function destroy(Quote $quote, QuoteItem $item): RedirectResponse
    {
        if (!$quote->canEdit()) {
            return back()->with('error', 'Impossible de modifier ce devis.');
        }

        $item->delete();

        return back()->with('success', 'Ligne supprimée.');
    }
}
