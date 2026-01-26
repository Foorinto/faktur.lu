<?php

namespace App\Actions;

use App\Exceptions\ImmutableInvoiceException;
use App\Models\BusinessSettings;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinalizeInvoiceAction
{
    public function __construct(
        private GenerateInvoiceNumberAction $generateNumber,
        private CalculateInvoiceTotalsAction $calculateTotals,
    ) {}

    /**
     * Finalize an invoice: generate number, create snapshots, lock it.
     *
     * @throws ImmutableInvoiceException
     * @throws ValidationException
     */
    public function execute(Invoice $invoice, ?string $issuedAt = null): Invoice
    {
        // Validate invoice can be finalized
        if ($invoice->status !== Invoice::STATUS_DRAFT) {
            throw ValidationException::withMessages([
                'status' => 'Seuls les brouillons peuvent être finalisés.',
            ]);
        }

        if ($invoice->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'La facture doit contenir au moins une ligne.',
            ]);
        }

        $settings = BusinessSettings::getInstance();
        if (!$settings) {
            throw ValidationException::withMessages([
                'settings' => 'Les paramètres de l\'entreprise doivent être configurés avant de finaliser une facture.',
            ]);
        }

        return DB::transaction(function () use ($invoice, $settings, $issuedAt) {
            // Recalculate totals one last time
            $this->calculateTotals->execute($invoice);
            $invoice->refresh();

            // Generate the invoice number
            $number = $this->generateNumber->execute();

            // Create snapshots
            $sellerSnapshot = $settings->toSnapshot();
            $buyerSnapshot = $invoice->client->toSnapshot();

            // Determine dates
            $issuedDate = $issuedAt ? now()->parse($issuedAt) : now();
            $paymentDays = config('billing.default_payment_days', 30);
            $dueDate = $invoice->due_at ?? $issuedDate->copy()->addDays($paymentDays);

            // Update invoice - use withoutEvents to bypass immutability check
            Invoice::withoutEvents(function () use ($invoice, $number, $sellerSnapshot, $buyerSnapshot, $issuedDate, $dueDate) {
                $invoice->update([
                    'number' => $number,
                    'status' => Invoice::STATUS_FINALIZED,
                    'seller_snapshot' => $sellerSnapshot,
                    'buyer_snapshot' => $buyerSnapshot,
                    'issued_at' => $issuedDate,
                    'due_at' => $dueDate,
                    'finalized_at' => now(),
                ]);
            });

            return $invoice->refresh();
        });
    }
}
