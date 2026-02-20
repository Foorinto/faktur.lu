<?php

namespace App\Services\Accounting;

use App\Models\AccountingSetting;

class Sage100Formatter
{
    /**
     * Format entries as Sage 100 CSV (semicolon-separated).
     *
     * Columns: Journal;Date;Compte général;Compte tiers;N° pièce;Libellé;Débit;Crédit;Échéance
     */
    public function format(array $entries, AccountingSetting $settings): string
    {
        $lines = [];

        // Header
        $lines[] = implode(';', [
            'Journal',
            'Date',
            'Compte général',
            'Compte tiers',
            'N° pièce',
            'Libellé',
            'Débit',
            'Crédit',
            'Échéance',
        ]);

        foreach ($entries as $entry) {
            $lines[] = implode(';', [
                $entry['journal'],
                $entry['date']->format('d/m/Y'),
                $entry['account'],
                $entry['third_party'] ?? '',
                $entry['piece'],
                $this->escapeCsvField($entry['label']),
                $entry['debit'] > 0 ? $this->formatAmount($entry['debit']) : '',
                $entry['credit'] > 0 ? $this->formatAmount($entry['credit']) : '',
                $entry['due_date'] ? $entry['due_date']->format('d/m/Y') : '',
            ]);
        }

        return implode("\r\n", $lines);
    }

    /**
     * Format amount with comma as decimal separator (French convention).
     */
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
