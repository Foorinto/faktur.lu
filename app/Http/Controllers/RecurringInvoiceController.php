<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\RecurringInvoice;
use App\Rules\SalesVatRateAllowed;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RecurringInvoiceController extends Controller
{
    public function index(): Response
    {
        $recurringInvoices = RecurringInvoice::where('user_id', auth()->id())
            ->with(['client:id,name', 'items'])
            ->latest()
            ->paginate(20);

        return Inertia::render('RecurringInvoices/Index', [
            'recurringInvoices' => $recurringInvoices,
            // La liste reste ouverte à tous les plans pour que les récurrences
            // héritées puissent être arrêtées ; seule la création est réservée.
            'canCreate' => app(\App\Services\PlanService::class)
                ->hasFeature(auth()->user(), 'recurring_invoices'),
        ]);
    }

    public function create(): Response
    {
        $clients = Client::where('user_id', auth()->id())
            ->orderBy('name')
            ->get(['id', 'name', 'currency', 'vat_number', 'default_vat_rate', 'status']);

        return Inertia::render('RecurringInvoices/Create', [
            'clients' => $clients,
            'frequencies' => RecurringInvoice::FREQUENCIES,
            'defaultVatRate' => auth()->user()->businessSettings?->default_vat_rate ?? 17,
            'isVatExempt' => auth()->user()->businessSettings?->isVatExempt() ?? true,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'nullable|string|max:255',
            'frequency' => 'required|in:'.implode(',', RecurringInvoice::FREQUENCIES),
            'next_invoice_date' => 'required|date|after_or_equal:today',
            'ends_at' => 'nullable|date|after:next_invoice_date',
            'auto_finalize' => 'boolean',
            'auto_send' => 'boolean',
            'payment_delay_days' => 'integer|min:0|max:365',
            'notes' => 'nullable|string|max:5000',
            'vat_mention' => 'nullable|string|max:500',
            'footer_message' => 'nullable|string|max:10000',
            'currency' => 'string|size:3',
            'items' => 'required|array|min:1',
            'items.*.title' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'required|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_type' => 'nullable|in:percent,amount',
            'items.*.discount_value' => 'nullable|numeric|min:0',
            'items.*.vat_rate' => ['required', 'numeric', 'min:0', 'max:100', new SalesVatRateAllowed],
            'discounts' => 'nullable|array',
            'discounts.*.label' => 'nullable|string|max:255',
            'discounts.*.type' => 'required|in:percent,amount',
            'discounts.*.value' => 'required|numeric|min:0',
        ]);

        // Verify client belongs to user
        $client = Client::where('id', $validated['client_id'])
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $recurring = RecurringInvoice::create([
            'user_id' => auth()->id(),
            'client_id' => $validated['client_id'],
            'title' => $validated['title'] ?? null,
            'frequency' => $validated['frequency'],
            'next_invoice_date' => $validated['next_invoice_date'],
            'ends_at' => $validated['ends_at'] ?? null,
            'auto_finalize' => $validated['auto_finalize'] ?? false,
            'auto_send' => $validated['auto_send'] ?? false,
            'payment_delay_days' => $validated['payment_delay_days'] ?? 30,
            'notes' => $validated['notes'] ?? null,
            'vat_mention' => $validated['vat_mention'] ?? null,
            'footer_message' => $validated['footer_message'] ?? null,
            'currency' => $validated['currency'] ?? 'EUR',
        ]);

        foreach ($validated['items'] as $index => $item) {
            $recurring->items()->create([
                'title' => $item['title'],
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'],
                'unit' => $item['unit'],
                'unit_price' => $item['unit_price'],
                'discount_type' => $item['discount_type'] ?? 'percent',
                'discount_value' => $item['discount_value'] ?? 0,
                'vat_rate' => $item['vat_rate'],
                'sort_order' => $index,
            ]);
        }

        foreach ($validated['discounts'] ?? [] as $index => $discount) {
            $recurring->discounts()->create([
                'label' => $discount['label'] ?? null,
                'type' => $discount['type'],
                'value' => $discount['value'],
                'sort_order' => $index,
            ]);
        }

        return redirect()->route('recurring-invoices.index')
            ->with('success', __('app.recurring_flash.created'));
    }

    public function edit(RecurringInvoice $recurringInvoice): Response
    {
        abort_unless((int) $recurringInvoice->user_id === (int) auth()->id(), 403);

        $recurringInvoice->load(['items', 'discounts']);

        $clients = Client::where('user_id', auth()->id())
            ->orderBy('name')
            ->get(['id', 'name', 'currency', 'vat_number', 'default_vat_rate', 'status']);

        return Inertia::render('RecurringInvoices/Edit', [
            'recurringInvoice' => $recurringInvoice,
            'clients' => $clients,
            'frequencies' => RecurringInvoice::FREQUENCIES,
            'defaultVatRate' => auth()->user()->businessSettings?->default_vat_rate ?? 17,
            'isVatExempt' => auth()->user()->businessSettings?->isVatExempt() ?? true,
        ]);
    }

    public function update(Request $request, RecurringInvoice $recurringInvoice)
    {
        abort_unless((int) $recurringInvoice->user_id === (int) auth()->id(), 403);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'nullable|string|max:255',
            'frequency' => 'required|in:'.implode(',', RecurringInvoice::FREQUENCIES),
            'next_invoice_date' => 'required|date',
            'ends_at' => 'nullable|date|after:next_invoice_date',
            'is_active' => 'boolean',
            'auto_finalize' => 'boolean',
            'auto_send' => 'boolean',
            'payment_delay_days' => 'integer|min:0|max:365',
            'notes' => 'nullable|string|max:5000',
            'vat_mention' => 'nullable|string|max:500',
            'footer_message' => 'nullable|string|max:10000',
            'currency' => 'string|size:3',
            'items' => 'required|array|min:1',
            'items.*.title' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit' => 'required|string',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_type' => 'nullable|in:percent,amount',
            'items.*.discount_value' => 'nullable|numeric|min:0',
            'items.*.vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'discounts' => 'nullable|array',
            'discounts.*.label' => 'nullable|string|max:255',
            'discounts.*.type' => 'required|in:percent,amount',
            'discounts.*.value' => 'required|numeric|min:0',
        ]);

        $recurringInvoice->update([
            'client_id' => $validated['client_id'],
            'title' => $validated['title'] ?? null,
            'frequency' => $validated['frequency'],
            'next_invoice_date' => $validated['next_invoice_date'],
            'ends_at' => $validated['ends_at'] ?? null,
            'is_active' => $validated['is_active'] ?? true,
            'auto_finalize' => $validated['auto_finalize'] ?? false,
            'auto_send' => $validated['auto_send'] ?? false,
            'payment_delay_days' => $validated['payment_delay_days'] ?? 30,
            'notes' => $validated['notes'] ?? null,
            'vat_mention' => $validated['vat_mention'] ?? null,
            'footer_message' => $validated['footer_message'] ?? null,
            'currency' => $validated['currency'] ?? 'EUR',
        ]);

        // Replace items
        $recurringInvoice->items()->delete();
        foreach ($validated['items'] as $index => $item) {
            $recurringInvoice->items()->create([
                'title' => $item['title'],
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'],
                'unit' => $item['unit'],
                'unit_price' => $item['unit_price'],
                'discount_type' => $item['discount_type'] ?? 'percent',
                'discount_value' => $item['discount_value'] ?? 0,
                'vat_rate' => $item['vat_rate'],
                'sort_order' => $index,
            ]);
        }

        // Replace global discounts
        $recurringInvoice->discounts()->delete();
        foreach ($validated['discounts'] ?? [] as $index => $discount) {
            $recurringInvoice->discounts()->create([
                'label' => $discount['label'] ?? null,
                'type' => $discount['type'],
                'value' => $discount['value'],
                'sort_order' => $index,
            ]);
        }

        return redirect()->route('recurring-invoices.index')
            ->with('success', __('app.recurring_flash.updated'));
    }

    public function destroy(RecurringInvoice $recurringInvoice)
    {
        abort_unless((int) $recurringInvoice->user_id === (int) auth()->id(), 403);

        $recurringInvoice->delete();

        return redirect()->route('recurring-invoices.index')
            ->with('success', __('app.recurring_flash.deleted'));
    }

    /**
     * Duplicate a recurring invoice template.
     * Resets generation counters and is created INACTIVE so a copy
     * sent by mistake doesn't trigger billing.
     */
    public function duplicate(RecurringInvoice $recurringInvoice)
    {
        abort_unless((int) $recurringInvoice->user_id === (int) auth()->id(), 403);

        $recurringInvoice->load(['items', 'discounts']);

        $duplicate = DB::transaction(function () use ($recurringInvoice) {
            $copy = RecurringInvoice::create([
                'user_id' => auth()->id(),
                'client_id' => $recurringInvoice->client_id,
                'title' => $recurringInvoice->title,
                'frequency' => $recurringInvoice->frequency,
                'next_invoice_date' => now()->addDay()->toDateString(),
                'ends_at' => $recurringInvoice->ends_at,
                'is_active' => false,
                'auto_finalize' => $recurringInvoice->auto_finalize,
                'auto_send' => $recurringInvoice->auto_send,
                'payment_delay_days' => $recurringInvoice->payment_delay_days,
                'notes' => $recurringInvoice->notes,
                'vat_mention' => $recurringInvoice->vat_mention,
                'custom_vat_mention' => $recurringInvoice->custom_vat_mention,
                'footer_message' => $recurringInvoice->footer_message,
                'currency' => $recurringInvoice->currency,
            ]);

            foreach ($recurringInvoice->items as $item) {
                $copy->items()->create([
                    'title' => $item->title,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'discount_type' => $item->discount_type,
                    'discount_value' => $item->discount_value,
                    'vat_rate' => $item->vat_rate,
                    'sort_order' => $item->sort_order,
                ]);
            }

            foreach ($recurringInvoice->discounts as $discount) {
                $copy->discounts()->create([
                    'label' => $discount->label,
                    'type' => $discount->type,
                    'value' => $discount->value,
                    'sort_order' => $discount->sort_order,
                ]);
            }

            return $copy;
        });

        return redirect()
            ->route('recurring-invoices.edit', $duplicate)
            ->with('success', __('app.recurring_flash.duplicated'));
    }

    public function toggleActive(RecurringInvoice $recurringInvoice)
    {
        abort_unless((int) $recurringInvoice->user_id === (int) auth()->id(), 403);

        $recurringInvoice->update(['is_active' => ! $recurringInvoice->is_active]);

        return back()->with('success', $recurringInvoice->is_active ? __('app.recurring_flash.activated') : __('app.recurring_flash.deactivated'));
    }
}
