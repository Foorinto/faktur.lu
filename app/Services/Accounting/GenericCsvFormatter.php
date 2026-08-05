<?php

namespace App\Services\Accounting;

use App\Models\AccountingSetting;
use App\Models\Invoice;
use Illuminate\Support\Collection;

class GenericCsvFormatter
{
    /**
     * Format invoices as a generic CSV (one line per invoice).
     *
     * Les dépenses, quand elles sont demandées, forment un second tableau après
     * une ligne vide. Elles n'ont pas les mêmes colonnes qu'une facture — ni
     * numéro, ni client, ni échéance — et les forcer dans le même en-tête
     * produirait un fichier illisible pour l'humain comme pour le tableur.
     */
    public function format(Collection $invoices, AccountingSetting $settings, ?Collection $expenses = null): string
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

        if ($expenses !== null && $expenses->isNotEmpty()) {
            $lines[] = '';
            $lines[] = implode(';', [
                'Date',
                'Référence',
                'Fournisseur',
                'Catégorie',
                'HT',
                'TVA',
                'TTC',
                'Taux TVA',
                'TVA déductible',
                'Journal',
            ]);

            foreach ($expenses as $expense) {
                $lines[] = implode(';', [
                    $expense->date->format('d/m/Y'),
                    $this->escapeCsvField((string) ($expense->reference ?? '')),
                    $this->escapeCsvField((string) $expense->provider_name),
                    $this->escapeCsvField((string) $expense->category_label),
                    $this->formatAmount((float) $expense->amount_ht),
                    $this->formatAmount((float) $expense->amount_vat),
                    $this->formatAmount((float) $expense->amount_ttc),
                    number_format((float) $expense->vat_rate, 0) . '%',
                    $expense->is_deductible ? 'Oui' : 'Non',
                    $settings->purchase_journal,
                ]);
            }
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
