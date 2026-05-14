<?php

namespace App\Http\Controllers;

use App\Http\Requests\Api\V1\StoreInvoiceItemRequest;
use App\Http\Requests\Api\V1\UpdateInvoiceItemRequest;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InvoiceItemController extends Controller
{
    /**
     * Store a new invoice item.
     */
    public function store(StoreInvoiceItemRequest $request, Invoice $invoice): RedirectResponse
    {
        $maxOrder = $invoice->items()->max('sort_order') ?? -1;

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'quantity' => $request->validated('quantity'),
            'unit' => $request->validated('unit'),
            'unit_price' => $request->validated('unit_price'),
            'vat_rate' => $request->validated('vat_rate'),
            'sort_order' => $request->validated('sort_order') ?? ($maxOrder + 1),
        ]);

        return back()->with('success', __('app.invoice_items_flash.created'));
    }

    /**
     * Update the specified invoice item.
     */
    public function update(UpdateInvoiceItemRequest $request, Invoice $invoice, InvoiceItem $item): RedirectResponse
    {
        abort_unless($item->invoice_id === $invoice->id, 404);

        $item->update($request->validated());

        return back()->with('success', __('app.invoice_items_flash.updated'));
    }

    /**
     * Move the specified invoice item up or down.
     */
    public function move(Request $request, Invoice $invoice, InvoiceItem $item): RedirectResponse
    {
        abort_unless($item->invoice_id === $invoice->id, 404);

        if ($invoice->isImmutable()) {
            return back()->with('error', __('app.invoice_items_flash.error_finalized'));
        }

        $direction = $request->input('direction');

        if (!in_array($direction, ['up', 'down'])) {
            return back()->with('error', __('app.invoice_items_flash.error_invalid_direction'));
        }

        $items = $invoice->items()->orderBy('sort_order')->get();
        $currentIndex = $items->search(fn($i) => $i->id === $item->id);

        if ($currentIndex === false) {
            return back()->with('error', __('app.invoice_items_flash.error_not_found'));
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
     * Remove the specified invoice item.
     */
    public function destroy(Invoice $invoice, InvoiceItem $item): RedirectResponse
    {
        abort_unless($item->invoice_id === $invoice->id, 404);

        if ($invoice->isImmutable()) {
            return back()->with('error', __('app.invoice_items_flash.error_finalized'));
        }

        $item->delete();

        return back()->with('success', __('app.invoice_items_flash.deleted'));
    }
}
