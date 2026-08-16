<?php

namespace App\Services\Accounting;

use App\Models\AccountingSetting;
use App\Models\Invoice;
use Illuminate\Support\Collection;

class GenericCsvFormatter
{
    /**
     * Format invoices as a generic CSV (one line per invoice and sales account).
     *
     * Une facture donne une ligne, sauf lorsque ses articles se répartissent sur
     * plusieurs comptes de ventes — elle en donne alors une par compte, dont la
     * somme fait le total du document.
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

            foreach ($this->ventilerParCompte($invoice, $settings) as $compte => $part) {
                $lines[] = implode(';', [
                    $invoice->issued_at->format('d/m/Y'),
                    $invoice->number,
                    $this->escapeCsvField($invoice->client?->name ?? 'N/A'),
                    $clientId,
                    $this->formatAmount($part['ht']),
                    $this->formatAmount($part['tva']),
                    $this->formatAmount($part['ttc']),
                    number_format($mainVatRate, 0) . '%',
                    $compte,
                    $vatAccount,
                    $settings->sales_journal,
                    $invoice->due_at?->format('d/m/Y') ?? '',
                    $invoice->type === Invoice::TYPE_CREDIT_NOTE ? 'Avoir' : 'Facture',
                ]);
            }
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
     * Ventile une facture par compte de ventes.
     *
     * Ce format s'écrit document par document, non écriture par écriture : la
     * colonne « Compte Ventes » portait donc le compte du paramétrage, le même
     * pour tout le monde. Des frais refacturés sur le 708 y retombaient sur le
     * compte de chiffre d'affaires, et la ventilation par article devenait
     * invisible précisément dans le fichier que l'utilisateur ouvre.
     *
     * Une facture dont toutes les lignes partagent un compte — le cas de toutes
     * celles émises avant cette fonctionnalité — produit une seule ligne, à
     * l'identique. Les autres en produisent une par compte, et la somme des
     * lignes reste le total de la facture : c'est ce qu'un comptable vérifie en
     * premier.
     *
     * @return array<string, array{ht: float, tva: float, ttc: float}>
     */
    protected function ventilerParCompte(Invoice $invoice, AccountingSetting $settings): array
    {
        if (! $invoice->relationLoaded('items')) {
            $invoice->load('items');
        }

        $totalHt = round((float) $invoice->total_ht, 2);
        $totalTva = round((float) $invoice->total_vat, 2);
        $totalTtc = round((float) $invoice->total_ttc, 2);

        $brut = $invoice->items
            ->groupBy(fn ($item) => $item->pcn_account ?: $settings->sales_account)
            ->map(fn ($lignes) => round((float) $lignes->sum('total_ht'), 2))
            ->filter(fn ($montant) => abs($montant) > 0.001);

        // Un seul compte — ou aucune ligne : on garde le total du document tel
        // quel, sans le faire transiter par un prorata qui ne peut que déplacer
        // des centimes.
        if ($brut->count() <= 1) {
            return [($brut->keys()->first() ?? $settings->sales_account) => [
                'ht' => $totalHt,
                'tva' => $totalTva,
                'ttc' => $totalTtc,
            ]];
        }

        $sommeBrute = round($brut->sum(), 2);
        $parts = [];
        $cumul = ['ht' => 0.0, 'tva' => 0.0, 'ttc' => 0.0];

        foreach ($brut as $compte => $montant) {
            $quotient = $montant / $sommeBrute;

            $parts[$compte] = [
                'ht' => round($totalHt * $quotient, 2),
                'tva' => round($totalTva * $quotient, 2),
                'ttc' => round($totalTtc * $quotient, 2),
            ];

            foreach ($cumul as $cle => $_) {
                $cumul[$cle] = round($cumul[$cle] + $parts[$compte][$cle], 2);
            }
        }

        // Le reliquat d'arrondi va au plus gros compte : la somme des lignes
        // doit faire le total du document au centime près, sinon le fichier se
        // contredit lui-même.
        $principal = $brut->sortByDesc(fn ($m) => abs($m))->keys()->first();
        $parts[$principal]['ht'] = round($parts[$principal]['ht'] + ($totalHt - $cumul['ht']), 2);
        $parts[$principal]['tva'] = round($parts[$principal]['tva'] + ($totalTva - $cumul['tva']), 2);
        $parts[$principal]['ttc'] = round($parts[$principal]['ttc'] + ($totalTtc - $cumul['ttc']), 2);

        return $parts;
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
