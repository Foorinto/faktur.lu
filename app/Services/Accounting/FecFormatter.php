<?php

namespace App\Services\Accounting;

use App\Models\AccountingSetting;

/**
 * Génère un Fichier des Écritures Comptables (FEC) conforme à la norme DGFiP
 * (art. L47 A-I du LPF). Fichier texte tabulé, 18 colonnes dans l'ordre imposé.
 *
 * Réutilise les écritures en partie double produites par AccountingExportService::buildEntries().
 * Toutes les lignes d'une même pièce (facture) partagent le même EcritureNum.
 */
class FecFormatter
{
    /** Les 18 champs obligatoires du FEC, dans l'ordre. */
    private const HEADERS = [
        'JournalCode', 'JournalLib', 'EcritureNum', 'EcritureDate', 'CompteNum', 'CompteLib',
        'CompAuxNum', 'CompAuxLib', 'PieceRef', 'PieceDate', 'EcritureLib', 'Debit', 'Credit',
        'EcritureLet', 'DateLet', 'ValidDate', 'Montantdevise', 'Idevise',
    ];

    public function format(array $entries, AccountingSetting $settings): string
    {
        $lines = [implode("\t", self::HEADERS)];

        $ecritureNum = 0;
        $lastPiece = null;

        foreach ($entries as $entry) {
            // Nouvelle écriture à chaque changement de pièce (facture).
            if (($entry['piece'] ?? null) !== $lastPiece) {
                $ecritureNum++;
                $lastPiece = $entry['piece'] ?? null;
            }

            $date = $entry['date']->format('Ymd');

            $lines[] = implode("\t", [
                $this->clean($entry['journal'] ?? ''),                 // JournalCode
                'Ventes',                                              // JournalLib (seules des ventes émises pour l'instant)
                (string) $ecritureNum,                                 // EcritureNum
                $date,                                                 // EcritureDate (AAAAMMJJ)
                $this->clean($entry['account'] ?? ''),                 // CompteNum
                $this->clean($entry['account_label'] ?? ''),           // CompteLib
                $this->clean($entry['third_party'] ?? ''),             // CompAuxNum
                $this->clean($entry['third_party_label'] ?? ''),       // CompAuxLib
                $this->clean($entry['piece'] ?? ''),                   // PieceRef
                $date,                                                 // PieceDate
                $this->clean($entry['label'] ?? ''),                   // EcritureLib
                $this->amount($entry['debit'] ?? 0),                   // Debit
                $this->amount($entry['credit'] ?? 0),                  // Credit
                '',                                                    // EcritureLet (lettrage)
                '',                                                    // DateLet
                $date,                                                 // ValidDate
                '',                                                    // Montantdevise
                '',                                                    // Idevise
            ]);
        }

        return implode("\r\n", $lines);
    }

    /** Montant avec virgule décimale (convention française), 0,00 si nul. */
    private function amount(float $amount): string
    {
        return $amount > 0 ? number_format($amount, 2, ',', '') : '0,00';
    }

    /** Nettoie les séparateurs (tab/retours) pour ne pas casser les colonnes. */
    private function clean($value): string
    {
        return str_replace(["\t", "\r", "\n"], ' ', (string) $value);
    }
}
