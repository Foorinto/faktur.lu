<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\AccountantDownload;
use App\Models\AccountingExport;
use App\Models\Invoice;
use App\Models\User;
use App\Notifications\AccountantDownloadNotification;
use App\Services\Accounting\AccountingExportService;
use App\Services\InvoicePdfService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

class AccountantExportController extends Controller
{
    /**
     * Download an export.
     */
    public function download(Request $request, User $user, string $type)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'quarter' => 'nullable|integer|min:1|max:4',
        ]);

        $accountant = auth('accountant')->user();
        $year = $request->integer('year');
        $quarter = $request->integer('quarter');

        // Build period string
        $period = $year;
        if ($quarter) {
            $period .= '-Q' . $quarter;
        }

        // Get invoices for the period
        $invoices = $this->getInvoicesForPeriod($user, $year, $quarter);

        if ($invoices->isEmpty()) {
            return back()->withErrors(['export' => 'Aucune facture pour cette période.']);
        }

        // Record the download
        AccountantDownload::record(
            $accountant,
            $user,
            $type,
            $period,
            $request->ip(),
            $request->userAgent()
        );

        // Notify the user
        $user->notify(new AccountantDownloadNotification($accountant, $type, $period));

        // Generate and return the export
        return match ($type) {
            'faia' => $this->exportFaia($user, $invoices, $year),
            'excel' => $this->exportExcel($user, $invoices, $period),
            'pdf_archive' => $this->exportPdfArchive($user, $invoices, $period),
            default => abort(400, 'Type d\'export invalide.'),
        };
    }

    /**
     * Get invoices for a specific period.
     */
    protected function getInvoicesForPeriod(User $user, int $year, ?int $quarter)
    {
        $query = $user->userInvoices()
            ->where('status', '!=', 'draft')
            ->whereYear('finalized_at', $year)
            ->with('client')
            ->orderBy('finalized_at');

        if ($quarter) {
            $startMonth = ($quarter - 1) * 3 + 1;
            $endMonth = $quarter * 3;
            $query->whereMonth('finalized_at', '>=', $startMonth)
                  ->whereMonth('finalized_at', '<=', $endMonth);
        }

        return $query->get();
    }

    /**
     * Export FAIA XML.
     */
    protected function exportFaia(User $user, $invoices, int $year)
    {
        // Use existing FAIA export logic
        $faiaController = app(\App\Http\Controllers\AuditExportController::class);

        // Generate FAIA XML
        $xml = $this->generateFaiaXml($user, $invoices, $year);

        $filename = sprintf('FAIA_%s_%d.xml',
            $user->businessSettings?->matricule ?? 'export',
            $year
        );

        return response($xml)
            ->header('Content-Type', 'application/xml')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    /**
     * Generate FAIA XML content.
     */
    protected function generateFaiaXml(User $user, $invoices, int $year): string
    {
        $settings = $user->businessSettings;

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><AuditFile xmlns="urn:OECD:StandardAuditFile-Taxation/2.00" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"></AuditFile>');

        // Header
        $header = $xml->addChild('Header');
        $header->addChild('AuditFileVersion', '2.01');
        $header->addChild('AuditFileCountry', 'LU');
        $header->addChild('AuditFileDateCreated', now()->format('Y-m-d'));
        $header->addChild('SoftwareCompanyName', 'faktur.lu');
        $header->addChild('SoftwareID', 'faktur.lu');
        $header->addChild('SoftwareVersion', '1.0');

        // Company
        $company = $header->addChild('Company');
        $company->addChild('RegistrationNumber', $settings?->matricule ?? '');
        $company->addChild('Name', $settings?->company_name ?? $user->name);

        $address = $company->addChild('Address');
        $address->addChild('StreetName', $settings?->address ?? '');
        $address->addChild('City', $settings?->city ?? '');
        $address->addChild('PostalCode', $settings?->postal_code ?? '');
        $address->addChild('Country', $settings?->country_code ?? 'LU');

        if ($settings?->vat_number) {
            $company->addChild('TaxRegistration')->addChild('TaxRegistrationNumber', $settings->vat_number);
        }

        $header->addChild('SelectionCriteria')->addChild('SelectionStartDate', $year . '-01-01');
        $header->addChild('SelectionCriteria')->addChild('SelectionEndDate', $year . '-12-31');

        // Source Documents - Sales Invoices
        $sourceDocuments = $xml->addChild('SourceDocuments');
        $salesInvoices = $sourceDocuments->addChild('SalesInvoices');
        $salesInvoices->addChild('NumberOfEntries', $invoices->count());

        $totalDebit = 0;
        $totalCredit = $invoices->sum('total_ttc');

        $salesInvoices->addChild('TotalDebit', number_format($totalDebit, 2, '.', ''));
        $salesInvoices->addChild('TotalCredit', number_format($totalCredit, 2, '.', ''));

        foreach ($invoices as $invoice) {
            $inv = $salesInvoices->addChild('Invoice');
            $inv->addChild('InvoiceNo', $invoice->number);
            $inv->addChild('InvoiceDate', $invoice->finalized_at?->format('Y-m-d'));
            $inv->addChild('InvoiceType', $invoice->type === 'credit_note' ? 'CN' : 'FT');

            $customer = $inv->addChild('CustomerInfo');
            $customer->addChild('CustomerID', $invoice->client_id);
            $customer->addChild('BillingAddress')->addChild('StreetName', $invoice->buyer_snapshot['address'] ?? '');

            $inv->addChild('DocumentTotals')->addChild('GrossTotal', number_format($invoice->total_ttc, 2, '.', ''));
        }

        return $xml->asXML();
    }

    /**
     * Export Excel.
     */
    protected function exportExcel(User $user, $invoices, string $period)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Factures');

        // Headers
        $headers = ['Numéro', 'Date', 'Client', 'Type', 'HT', 'TVA', 'TTC', 'Statut'];
        $sheet->fromArray($headers, null, 'A1');

        // Style headers
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

        // Data
        $row = 2;
        foreach ($invoices as $invoice) {
            $sheet->setCellValue('A' . $row, $invoice->number);
            $sheet->setCellValue('B' . $row, $invoice->finalized_at?->format('d/m/Y'));
            $sheet->setCellValue('C' . $row, $invoice->buyer_snapshot['name'] ?? '');
            $sheet->setCellValue('D' . $row, $invoice->type === 'credit_note' ? 'Avoir' : 'Facture');
            $sheet->setCellValue('E' . $row, $invoice->total_ht);
            $sheet->setCellValue('F' . $row, $invoice->total_tax);
            $sheet->setCellValue('G' . $row, $invoice->total_ttc);
            $sheet->setCellValue('H' . $row, $invoice->status_label ?? $invoice->status);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Generate file
        $filename = sprintf('Factures_%s_%s.xlsx',
            $user->businessSettings?->company_name ?? 'export',
            str_replace('-', '_', $period)
        );

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Export PDF archive.
     */
    protected function exportPdfArchive(User $user, $invoices, string $period)
    {
        $tempDir = storage_path('app/temp/' . uniqid('pdf_archive_'));
        mkdir($tempDir, 0755, true);

        $zipPath = $tempDir . '/archive.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            abort(500, 'Impossible de créer l\'archive.');
        }

        $pdfService = app(InvoicePdfService::class);
        $pdfCount = 0;

        foreach ($invoices as $invoice) {
            if ($invoice->status === 'draft') {
                continue;
            }

            try {
                $pdfContent = $pdfService->getContent($invoice);
                $zip->addFromString($invoice->number . '.pdf', $pdfContent);
                $pdfCount++;
            } catch (\Exception $e) {
                // Skip invoices that fail PDF generation
                continue;
            }
        }

        if ($pdfCount === 0) {
            $zip->addFromString('README.txt', "Aucune facture disponible pour cette période.");
        }

        $zip->close();

        $filename = sprintf('Archive_PDF_%s_%s.zip',
            $user->businessSettings?->company_name ?? 'export',
            str_replace('-', '_', $period)
        );

        return response()->download($zipPath, $filename)->deleteFileAfterSend(true);
    }

    /**
     * Download an accounting export (Sage BOB, Sage 100, or Generic CSV).
     */
    public function downloadAccounting(Request $request, User $user, string $format)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'quarter' => 'nullable|integer|min:1|max:4',
        ]);

        if (!in_array($format, [AccountingExport::FORMAT_SAGE_BOB, AccountingExport::FORMAT_SAGE_100, AccountingExport::FORMAT_GENERIC])) {
            abort(400, 'Format d\'export invalide.');
        }

        $accountant = auth('accountant')->user();
        $year = $request->integer('year');
        $quarter = $request->integer('quarter');

        // Build period
        if ($quarter) {
            $startMonth = ($quarter - 1) * 3 + 1;
            $periodStart = Carbon::create($year, $startMonth, 1)->startOfDay();
            $periodEnd = $periodStart->copy()->addMonths(3)->subDay()->endOfDay();
            $period = "{$year}-Q{$quarter}";
        } else {
            $periodStart = Carbon::create($year, 1, 1)->startOfDay();
            $periodEnd = Carbon::create($year, 12, 31)->endOfDay();
            $period = (string) $year;
        }

        // Record the download
        $formatLabel = AccountingExport::FORMATS[$format] ?? $format;
        AccountantDownload::record(
            $accountant,
            $user,
            "accounting_{$format}",
            $period,
            $request->ip(),
            $request->userAgent()
        );

        // Notify the user
        $user->notify(new AccountantDownloadNotification($accountant, "accounting_{$format}", $period));

        // Generate content
        $exportService = app(AccountingExportService::class);
        $content = $exportService->generateContent($user, $periodStart, $periodEnd, $format);

        $extension = match ($format) {
            AccountingExport::FORMAT_SAGE_BOB => 'txt',
            default => 'csv',
        };

        $mimeType = match ($format) {
            AccountingExport::FORMAT_SAGE_BOB => 'text/plain',
            default => 'text/csv',
        };

        $companyName = $user->businessSettings?->company_name ?? 'export';
        $filename = sprintf('%s_%s_%s.%s', $format, $companyName, str_replace('-', '_', $period), $extension);

        return response($content)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
