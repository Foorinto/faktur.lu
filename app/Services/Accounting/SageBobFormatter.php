<?php

namespace App\Services\Accounting;

use App\Models\AccountingSetting;

class SageBobFormatter
{
    /**
     * Format entries as Sage BOB 50 ASCII fixed-position file.
     *
     * Positions:
     *  1-2:   Type (2 chars) — "01" = écriture
     *  3-10:  Journal (8 chars, right-padded)
     * 11-18:  Date (8 chars, YYYYMMDD)
     * 19-26:  N° pièce (8 chars, right-padded)
     * 27-34:  Compte (8 chars, right-padded)
     * 35-49:  Montant (15 chars, signed +/-, left-padded zeros)
     * 50-89:  Libellé (40 chars, right-padded)
     * 90-97:  Échéance (8 chars, YYYYMMDD)
     * 98-105: Tiers (8 chars, right-padded)
     */
    public function format(array $entries, AccountingSetting $settings): string
    {
        $lines = [];

        foreach ($entries as $entry) {
            $amount = $entry['debit'] > 0 ? $entry['debit'] : -$entry['credit'];
            $amountFormatted = $this->formatAmount($amount);

            $line = '01'                                                           // Type (2)
                . str_pad(mb_substr($entry['journal'], 0, 8), 8)                   // Journal (8)
                . $entry['date']->format('Ymd')                                    // Date (8)
                . str_pad(mb_substr($entry['piece'], 0, 8), 8)                     // N° pièce (8)
                . str_pad(mb_substr($entry['account'], 0, 8), 8)                   // Compte (8)
                . $amountFormatted                                                 // Montant (15)
                . str_pad(mb_substr($entry['label'], 0, 40), 40)                   // Libellé (40)
                . ($entry['due_date'] ? $entry['due_date']->format('Ymd') : '        ') // Échéance (8)
                . str_pad(mb_substr($entry['third_party'] ?? '', 0, 8), 8);        // Tiers (8)

            $lines[] = $line;
        }

        $content = implode("\r\n", $lines);

        // Sage BOB requires ISO-8859-1 encoding
        return mb_convert_encoding($content, 'ISO-8859-1', 'UTF-8');
    }

    /**
     * Format amount as signed 15-char string: +0000000125000 or -0000000018162
     * Amount in cents (multiply by 100), padded with zeros.
     */
    protected function formatAmount(float $amount): string
    {
        $sign = $amount >= 0 ? '+' : '-';
        $cents = abs(round($amount * 100));

        return $sign . str_pad((string) $cents, 14, '0', STR_PAD_LEFT);
    }
}
