<?php

namespace App\Services\Accounting;

use App\Models\AccountingExport;
use App\Models\AccountingSetting;
use App\Models\Invoice;
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
        $invoices = $this->getInvoicesForPeriod($user, $periodStart, $periodEnd, $includeCreditNotes);

        $invoicesOnly = $invoices->where('type', Invoice::TYPE_INVOICE);
        $creditNotes = $invoices->where('type', Invoice::TYPE_CREDIT_NOTE);

        return [
            'invoices_count' => $invoicesOnly->count(),
            'credit_notes_count' => $creditNotes->count(),
            'total_ht' => round($invoices->sum('total_ht'), 2),
            'total_vat' => round($invoices->sum('total_vat'), 2),
            'total_ttc' => round($invoices->sum('total_ttc'), 2),
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

            $invoices = $this->getInvoicesForPeriod(
                $user,
                $export->period_start,
                $export->period_end,
                $includeCreditNotes
            );

            $settings = AccountingSetting::getForUser($user);
            $entries = $this->buildEntries($invoices, $settings);
            $content = $this->formatContent($entries, $invoices, $settings, $export->format);

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
        $invoices = $this->getInvoicesForPeriod($user, $periodStart, $periodEnd, $includeCreditNotes);
        $settings = AccountingSetting::getForUser($user);
        $entries = $this->buildEntries($invoices, $settings);

        return $this->formatContent($entries, $invoices, $settings, $format);
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

            // 1. Client line (TTC) — Debit for invoices, Credit for credit notes
            $entries[] = [
                'date' => $date,
                'journal' => $settings->sales_journal,
                'account' => $settings->clients_account,
                'third_party' => $clientId,
                'piece' => $invoice->number,
                'label' => $label,
                'debit' => $isCreditNote ? 0 : round($invoice->total_ttc, 2),
                'credit' => $isCreditNote ? round(abs($invoice->total_ttc), 2) : 0,
                'due_date' => $dueDate,
            ];

            // 2. VAT lines — one per rate
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
                    'third_party' => '',
                    'piece' => $invoice->number,
                    'label' => $vatLabel,
                    'debit' => $isCreditNote ? round(abs($vat['amount']), 2) : 0,
                    'credit' => $isCreditNote ? 0 : round($vat['amount'], 2),
                    'due_date' => null,
                ];
            }

            // 3. Sales line (HT) — Credit for invoices, Debit for credit notes
            $entries[] = [
                'date' => $date,
                'journal' => $settings->sales_journal,
                'account' => $settings->sales_account,
                'third_party' => '',
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
     * Format content using the appropriate formatter.
     */
    protected function formatContent(array $entries, Collection $invoices, AccountingSetting $settings, string $format): string
    {
        return match ($format) {
            AccountingExport::FORMAT_SAGE_BOB => (new SageBobFormatter())->format($entries, $settings),
            AccountingExport::FORMAT_SAGE_100 => (new Sage100Formatter())->format($entries, $settings),
            AccountingExport::FORMAT_GENERIC => (new GenericCsvFormatter())->format($invoices, $settings),
            default => throw new \InvalidArgumentException("Format non supporté: {$format}"),
        };
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
            default => 'txt',
        };

        $prefix = match ($export->format) {
            AccountingExport::FORMAT_SAGE_BOB => 'sage_bob',
            AccountingExport::FORMAT_SAGE_100 => 'sage_100',
            AccountingExport::FORMAT_GENERIC => 'export_comptable',
            default => 'export',
        };

        return "{$prefix}_{$period}_{$date}.{$extension}";
    }
}
