<?php

namespace App\Services\Accounting;

use App\Models\AccountingExport;
use App\Models\AccountingSetting;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PurchaseCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class AccountingExportService
{
    /**
     * Get preview data for the export period.
     */
    public function getPreview(User $user, Carbon $periodStart, Carbon $periodEnd, array $options = []): array
    {
        $includeCreditNotes = $options['include_credit_notes'] ?? true;
        $scope = $this->scope($options);

        $invoices = $this->includesSales($scope)
            ? $this->getInvoicesForPeriod($user, $periodStart, $periodEnd, $includeCreditNotes)
            : collect();

        $expenses = $this->includesPurchases($scope)
            ? $this->getExpensesForPeriod($user, $periodStart, $periodEnd)
            : collect();

        $invoicesOnly = $invoices->where('type', Invoice::TYPE_INVOICE);
        $creditNotes = $invoices->where('type', Invoice::TYPE_CREDIT_NOTE);

        return [
            'invoices_count' => $invoicesOnly->count(),
            'credit_notes_count' => $creditNotes->count(),
            'total_ht' => round($invoices->sum('total_ht'), 2),
            'total_vat' => round($invoices->sum('total_vat'), 2),
            'total_ttc' => round($invoices->sum('total_ttc'), 2),
            'expenses_count' => $expenses->count(),
            'expenses_total_ht' => round($expenses->sum('amount_ht'), 2),
            'expenses_total_vat' => round($expenses->sum('amount_vat'), 2),
            'expenses_total_ttc' => round($expenses->sum('amount_ttc'), 2),
        ];
    }

    /**
     * Generate the export file.
     */
    public function generate(AccountingExport $export): void
    {
        $export->markAsProcessing();

        try {
            $user = $export->user;
            $options = $export->options ?? [];
            $includeCreditNotes = $options['include_credit_notes'] ?? true;

            $scope = $this->scope($options);

            $invoices = $this->includesSales($scope)
                ? $this->getInvoicesForPeriod($user, $export->period_start, $export->period_end, $includeCreditNotes)
                : collect();

            $expenses = $this->includesPurchases($scope)
                ? $this->getExpensesForPeriod($user, $export->period_start, $export->period_end)
                : collect();

            $settings = AccountingSetting::getForUser($user);
            $entries = array_merge(
                $this->buildEntries($invoices, $settings),
                $this->buildExpenseEntries($expenses, $settings)
            );
            $content = $this->formatContent($entries, $invoices, $settings, $export->format, $expenses);

            $fileName = $this->generateFileName($export);
            $filePath = "exports/accounting/{$fileName}";

            Storage::disk('local')->put($filePath, $content);

            $invoicesOnly = $invoices->where('type', Invoice::TYPE_INVOICE);
            $creditNotes = $invoices->where('type', Invoice::TYPE_CREDIT_NOTE);

            $export->markAsCompleted($filePath, $fileName, [
                'invoices_count' => $invoicesOnly->count(),
                'credit_notes_count' => $creditNotes->count(),
                'total_ht' => round($invoices->sum('total_ht'), 2),
                'total_vat' => round($invoices->sum('total_vat'), 2),
                'total_ttc' => round($invoices->sum('total_ttc'), 2),
                'expenses_count' => $expenses->count(),
                'expenses_total_ttc' => round($expenses->sum('amount_ttc'), 2),
                'entries_count' => count($entries),
            ]);
        } catch (\Exception $e) {
            $export->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate content directly (for accountant streaming downloads).
     */
    public function generateContent(User $user, Carbon $periodStart, Carbon $periodEnd, string $format, array $options = []): string
    {
        $includeCreditNotes = $options['include_credit_notes'] ?? true;
        $scope = $this->scope($options);

        $invoices = $this->includesSales($scope)
            ? $this->getInvoicesForPeriod($user, $periodStart, $periodEnd, $includeCreditNotes)
            : collect();

        $expenses = $this->includesPurchases($scope)
            ? $this->getExpensesForPeriod($user, $periodStart, $periodEnd)
            : collect();

        $settings = AccountingSetting::getForUser($user);
        $entries = array_merge(
            $this->buildEntries($invoices, $settings),
            $this->buildExpenseEntries($expenses, $settings)
        );

        return $this->formatContent($entries, $invoices, $settings, $format, $expenses);
    }

    /**
     * Get invoices for the given period, scoped to user.
     */
    protected function getInvoicesForPeriod(User $user, Carbon $start, Carbon $end, bool $includeCreditNotes = true): Collection
    {
        $query = $user->userInvoices()
            ->whereIn('status', [Invoice::STATUS_FINALIZED, Invoice::STATUS_SENT, Invoice::STATUS_PAID])
            ->whereBetween('issued_at', [$start->startOfDay(), $end->endOfDay()])
            ->with(['client', 'items'])
            ->orderBy('issued_at')
            ->orderBy('number');

        if (!$includeCreditNotes) {
            $query->where('type', Invoice::TYPE_INVOICE);
        }

        return $query->get();
    }

    /**
     * Build accounting entries from invoices.
     *
     * For each invoice:
     * 1. Debit: clients account (TTC amount)
     * 2. Credit: VAT account(s) (VAT amount per rate)
     * 3. Credit: sales account (HT amount)
     *
     * For credit notes, debit/credit are reversed.
     */
    public function buildEntries(Collection $invoices, AccountingSetting $settings): array
    {
        $entries = [];

        foreach ($invoices as $invoice) {
            $isCreditNote = $invoice->type === Invoice::TYPE_CREDIT_NOTE;
            $clientId = $settings->getClientAccountingId($invoice->client);
            $clientName = $invoice->client?->name ?? 'N/A';
            $label = mb_substr("{$clientName} - {$invoice->number}", 0, 40);
            $date = $invoice->issued_at;
            $dueDate = $invoice->due_at;

            // Montant client = somme EXACTE des lignes HT + TVA (chacune arrondie) afin de
            // garantir l'équilibre Débit = Crédit de chaque écriture (obligatoire pour le FEC,
            // et plus fiable pour les exports Sage).
            $balancedTtc = round(abs($invoice->total_ht), 2);
            foreach ($invoice->vat_breakdown as $vatLine) {
                $balancedTtc += round(abs($vatLine['amount']), 2);
            }
            $balancedTtc = round($balancedTtc, 2);

            // 1. Client line (TTC) - Debit for invoices, Credit for credit notes
            $entries[] = [
                'date' => $date,
                'journal' => $settings->sales_journal,
                'account' => $settings->clients_account,
                'account_label' => 'Clients',
                'third_party' => $clientId,
                'third_party_label' => $clientName,
                'piece' => $invoice->number,
                'label' => $label,
                'debit' => $isCreditNote ? 0 : $balancedTtc,
                'credit' => $isCreditNote ? $balancedTtc : 0,
                'due_date' => $dueDate,
            ];

            // 2. VAT lines - one per rate
            $vatBreakdown = $invoice->vat_breakdown;
            foreach ($vatBreakdown as $vat) {
                if (round($vat['amount'], 2) == 0) {
                    continue;
                }
                $vatAccount = $settings->getVatAccount($vat['rate']);
                $vatLabel = "TVA {$vat['rate']}%";

                $entries[] = [
                    'date' => $date,
                    'journal' => $settings->sales_journal,
                    'account' => $vatAccount,
                    'account_label' => $vatLabel,
                    'third_party' => '',
                    'third_party_label' => '',
                    'piece' => $invoice->number,
                    'label' => $vatLabel,
                    'debit' => $isCreditNote ? round(abs($vat['amount']), 2) : 0,
                    'credit' => $isCreditNote ? 0 : round($vat['amount'], 2),
                    'due_date' => null,
                ];
            }

            // 3. Sales line (HT) - Credit for invoices, Debit for credit notes
            $entries[] = [
                'date' => $date,
                'journal' => $settings->sales_journal,
                'account' => $settings->sales_account,
                'account_label' => 'Ventes',
                'third_party' => '',
                'third_party_label' => '',
                'piece' => $invoice->number,
                'label' => $label,
                'debit' => $isCreditNote ? round(abs($invoice->total_ht), 2) : 0,
                'credit' => $isCreditNote ? 0 : round($invoice->total_ht, 2),
                'due_date' => null,
            ];
        }

        return $entries;
    }

    /**
     * Dépenses de la période, cloisonnées à l'utilisateur.
     */
    protected function getExpensesForPeriod(User $user, Carbon $start, Carbon $end): Collection
    {
        return Expense::withoutUserScope()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->orderBy('id')
            ->get();
    }

    /**
     * Construit les écritures d'achat.
     *
     * Pour chaque dépense :
     *   Débit  compte de charge         (HT, ou TTC si la TVA n'est pas déductible)
     *   Débit  TVA en amont             (le cas échéant)
     *   Crédit compte fournisseur       (TTC)
     *
     * Trois règles portent la justesse comptable de cet export.
     *
     * LE COMPTE DE CHARGE vient de la catégorie de la dépense, à défaut du
     * compte générique du paramétrage. Une dépense sans compte n'empêche jamais
     * l'export : elle atterrit dans « Autres charges externes diverses », que la
     * fiduciaire ventilera.
     *
     * LA TVA NON DÉDUCTIBLE n'a pas d'écriture propre : elle grossit la charge.
     * C'est ce que dit `is_deductible`, un champ qui existait depuis l'origine
     * sans être utilisé nulle part.
     *
     * LA TVA ÉTRANGÈRE part sur un compte distinct. Celle qu'un fournisseur
     * allemand ou français facture ne se déduit pas sur la déclaration
     * luxembourgeoise : elle se récupère par une autre procédure. La confondre
     * avec la TVA en amont fausserait la déclaration.
     */
    public function buildExpenseEntries(Collection $expenses, AccountingSetting $settings): array
    {
        $entries = [];
        $localRates = $this->localVatRates();

        foreach ($expenses as $expense) {
            $ht = round((float) $expense->amount_ht, 2);
            $vat = round((float) $expense->amount_vat, 2);
            $rate = (float) $expense->vat_rate;

            $deductible = (bool) $expense->is_deductible && $vat > 0;
            $foreign = $deductible && ! in_array($rate, $localRates, true);

            // La charge absorbe la TVA quand celle-ci n'est pas récupérable.
            $chargeAmount = $deductible ? $ht : round($ht + $vat, 2);
            $total = round($chargeAmount + ($deductible ? $vat : 0), 2);

            $label = mb_substr(trim($expense->provider_name.' - '.($expense->description ?: $expense->category_label)), 0, 40);
            $piece = mb_substr((string) ($expense->reference ?: 'DEP-'.$expense->id), 0, 8);

            // 1. Charge
            $entries[] = [
                'date' => $expense->date,
                'journal' => $settings->purchase_journal,
                'account' => $this->expenseAccount($expense, $settings),
                'account_label' => $expense->category_label,
                'third_party' => '',
                'third_party_label' => $expense->provider_name,
                'piece' => $piece,
                'label' => $label,
                'debit' => $chargeAmount,
                'credit' => 0,
                'due_date' => null,
            ];

            // 2. TVA récupérable, luxembourgeoise ou étrangère
            if ($deductible) {
                $entries[] = [
                    'date' => $expense->date,
                    'journal' => $settings->purchase_journal,
                    'account' => $foreign ? $settings->vat_foreign_account : $settings->vat_deductible_account,
                    'account_label' => $foreign ? "TVA étrangère {$rate}%" : "TVA déductible {$rate}%",
                    'third_party' => '',
                    'third_party_label' => '',
                    'piece' => $piece,
                    'label' => $foreign ? "TVA étrangère {$rate}%" : "TVA déductible {$rate}%",
                    'debit' => $vat,
                    'credit' => 0,
                    'due_date' => null,
                ];
            }

            // 3. Fournisseur (TTC)
            $entries[] = [
                'date' => $expense->date,
                'journal' => $settings->purchase_journal,
                'account' => $settings->suppliers_account,
                'account_label' => 'Fournisseurs',
                'third_party' => '',
                'third_party_label' => $expense->provider_name,
                'piece' => $piece,
                'label' => $label,
                'debit' => 0,
                'credit' => $total,
                'due_date' => null,
            ];
        }

        return $entries;
    }

    /**
     * Compte de charge d'une dépense : celui de sa catégorie, sinon le générique.
     */
    protected function expenseAccount(Expense $expense, AccountingSetting $settings): string
    {
        $account = PurchaseCategory::withoutUserScope()
            ->where('user_id', $expense->user_id)
            ->where('key', $expense->category)
            ->value('pcn_account');

        return $account ?: $settings->default_expense_account;
    }

    /**
     * Taux de TVA du pays de l'entreprise.
     *
     * Sert à distinguer une TVA récupérable ici d'une TVA facturée à l'étranger.
     * En l'absence de paramètres, on retient les taux luxembourgeois.
     */
    protected function localVatRates(): array
    {
        $settings = \App\Models\BusinessSettings::getInstance();
        $rates = $settings?->getVatRates() ?: config('countries.LU.vat_rates', []);

        return array_map(fn ($rate) => (float) ($rate['value'] ?? $rate), $rates);
    }

    /**
     * Format content using the appropriate formatter.
     */
    protected function formatContent(array $entries, Collection $invoices, AccountingSetting $settings, string $format, ?Collection $expenses = null): string
    {
        return match ($format) {
            AccountingExport::FORMAT_SAGE_BOB => (new SageBobFormatter())->format($entries, $settings),
            AccountingExport::FORMAT_SAGE_100 => (new Sage100Formatter())->format($entries, $settings),
            AccountingExport::FORMAT_FEC => (new FecFormatter())->format($entries, $settings),
            // Le CSV générique se lit ligne par document, pas par écriture : il
            // reçoit donc les objets et non les entrées comptables.
            AccountingExport::FORMAT_GENERIC => (new GenericCsvFormatter())->format($invoices, $settings, $expenses ?? collect()),
            default => throw new \InvalidArgumentException("Format non supporté: {$format}"),
        };
    }

    /**
     * Périmètre de l'export : ventes, achats, ou les deux.
     *
     * La valeur par défaut reste « ventes » : un export enregistré avant cette
     * évolution doit produire exactement le même fichier qu'auparavant.
     */
    protected function scope(array $options): string
    {
        return $options['scope'] ?? 'sales';
    }

    protected function includesSales(string $scope): bool
    {
        return in_array($scope, ['sales', 'both'], true);
    }

    protected function includesPurchases(string $scope): bool
    {
        return in_array($scope, ['purchases', 'both'], true);
    }

    /**
     * Generate file name for the export.
     */
    protected function generateFileName(AccountingExport $export): string
    {
        $date = now()->format('Ymd_His');
        $period = $export->period_start->format('Ymd') . '-' . $export->period_end->format('Ymd');

        $extension = match ($export->format) {
            AccountingExport::FORMAT_SAGE_BOB => 'txt',
            AccountingExport::FORMAT_SAGE_100 => 'csv',
            AccountingExport::FORMAT_GENERIC => 'csv',
            AccountingExport::FORMAT_FEC => 'txt',
            default => 'txt',
        };

        $prefix = match ($export->format) {
            AccountingExport::FORMAT_SAGE_BOB => 'sage_bob',
            AccountingExport::FORMAT_SAGE_100 => 'sage_100',
            AccountingExport::FORMAT_GENERIC => 'export_comptable',
            AccountingExport::FORMAT_FEC => 'fec',
            default => 'export',
        };

        return "{$prefix}_{$period}_{$date}.{$extension}";
    }
}
