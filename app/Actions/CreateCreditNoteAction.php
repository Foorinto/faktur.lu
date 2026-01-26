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
     * @throws ValidationException
     */
    public function execute(Invoice $originalInvoice, bool $finalize = false): Invoice
    {
        if (!$originalInvoice->canCreateCreditNote()) {
            throw ValidationException::withMessages([
                'invoice' => 'Impossible de créer une note de crédit pour cette facture.',
            ]);
        }

        return DB::transaction(function () use ($originalInvoice, $finalize) {
            // Create the credit note
            $creditNote = Invoice::create([
                'client_id' => $originalInvoice->client_id,
                'type' => Invoice::TYPE_CREDIT_NOTE,
                'credit_note_for' => $originalInvoice->id,
                'status' => Invoice::STATUS_DRAFT,
                'currency' => $originalInvoice->currency,
                'notes' => "Note de crédit pour la facture n° {$originalInvoice->number}",
            ]);

            // Copy items with negative amounts
            foreach ($originalInvoice->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $creditNote->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'quantity' => bcmul($item->quantity, '-1', 4), // Negative quantity
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
}
