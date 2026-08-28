<?php

namespace App\Services\Accounting;

use App\Models\AccountingSetting;
use App\Models\Invoice;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Classeur à trois onglets : Ventes, Dépenses, Encaissements.
 *
 * Le CSV générique met les trois tableaux sur une seule feuille, séparés par
 * des lignes vides. C'est lisible pour un humain attentif et pénible pour tous
 * les autres : les colonnes ne s'alignent pas d'un tableau à l'autre, et un
 * tableur qui ouvre le fichier applique la largeur du premier aux suivants.
 *
 * Trois onglets règlent ça. Le CSV reste disponible : il s'importe, un classeur
 * se lit. Les cabinets qui alimentent un logiciel comptable veulent le premier,
 * ceux qui vérifient à l'œil préfèrent le second.
 *
 * ⚠️ Les montants sont écrits en NOMBRES, pas en chaînes. Un « 1 234,56 » texte
 * ne s'additionne pas, et la première chose que fait une fiduciaire est de
 * sélectionner une colonne pour en lire la somme.
 */
class XlsxFormatter
{
    use VentileLesFactures;

    /** Fond des lignes d'en-tête. */
    private const COULEUR_ENTETE = 'FFE8E8E8';

    public function format(Collection $invoices, AccountingSetting $settings, ?Collection $expenses = null): string
    {
        $classeur = new Spreadsheet;
        $classeur->removeSheetByIndex(0);

        $this->feuilleVentes($classeur, $invoices, $settings);

        if ($expenses !== null && $expenses->isNotEmpty()) {
            $this->feuilleDepenses($classeur, $expenses, $settings);
        }

        $this->feuilleEncaissements($classeur, $invoices);

        $classeur->setActiveSheetIndex(0);

        // Passage par un fichier temporaire : PhpSpreadsheet écrit dans un flux,
        // et le service attend une chaîne à confier au disque.
        $chemin = tempnam(sys_get_temp_dir(), 'xlsx');

        try {
            (new Xlsx($classeur))->save($chemin);

            return (string) file_get_contents($chemin);
        } finally {
            @unlink($chemin);
            $classeur->disconnectWorksheets();
        }
    }

    protected function feuilleVentes(Spreadsheet $classeur, Collection $invoices, AccountingSetting $settings): void
    {
        $feuille = $classeur->createSheet();
        $feuille->setTitle(__('app.export_sheet_sales'));

        $this->entetes($feuille, [
            'Date', 'N° Facture', 'Client', 'Code Client',
            'HT', 'TVA', 'TTC', 'Taux TVA',
            'Compte Ventes', 'Compte TVA', 'Journal', 'Échéance', 'Type',
        ]);

        $ligne = 2;

        foreach ($invoices as $invoice) {
            $codeClient = $settings->getClientAccountingId($invoice->client);

            foreach ($this->ventiler($invoice, $settings) as $part) {
                $feuille->setCellValue("A{$ligne}", $invoice->issued_at?->format('d/m/Y'));
                $feuille->setCellValue("B{$ligne}", $invoice->number);
                $feuille->setCellValue("C{$ligne}", $invoice->client?->name ?? 'N/A');
                $feuille->setCellValue("D{$ligne}", $codeClient);
                $feuille->setCellValue("E{$ligne}", $part['ht']);
                $feuille->setCellValue("F{$ligne}", $part['tva']);
                $feuille->setCellValue("G{$ligne}", $part['ttc']);
                $feuille->setCellValue("H{$ligne}", $part['taux'] / 100);
                $feuille->setCellValue("I{$ligne}", $part['compte']);
                $feuille->setCellValue("J{$ligne}", $settings->getVatAccount($part['taux']));
                $feuille->setCellValue("K{$ligne}", $settings->sales_journal);
                $feuille->setCellValue("L{$ligne}", $invoice->due_at?->format('d/m/Y') ?? '');
                $feuille->setCellValue("M{$ligne}", $invoice->type === Invoice::TYPE_CREDIT_NOTE ? 'Avoir' : 'Facture');
                $ligne++;
            }
        }

        $this->montants($feuille, ['E', 'F', 'G'], $ligne - 1);
        $this->pourcentages($feuille, 'H', $ligne - 1);
        $this->ajuster($feuille, 'M');
    }

    protected function feuilleDepenses(Spreadsheet $classeur, Collection $expenses, AccountingSetting $settings): void
    {
        $feuille = $classeur->createSheet();
        $feuille->setTitle(__('app.export_sheet_expenses'));

        $this->entetes($feuille, [
            'Date', 'Référence', 'Fournisseur', 'Catégorie',
            'HT', 'TVA', 'TTC', 'Taux TVA', 'TVA déductible', 'Journal',
        ]);

        $ligne = 2;

        foreach ($expenses as $expense) {
            $feuille->setCellValue("A{$ligne}", $expense->date?->format('d/m/Y'));
            $feuille->setCellValue("B{$ligne}", (string) ($expense->reference ?? ''));
            $feuille->setCellValue("C{$ligne}", (string) $expense->provider_name);
            $feuille->setCellValue("D{$ligne}", (string) $expense->category_label);
            $feuille->setCellValue("E{$ligne}", (float) $expense->amount_ht);
            $feuille->setCellValue("F{$ligne}", (float) $expense->amount_vat);
            $feuille->setCellValue("G{$ligne}", (float) $expense->amount_ttc);
            $feuille->setCellValue("H{$ligne}", (float) $expense->vat_rate / 100);
            $feuille->setCellValue("I{$ligne}", $expense->is_deductible ? 'Oui' : 'Non');
            $feuille->setCellValue("J{$ligne}", $settings->purchase_journal);
            $ligne++;
        }

        $this->montants($feuille, ['E', 'F', 'G'], $ligne - 1);
        $this->pourcentages($feuille, 'H', $ligne - 1);
        $this->ajuster($feuille, 'J');
    }

    /**
     * Les encaissements, triés par date tous documents confondus : c'est ainsi
     * qu'on rapproche un relevé bancaire.
     */
    protected function feuilleEncaissements(Spreadsheet $classeur, Collection $invoices): void
    {
        $feuille = $classeur->createSheet();
        $feuille->setTitle(__('app.export_sheet_payments'));

        $this->entetes($feuille, [
            'Date encaissement', 'N° Facture', 'Client', 'Montant', 'Moyen de paiement', 'Référence',
        ]);

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

        $ligne = 2;

        foreach ($encaissements as $e) {
            $feuille->setCellValue("A{$ligne}", $e['date']?->format('d/m/Y') ?? '');
            $feuille->setCellValue("B{$ligne}", $e['facture']);
            $feuille->setCellValue("C{$ligne}", $e['client']);
            $feuille->setCellValue("D{$ligne}", $e['montant']);
            $feuille->setCellValue("E{$ligne}", $e['moyen']);
            $feuille->setCellValue("F{$ligne}", $e['reference']);
            $ligne++;
        }

        $this->montants($feuille, ['D'], $ligne - 1);
        $this->ajuster($feuille, 'F');
    }

    // --- mise en forme -------------------------------------------------------

    protected function entetes($feuille, array $titres): void
    {
        $feuille->fromArray($titres, null, 'A1');

        // `getCellByColumnAndRow()` a disparu de PhpSpreadsheet 2.0 : on
        // convertit l'indice de colonne en lettre.
        $derniere = Coordinate::stringFromColumnIndex(count($titres));
        $plage = "A1:{$derniere}1";

        $feuille->getStyle($plage)->getFont()->setBold(true);
        $feuille->getStyle($plage)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB(self::COULEUR_ENTETE);
        $feuille->getStyle($plage)->getAlignment()
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Volet figé : une fiduciaire fait défiler des centaines de lignes et
        // ne doit pas perdre le nom des colonnes.
        $feuille->freezePane('A2');
    }

    protected function montants($feuille, array $colonnes, int $derniereLigne): void
    {
        if ($derniereLigne < 2) {
            return;
        }

        foreach ($colonnes as $colonne) {
            $feuille->getStyle("{$colonne}2:{$colonne}{$derniereLigne}")
                ->getNumberFormat()->setFormatCode('#,##0.00');
        }
    }

    protected function pourcentages($feuille, string $colonne, int $derniereLigne): void
    {
        if ($derniereLigne < 2) {
            return;
        }

        $feuille->getStyle("{$colonne}2:{$colonne}{$derniereLigne}")
            ->getNumberFormat()->setFormatCode('0%');
    }

    protected function ajuster($feuille, string $derniereColonne): void
    {
        foreach (range('A', $derniereColonne) as $colonne) {
            $feuille->getColumnDimension($colonne)->setAutoSize(true);
        }
    }
}
