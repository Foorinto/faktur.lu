<?php

namespace App\Actions;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class GenerateInvoiceNumberAction
{
    /**
     * Generate the next invoice number in the format YYYY-XXX.
     * Numbers are sequential per year and never reused.
     */
    public function execute(?int $year = null): string
    {
        $year = $year ?? now()->year;

        return DB::transaction(function () use ($year) {
            // Lock the table to prevent race conditions
            $lastInvoice = Invoice::query()
                ->whereYear('finalized_at', $year)
                ->whereNotNull('number')
                ->orderByRaw("CAST(SUBSTR(number, 6) AS INTEGER) DESC")
                ->lockForUpdate()
                ->first();

            if ($lastInvoice) {
                // Extract the sequence number from the last invoice
                $parts = explode('-', $lastInvoice->number);
                $lastNumber = (int) ($parts[1] ?? 0);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            // Format with padding (e.g., 2026-001)
            $padding = config('billing.invoice_number_padding', 3);

            return sprintf('%d-%0' . $padding . 'd', $year, $nextNumber);
        });
    }

    /**
     * Get the next invoice number without generating it (preview).
     */
    public function preview(?int $year = null): string
    {
        $year = $year ?? now()->year;

        $lastInvoice = Invoice::query()
            ->whereYear('finalized_at', $year)
            ->whereNotNull('number')
            ->orderByRaw("CAST(SUBSTR(number, 6) AS INTEGER) DESC")
            ->first();

        if ($lastInvoice) {
            $parts = explode('-', $lastInvoice->number);
            $lastNumber = (int) ($parts[1] ?? 0);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        $padding = config('billing.invoice_number_padding', 3);

        return sprintf('%d-%0' . $padding . 'd', $year, $nextNumber);
    }
}
