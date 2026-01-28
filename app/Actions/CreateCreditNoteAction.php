<?php

namespace App\Actions;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateCreditNoteAction
{
    public function __construct(
        private FinalizeInvoiceAction $finalizeAction,
    ) {}

    /**
     * Create a credit note for an existing invoice.
     * The credit note will have negative amounts.
     *
     * @param Invoice $originalInvoice The invoice to create a credit note for
     * @param string|null $reason The reason for the credit note (from CREDIT_NOTE_REASONS)
     * @param array|null $itemIds Specific item IDs to include (null = all items for full credit note)
     * @param bool $finalize Whether to finalize the credit note immediately
     *
     * @throws ValidationException
     */
    public function execute(
        Invoice $originalInvoice,
        ?string $reason = 'cancellation',
        ?array $itemIds = null,
        bool $finalize = false
    ): Invoice {
        if (!$originalInvoice->canCreateCreditNote()) {
            throw ValidationException::withMessages([
                'invoice' => 'Impossible de créer une note de crédit pour cette facture.',
            ]);
        }

        return DB::transaction(function () use ($originalInvoice, $reason, $itemIds, $finalize) {
            // Determine if this is a partial or full credit note
            $isPartial = $itemIds !== null && count($itemIds) < $originalInvoice->items->count();
            $creditNoteType = $isPartial ? 'partiel' : 'total';

            // Build the notes message
            $notesMessage = $isPartial
                ? "Avoir partiel sur la facture n° {$originalInvoice->number}"
                : "Avoir annulant la facture n° {$originalInvoice->number}";

            // Add reason text if available
            $reasonText = Invoice::CREDIT_NOTE_REASONS[$reason] ?? null;
            if ($reasonText && $reason !== 'other') {
                $notesMessage .= "\nMotif : {$reasonText}";
            }

            // Create the credit note
            $creditNote = Invoice::create([
                'client_id' => $originalInvoice->client_id,
                'type' => Invoice::TYPE_CREDIT_NOTE,
                'credit_note_for' => $originalInvoice->id,
                'credit_note_reason' => $reason,
                'status' => Invoice::STATUS_DRAFT,
                'currency' => $originalInvoice->currency,
                'notes' => $notesMessage,
                // Copy VAT mention from original invoice
                'vat_mention' => $originalInvoice->vat_mention,
                'custom_vat_mention' => $originalInvoice->custom_vat_mention,
            ]);

            // Filter items if specific IDs provided
            $itemsToCredit = $itemIds !== null
                ? $originalInvoice->items->whereIn('id', $itemIds)
                : $originalInvoice->items;

            // Copy items with negative quantities
            foreach ($itemsToCredit as $item) {
                InvoiceItem::create([
                    'invoice_id' => $creditNote->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'quantity' => bcmul($item->quantity, '-1', 4), // Negative quantity
                    'unit' => $item->unit,
                    'unit_price' => $item->unit_price,
                    'vat_rate' => $item->vat_rate,
                    'sort_order' => $item->sort_order,
                ]);
            }

            // Finalize immediately if requested
            if ($finalize) {
                return $this->finalizeAction->execute($creditNote);
            }

            return $creditNote->refresh()->load('items');
        });
    }

    /**
     * Create a full credit note (cancels entire invoice).
     */
    public function createFullCreditNote(Invoice $invoice, ?string $reason = 'cancellation', bool $finalize = false): Invoice
    {
        return $this->execute($invoice, $reason, null, $finalize);
    }

    /**
     * Create a partial credit note (cancels specific items).
     */
    public function createPartialCreditNote(Invoice $invoice, array $itemIds, ?string $reason = 'billing_error', bool $finalize = false): Invoice
    {
        return $this->execute($invoice, $reason, $itemIds, $finalize);
    }
}
