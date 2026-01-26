<?php

namespace App\Actions;

use App\Models\Invoice;

class CalculateInvoiceTotalsAction
{
    /**
     * Calculate and update the invoice totals from its items.
     */
    public function execute(Invoice $invoice): Invoice
    {
        // Only recalculate for draft invoices
        if ($invoice->isImmutable()) {
            return $invoice;
        }

        $totalHt = '0';
        $totalVat = '0';

        foreach ($invoice->items as $item) {
            $totalHt = bcadd($totalHt, (string) $item->total_ht, 4);
            $totalVat = bcadd($totalVat, (string) $item->total_vat, 4);
        }

        $totalTtc = bcadd($totalHt, $totalVat, 4);

        // Update without triggering model events (to avoid infinite loop)
        Invoice::withoutEvents(function () use ($invoice, $totalHt, $totalVat, $totalTtc) {
            $invoice->update([
                'total_ht' => $totalHt,
                'total_vat' => $totalVat,
                'total_ttc' => $totalTtc,
            ]);
        });

        return $invoice->refresh();
    }

    /**
     * Calculate totals without persisting (for preview).
     */
    public function preview(Invoice $invoice): array
    {
        $totalHt = '0';
        $totalVat = '0';

        foreach ($invoice->items as $item) {
            $totalHt = bcadd($totalHt, (string) $item->total_ht, 4);
            $totalVat = bcadd($totalVat, (string) $item->total_vat, 4);
        }

        $totalTtc = bcadd($totalHt, $totalVat, 4);

        return [
            'total_ht' => $totalHt,
            'total_vat' => $totalVat,
            'total_ttc' => $totalTtc,
        ];
    }
}
