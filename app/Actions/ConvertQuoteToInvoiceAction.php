<?php

namespace App\Actions;

use App\Models\Invoice;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;

class ConvertQuoteToInvoiceAction
{
    /**
     * Convert an accepted quote to a draft invoice.
     *
     * @throws \InvalidArgumentException
     */
    public function execute(Quote $quote): Invoice
    {
        // Verify the quote can be converted
        if (!$quote->canConvert()) {
            throw new \InvalidArgumentException(
                'Ce devis ne peut pas être converti. Seuls les devis acceptés peuvent être convertis en facture.'
            );
        }

        return DB::transaction(function () use ($quote) {
            // Create the invoice as a draft
            $invoice = Invoice::create([
                'client_id' => $quote->client_id,
                'status' => Invoice::STATUS_DRAFT,
                'type' => Invoice::TYPE_INVOICE,
                'seller_snapshot' => $quote->seller_snapshot,
                'buyer_snapshot' => $quote->buyer_snapshot,
                'total_ht' => $quote->total_ht,
                'total_vat' => $quote->total_vat,
                'total_ttc' => $quote->total_ttc,
                'issued_at' => now(),
                'due_at' => now()->addDays(30),
                'notes' => $quote->notes,
                'currency' => $quote->currency,
            ]);

            // Copy all items from the quote to the invoice
            foreach ($quote->items as $quoteItem) {
                $invoice->items()->create([
                    'title' => $quoteItem->title,
                    'description' => $quoteItem->description,
                    'quantity' => $quoteItem->quantity,
                    'unit' => $quoteItem->unit,
                    'unit_price' => $quoteItem->unit_price,
                    'vat_rate' => $quoteItem->vat_rate,
                    'total_ht' => $quoteItem->total_ht,
                    'total_vat' => $quoteItem->total_vat,
                    'total_ttc' => $quoteItem->total_ttc,
                    'sort_order' => $quoteItem->sort_order,
                ]);
            }

            // Mark the quote as converted
            Quote::withoutEvents(function () use ($quote, $invoice) {
                $quote->update([
                    'status' => Quote::STATUS_CONVERTED,
                    'converted_to_invoice_id' => $invoice->id,
                ]);
            });

            return $invoice;
        });
    }
}
