<?php

namespace App\Actions;

use App\Models\Quote;

class CalculateQuoteTotalsAction
{
    /**
     * Calculate and update the quote totals from its items.
     */
    public function execute(Quote $quote): Quote
    {
        $totalHt = '0';
        $totalVat = '0';

        foreach ($quote->items as $item) {
            $totalHt = bcadd($totalHt, (string) $item->total_ht, 4);
            $totalVat = bcadd($totalVat, (string) $item->total_vat, 4);
        }

        $totalTtc = bcadd($totalHt, $totalVat, 4);

        // Update without triggering model events (to avoid infinite loop)
        Quote::withoutEvents(function () use ($quote, $totalHt, $totalVat, $totalTtc) {
            $quote->update([
                'total_ht' => $totalHt,
                'total_vat' => $totalVat,
                'total_ttc' => $totalTtc,
            ]);
        });

        return $quote->refresh();
    }

    /**
     * Calculate totals without persisting (for preview).
     */
    public function preview(Quote $quote): array
    {
        $totalHt = '0';
        $totalVat = '0';

        foreach ($quote->items as $item) {
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
