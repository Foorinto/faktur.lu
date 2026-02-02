<?php

namespace App\Services;

use App\Models\AuditExport;
use App\Models\BusinessSettings;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class AuditExportService
{
    /**
     * Get preview data for the export period.
     */
    public function getPreview(Carbon $periodStart, Carbon $periodEnd, array $options = []): array
    {
        $includeCreditNotes = $options['include_credit_notes'] ?? true;

        $invoices = $this->getInvoicesForPeriod($periodStart, $periodEnd, $includeCreditNotes);
        $sequenceResult = $this->validateSequence($invoices);

        return [
            'invoices_count' => $invoices->where('type', Invoice::TYPE_INVOICE)->count(),
            'credit_notes_count' => $invoices->where('type', Invoice::TYPE_CREDIT_NOTE)->count(),
            'total_ht' => round($invoices->sum('total_ht'), 2),
            'total_vat' => round($invoices->sum('total_vat'), 2),
            'total_ttc' => round($invoices->sum('total_ttc'), 2),
            'sequence_valid' => $sequenceResult['valid'],
            'sequence_errors' => $sequenceResult['errors'],
        ];
    }

    /**
     * Generate the export file.
     */
    public function generate(AuditExport $export): void
    {
        $export->markAsProcessing();

        try {
            $options = $export->options ?? [];
            $includeCreditNotes = $options['include_credit_notes'] ?? true;
            $anonymize = $options['anonymize'] ?? false;

            $invoices = $this->getInvoicesForPeriod(
                $export->period_start,
                $export->period_end,
                $includeCreditNotes
            );

            $sequenceResult = $this->validateSequence($invoices);
            $stats = $this->calculateStats($invoices);

            // Generate the file based on format
            $content = match ($export->format) {
                AuditExport::FORMAT_CSV => $this->generateCsv($invoices, $export, $anonymize),
                AuditExport::FORMAT_JSON => $this->generateJson($invoices, $export, $stats, $sequenceResult, $anonymize),
                AuditExport::FORMAT_XML => $this->generateXml($invoices, $export, $stats, $sequenceResult, $anonymize),
                default => throw new \InvalidArgumentException("Format non supporté: {$export->format}"),
            };

            // Save the file
            $fileName = $this->generateFileName($export);
            $filePath = "exports/audit/{$fileName}";

            Storage::disk('local')->put($filePath, $content);

            $export->markAsCompleted(
                $filePath,
                $fileName,
                $stats,
                $sequenceResult['valid'],
                $sequenceResult['errors'] ? implode("\n", $sequenceResult['errors']) : null
            );
        } catch (\Exception $e) {
            $export->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Get invoices for the given period.
     */
    protected function getInvoicesForPeriod(Carbon $start, Carbon $end, bool $includeCreditNotes = true): Collection
    {
        $query = Invoice::query()
            ->whereIn('status', [Invoice::STATUS_FINALIZED, Invoice::STATUS_SENT, Invoice::STATUS_PAID])
            ->whereBetween('issued_at', [$start->startOfDay(), $end->endOfDay()])
            ->with(['client', 'originalInvoice'])
            ->orderBy('issued_at')
            ->orderBy('number');

        if (!$includeCreditNotes) {
            $query->where('type', Invoice::TYPE_INVOICE);
        }

        return $query->get();
    }

    /**
     * Validate the invoice number sequence.
     */
    public function validateSequence(Collection $invoices): array
    {
        $errors = [];

        // Group by type and year
        $grouped = $invoices->groupBy(function ($invoice) {
            $year = $invoice->issued_at->year;
            $type = $invoice->type === Invoice::TYPE_CREDIT_NOTE ? 'AV' : 'FAC';
            return "{$type}-{$year}";
        });

        foreach ($grouped as $key => $group) {
            $numbers = $group->map(function ($invoice) {
                // Extract the sequence number from the invoice number (e.g., FAC-2026-001 -> 1)
                if (preg_match('/\d{4}-(\d+)$/', $invoice->number, $matches)) {
                    return (int) $matches[1];
                }
                return null;
            })->filter()->sort()->values();

            if ($numbers->isEmpty()) {
                continue;
            }

            $expected = 1;
            foreach ($numbers as $number) {
                if ($number !== $expected) {
                    // Check for gaps
                    for ($i = $expected; $i < $number; $i++) {
                        $errors[] = "Numéro manquant: {$key}-" . str_pad($i, 3, '0', STR_PAD_LEFT);
                    }
                }
                $expected = $number + 1;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Calculate summary statistics.
     */
    protected function calculateStats(Collection $invoices): array
    {
        $invoicesOnly = $invoices->where('type', Invoice::TYPE_INVOICE);
        $creditNotes = $invoices->where('type', Invoice::TYPE_CREDIT_NOTE);

        return [
            'invoices_count' => $invoicesOnly->count(),
            'credit_notes_count' => $creditNotes->count(),
            'total_ht' => round($invoices->sum('total_ht'), 2),
            'total_vat' => round($invoices->sum('total_vat'), 2),
            'total_ttc' => round($invoices->sum('total_ttc'), 2),
            'invoices_total_ht' => round($invoicesOnly->sum('total_ht'), 2),
            'credit_notes_total_ht' => round($creditNotes->sum('total_ht'), 2),
        ];
    }

    /**
     * Generate CSV content.
     */
    protected function generateCsv(Collection $invoices, AuditExport $export, bool $anonymize): string
    {
        $lines = [];

        // Header
        $lines[] = implode(';', [
            'numero',
            'date_emission',
            'date_echeance',
            'client_id',
            'client_nom',
            'client_tva',
            'type',
            'montant_ht',
            'taux_tva_principal',
            'montant_tva',
            'montant_ttc',
            'devise',
            'reference_originale',
        ]);

        // Data rows
        foreach ($invoices as $invoice) {
            $clientName = $anonymize ? "Client #{$invoice->client_id}" : ($invoice->client?->name ?? 'N/A');
            $clientVat = $anonymize ? '' : ($invoice->client?->vat_number ?? '');

            // Get the main VAT rate (most common or highest amount)
            $mainVatRate = $this->getMainVatRate($invoice);

            $lines[] = implode(';', [
                $invoice->number,
                $invoice->issued_at->format('Y-m-d'),
                $invoice->due_at?->format('Y-m-d') ?? '',
                $invoice->client_id,
                $this->escapeCsvField($clientName),
                $clientVat,
                $invoice->type === Invoice::TYPE_CREDIT_NOTE ? 'avoir' : 'facture',
                number_format($invoice->total_ht, 2, '.', ''),
                number_format($mainVatRate, 2, '.', ''),
                number_format($invoice->total_vat, 2, '.', ''),
                number_format($invoice->total_ttc, 2, '.', ''),
                $invoice->currency ?? 'EUR',
                $invoice->type === Invoice::TYPE_CREDIT_NOTE ? ($invoice->originalInvoice?->number ?? '') : '',
            ]);
        }

        return implode("\n", $lines);
    }

    /**
     * Generate JSON content.
     */
    protected function generateJson(Collection $invoices, AuditExport $export, array $stats, array $sequenceResult, bool $anonymize): string
    {
        $settings = BusinessSettings::getInstance();

        $data = [
            'export' => [
                'generated_at' => now()->toIso8601String(),
                'period' => [
                    'start' => $export->period_start->format('Y-m-d'),
                    'end' => $export->period_end->format('Y-m-d'),
                ],
                'company' => [
                    'name' => $settings?->company_name ?? '',
                    'matricule' => $settings?->matricule ?? '',
                    'vat_number' => $settings?->vat_number ?? '',
                    'rcs_number' => $settings?->rcs_number ?? '',
                ],
            ],
            'summary' => [
                'invoices_count' => $stats['invoices_count'],
                'credit_notes_count' => $stats['credit_notes_count'],
                'total_ht' => $stats['total_ht'],
                'total_vat' => $stats['total_vat'],
                'total_ttc' => $stats['total_ttc'],
                'sequence_valid' => $sequenceResult['valid'],
                'sequence_errors' => $sequenceResult['errors'],
            ],
            'documents' => $invoices->map(function ($invoice) use ($anonymize) {
                return $this->invoiceToArray($invoice, $anonymize);
            })->values()->toArray(),
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Generate XML FAIA content.
     */
    protected function generateXml(Collection $invoices, AuditExport $export, array $stats, array $sequenceResult, bool $anonymize): string
    {
        $settings = BusinessSettings::getInstance();

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><AuditFile xmlns="urn:lu:aed:FAIA:v1"></AuditFile>');

        // Header
        $header = $xml->addChild('Header');
        $header->addChild('AuditFileVersion', '1.0');
        $header->addChild('AuditFileDateCreated', now()->format('Y-m-d'));
        $header->addChild('SoftwareCompanyName', 'faktur.lu');
        $header->addChild('SoftwareID', 'FAKTURLU');
        $header->addChild('SoftwareVersion', '1.0');

        // Company
        $company = $header->addChild('Company');
        $company->addChild('CompanyName', $this->escapeXml($settings?->company_name ?? ''));
        $company->addChild('RegistrationNumber', $settings?->matricule ?? '');
        if ($settings?->vat_number) {
            $company->addChild('TaxRegistrationNumber', $settings->vat_number);
        }
        if ($settings?->rcs_number) {
            $company->addChild('RCSNumber', $settings->rcs_number);
        }

        $address = $company->addChild('Address');
        $address->addChild('StreetName', $this->escapeXml($settings?->address ?? ''));
        $address->addChild('City', $this->escapeXml($settings?->city ?? ''));
        $address->addChild('PostalCode', $settings?->postal_code ?? '');
        $address->addChild('Country', $settings?->country ?? 'LU');

        // Selection criteria
        $selection = $header->addChild('SelectionCriteria');
        $selection->addChild('PeriodStart', $export->period_start->format('Y-m-d'));
        $selection->addChild('PeriodEnd', $export->period_end->format('Y-m-d'));

        // Summary
        $summary = $xml->addChild('Summary');
        $summary->addChild('NumberOfInvoices', (string) $stats['invoices_count']);
        $summary->addChild('NumberOfCreditNotes', (string) $stats['credit_notes_count']);
        $summary->addChild('TotalNetAmount', number_format($stats['total_ht'], 2, '.', ''));
        $summary->addChild('TotalTaxAmount', number_format($stats['total_vat'], 2, '.', ''));
        $summary->addChild('TotalGrossAmount', number_format($stats['total_ttc'], 2, '.', ''));
        $summary->addChild('SequenceValid', $sequenceResult['valid'] ? 'true' : 'false');

        // Sales invoices
        $salesInvoices = $xml->addChild('SalesInvoices');

        foreach ($invoices as $invoice) {
            $inv = $salesInvoices->addChild('Invoice');
            $inv->addChild('InvoiceNo', $invoice->number);
            $inv->addChild('InvoiceType', $invoice->type === Invoice::TYPE_CREDIT_NOTE ? 'CreditNote' : 'Invoice');
            $inv->addChild('InvoiceDate', $invoice->issued_at->format('Y-m-d'));

            if ($invoice->due_at) {
                $inv->addChild('DueDate', $invoice->due_at->format('Y-m-d'));
            }

            // Customer
            $customer = $inv->addChild('Customer');
            $customer->addChild('CustomerID', (string) $invoice->client_id);
            $customerName = $anonymize ? "Client #{$invoice->client_id}" : ($invoice->client?->name ?? 'N/A');
            $customer->addChild('CustomerName', $this->escapeXml($customerName));

            if (!$anonymize && $invoice->client?->vat_number) {
                $customer->addChild('TaxRegistrationNumber', $invoice->client->vat_number);
            }

            // Amounts
            $inv->addChild('NetTotal', number_format($invoice->total_ht, 2, '.', ''));
            $inv->addChild('TaxTotal', number_format($invoice->total_vat, 2, '.', ''));
            $inv->addChild('GrossTotal', number_format($invoice->total_ttc, 2, '.', ''));
            $inv->addChild('CurrencyCode', $invoice->currency ?? 'EUR');

            // Credit note reference
            if ($invoice->type === Invoice::TYPE_CREDIT_NOTE && $invoice->originalInvoice) {
                $inv->addChild('OriginalInvoiceNo', $invoice->originalInvoice->number);
                if ($invoice->credit_note_reason) {
                    $inv->addChild('CreditNoteReason', $invoice->credit_note_reason);
                }
            }
        }

        // Format XML with indentation
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = true;
        $dom->loadXML($xml->asXML());

        return $dom->saveXML();
    }

    /**
     * Convert invoice to array for JSON export.
     */
    protected function invoiceToArray(Invoice $invoice, bool $anonymize): array
    {
        $clientName = $anonymize ? "Client #{$invoice->client_id}" : ($invoice->client?->name ?? 'N/A');
        $clientVat = $anonymize ? null : $invoice->client?->vat_number;

        return [
            'number' => $invoice->number,
            'type' => $invoice->type === Invoice::TYPE_CREDIT_NOTE ? 'credit_note' : 'invoice',
            'issued_at' => $invoice->issued_at->format('Y-m-d'),
            'due_at' => $invoice->due_at?->format('Y-m-d'),
            'client' => [
                'id' => $invoice->client_id,
                'name' => $clientName,
                'vat_number' => $clientVat,
            ],
            'amounts' => [
                'total_ht' => round($invoice->total_ht, 2),
                'total_vat' => round($invoice->total_vat, 2),
                'total_ttc' => round($invoice->total_ttc, 2),
            ],
            'currency' => $invoice->currency ?? 'EUR',
            'original_invoice' => $invoice->type === Invoice::TYPE_CREDIT_NOTE
                ? $invoice->originalInvoice?->number
                : null,
            'credit_note_reason' => $invoice->credit_note_reason,
        ];
    }

    /**
     * Generate file name for the export.
     */
    protected function generateFileName(AuditExport $export): string
    {
        $date = now()->format('Ymd_His');
        $period = $export->period_start->format('Ymd') . '-' . $export->period_end->format('Ymd');

        $extension = match ($export->format) {
            AuditExport::FORMAT_CSV => 'csv',
            AuditExport::FORMAT_JSON => 'json',
            AuditExport::FORMAT_XML => 'xml',
            default => 'txt',
        };

        return "faia_export_{$period}_{$date}.{$extension}";
    }

    /**
     * Get the main VAT rate from an invoice.
     */
    protected function getMainVatRate(Invoice $invoice): float
    {
        if (!$invoice->relationLoaded('items')) {
            $invoice->load('items');
        }

        if ($invoice->items->isEmpty()) {
            return 0;
        }

        // Return the VAT rate with the highest total amount
        return $invoice->items
            ->groupBy('vat_rate')
            ->map(fn ($items) => $items->sum('total_ht'))
            ->sortDesc()
            ->keys()
            ->first() ?? 0;
    }

    /**
     * Escape CSV field.
     */
    protected function escapeCsvField(string $value): string
    {
        if (str_contains($value, ';') || str_contains($value, '"') || str_contains($value, "\n")) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }

    /**
     * Escape XML special characters.
     */
    protected function escapeXml(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
