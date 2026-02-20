<?php

namespace App\Services\Accounting;

use App\Models\AccountingSetting;
use App\Models\Invoice;
use Illuminate\Support\Collection;

class GenericCsvFormatter
{
    /**
     * Format invoices as a generic CSV (one line per invoice).
     */
    public function format(Collection $invoices, AccountingSetting $settings): string
    {
        $lines = [];

        // UTF-8 BOM for Excel compatibility
        $bom = "\xEF\xBB\xBF";

        // Header
        $lines[] = implode(';', [
            'Date',
            'N° Facture',
            'Client',
            'Code Client',
            'HT',
            'TVA',
            'TTC',
            'Taux TVA',
            'Compte Ventes',
            'Compte TVA',
            'Journal',
            'Échéance',
            'Type',
        ]);

        foreach ($invoices as $invoice) {
            $clientId = $settings->getClientAccountingId($invoice->client);
            $mainVatRate = $this->getMainVatRate($invoice);
            $vatAccount = $settings->getVatAccount($mainVatRate);

            $lines[] = implode(';', [
                $invoice->issued_at->format('d/m/Y'),
                $invoice->number,
                $this->escapeCsvField($invoice->client?->name ?? 'N/A'),
                $clientId,
                $this->formatAmount($invoice->total_ht),
                $this->formatAmount($invoice->total_vat),
                $this->formatAmount($invoice->total_ttc),
                number_format($mainVatRate, 0) . '%',
                $settings->sales_account,
                $vatAccount,
                $settings->sales_journal,
                $invoice->due_at?->format('d/m/Y') ?? '',
                $invoice->type === Invoice::TYPE_CREDIT_NOTE ? 'Avoir' : 'Facture',
            ]);
        }

        return $bom . implode("\r\n", $lines);
    }

    /**
     * Get the main VAT rate from an invoice (highest HT amount).
     */
    protected function getMainVatRate(Invoice $invoice): float
    {
        if (!$invoice->relationLoaded('items')) {
            $invoice->load('items');
        }

        if ($invoice->items->isEmpty()) {
            return 0;
        }

        return $invoice->items
            ->groupBy('vat_rate')
            ->map(fn ($items) => $items->sum('total_ht'))
            ->sortDesc()
            ->keys()
            ->first() ?? 0;
    }

    protected function formatAmount(float $amount): string
    {
        return number_format($amount, 2, ',', '');
    }

    protected function escapeCsvField(string $value): string
    {
        if (str_contains($value, ';') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"' . str_replace('"', '""', $value) . '"';
        }

        return $value;
    }
}
