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
        $totals = app(\App\Services\DocumentTotalsCalculator::class)->compute($quote->items, $quote->discounts);
        $totalHt = $totals['total_ht'];
        $totalVat = $totals['total_vat'];
        $totalTtc = $totals['total_ttc'];

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
        $totals = app(\App\Services\DocumentTotalsCalculator::class)->compute($quote->items, $quote->discounts);
        $totalHt = $totals['total_ht'];
        $totalVat = $totals['total_vat'];
        $totalTtc = $totals['total_ttc'];

        return [
            'total_ht' => $totalHt,
            'total_vat' => $totalVat,
            'total_ttc' => $totalTtc,
        ];
    }
}
