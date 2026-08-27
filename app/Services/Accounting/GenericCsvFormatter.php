<?php

namespace App\Services\Accounting;

use App\Models\AccountingSetting;
use App\Models\Invoice;
use Illuminate\Support\Collection;

class GenericCsvFormatter
{
    use VentileLesFactures;

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

            foreach ($this->ventiler($invoice, $settings) as $part) {
                $lines[] = implode(';', [
                    $invoice->issued_at->format('d/m/Y'),
                    $invoice->number,
                    $this->escapeCsvField($invoice->client?->name ?? 'N/A'),
                    $clientId,
                    $this->formatAmount($part['ht']),
                    $this->formatAmount($part['tva']),
                    $this->formatAmount($part['ttc']),
                    number_format($part['taux'], 0) . '%',
                    $part['compte'],
                    $settings->getVatAccount($part['taux']),
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

        $lines = array_merge($lines, $this->lignesEncaissements($invoices));

        return $bom . implode("\r\n", $lines);
    }

    /**
     * Ventile une facture en lignes cohérentes : un taux, un compte, un montant.
     *
     * Ce format s'écrit document par document, non écriture par écriture. Il en
     * découlait deux approximations, dans la même colonne du même fichier.
     *
     * Le compte de ventes, d'abord : il portait celui du paramétrage quoi qu'il
     * arrive, si bien que des frais refacturés sur le 708 retombaient sur le
     * chiffre d'affaires — la ventilation par article restait invisible dans le
     * seul format que l'utilisateur ouvre lui-même.
     *
     * Le taux de TVA, ensuite, et c'était le plus gênant : une facture à
     * plusieurs taux n'affichait que le DOMINANT, avec le total de sa TVA en
     * face. La ligne ne se recoupait donc pas — 5 764,27 € annoncés à 3 % avec
     * 471,36 € de TVA, quand 3 % en font 172,93 —, et l'intégralité de la TVA
     * partait sur le compte du taux dominant. Une TVA à 17 % comptabilisée en
     * 461300 n'est pas une présentation discutable : c'est une écriture fausse.
     *
     * D'où une ligne par couple (taux, compte). Les bases nettes viennent de
     * `vat_breakdown`, qui ventile déjà les remises globales par taux : refaire
     * ce calcul ici l'aurait dupliqué, et les deux auraient divergé un jour.
     *
     * Une facture à un seul taux et un seul compte — toutes celles émises
     * jusqu'ici — produit une ligne unique, rigoureusement identique à avant.
     *
     * @return list<array{ht: float, tva: float, ttc: float, taux: float, compte: string}>
     */
    /**
     * Troisième tableau : les encaissements (FEAT-114).
     *
     * Ils ne peuvent pas être une colonne du journal des ventes. Une facture
     * réglée moitié espèces moitié virement porterait alors deux moyens sur une
     * même ligne — ou un seul, faux. Et la date d'encaissement diffère de la
     * date d'émission, qui est celle du journal des ventes.
     *
     * C'est ce tableau qui permet à la fiduciaire de rapprocher la banque et la
     * caisse : une ligne par mouvement d'argent, à sa date réelle.
     *
     * Le moyen absent s'écrit « Non renseigné ». Les encaissements repris de
     * l'ancien fonctionnement n'en ont pas, et les ranger sous « Virement »
     * fabriquerait une écriture qui n'a jamais existé.
     *
     * @param  Collection<int, Invoice>  $invoices
     * @return array<int, string>
     */
    protected function lignesEncaissements(Collection $invoices): array
    {
        $encaissements = $invoices
            ->flatMap(fn (Invoice $invoice) => $invoice->payments->map(fn ($p) => [
                'date' => $p->paid_at,
                'facture' => $invoice->number,
                'client' => $invoice->client?->name ?? 'N/A',
                'montant' => (float) $p->amount,
                'moyen' => $p->methodLabel(),
                'reference' => (string) ($p->reference ?? ''),
            ]))
            ->sortBy('date')
            ->values();

        if ($encaissements->isEmpty()) {
            return [];
        }

        $lines = [''];
        $lines[] = implode(';', [
            'Date encaissement',
            'N° Facture',
            'Client',
            'Montant',
            'Moyen de paiement',
            'Référence',
        ]);

        foreach ($encaissements as $e) {
            $lines[] = implode(';', [
                $e['date']?->format('d/m/Y') ?? '',
                $e['facture'],
                $this->escapeCsvField($e['client']),
                $this->formatAmount($e['montant']),
                $this->escapeCsvField($e['moyen']),
                $this->escapeCsvField($e['reference']),
            ]);
        }

        return $lines;
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
