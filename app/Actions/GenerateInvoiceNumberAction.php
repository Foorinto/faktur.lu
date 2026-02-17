<?php

namespace App\Actions;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class GenerateInvoiceNumberAction
{
    /**
     * Prefix for regular invoices.
     */
    public const PREFIX_INVOICE = 'F';

    /**
     * Prefix for credit notes.
     */
    public const PREFIX_CREDIT_NOTE = 'AV';

    /**
     * Generate the next invoice/credit note number.
     * Format: PREFIX-YYYY-XXX (e.g., F-2026-001 or AV-2026-001)
     * Numbers are sequential per year and type, never reused.
     */
    public function execute(?int $year = null, string $type = Invoice::TYPE_INVOICE): string
    {
        $year = $year ?? now()->year;
        $prefix = $this->getPrefixForType($type);

        return DB::transaction(function () use ($year, $type, $prefix) {
            // Lock the table to prevent race conditions
            // Use withoutGlobalScope to search across ALL users (numbers must be globally unique)
            $lastInvoice = Invoice::withoutGlobalScope('user')
                ->whereYear('finalized_at', $year)
                ->where('type', $type)
                ->whereNotNull('number')
                ->where('number', 'like', $prefix . '-%')
                ->orderByRaw("CAST(SUBSTR(number, -3) AS INTEGER) DESC")
                ->lockForUpdate()
                ->first();

            if ($lastInvoice) {
                // Extract the sequence number from the last invoice/credit note
                // Format is PREFIX-YYYY-NNN, so the number is after the last dash
                $parts = explode('-', $lastInvoice->number);
                $lastNumber = (int) end($parts);
                $nextNumber = $lastNumber + 1;
            } else {
                $nextNumber = 1;
            }

            // Format with padding (e.g., F-2026-001 or AV-2026-001)
            $padding = config('billing.invoice_number_padding', 3);

            return sprintf('%s-%d-%0' . $padding . 'd', $prefix, $year, $nextNumber);
        });
    }

    /**
     * Get the next number without generating it (preview).
     */
    public function preview(?int $year = null, string $type = Invoice::TYPE_INVOICE): string
    {
        $year = $year ?? now()->year;
        $prefix = $this->getPrefixForType($type);

        // Use withoutGlobalScope to search across ALL users (numbers must be globally unique)
        $lastInvoice = Invoice::withoutGlobalScope('user')
            ->whereYear('finalized_at', $year)
            ->where('type', $type)
            ->whereNotNull('number')
            ->where('number', 'like', $prefix . '-%')
            ->orderByRaw("CAST(SUBSTR(number, -3) AS INTEGER) DESC")
            ->first();

        if ($lastInvoice) {
            $parts = explode('-', $lastInvoice->number);
            $lastNumber = (int) end($parts);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        $padding = config('billing.invoice_number_padding', 3);

        return sprintf('%s-%d-%0' . $padding . 'd', $prefix, $year, $nextNumber);
    }

    /**
     * Get the prefix for a given invoice type.
     */
    private function getPrefixForType(string $type): string
    {
        return match ($type) {
            Invoice::TYPE_CREDIT_NOTE => self::PREFIX_CREDIT_NOTE,
            default => self::PREFIX_INVOICE,
        };
    }
}
