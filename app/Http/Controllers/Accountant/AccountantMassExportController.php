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
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ZipArchive;

class AccountantMassExportController extends Controller
{
    /**
     * Mass export: generates a ZIP with one export file per selected client.
     */
    public function massExport(Request $request)
    {
        $request->validate([
            'client_ids' => 'required|array|min:1',
            'client_ids.*' => 'integer|exists:users,id',
            'format' => 'required|string|in:faia,excel,pdf_archive,accounting_sage_bob,accounting_sage_100,accounting_generic',
            'year' => 'required|integer|min:2020|max:2100',
            'quarter' => 'nullable|integer|min:1|max:4',
        ]);

        $accountant = auth('accountant')->user();
        $clientIds = $request->input('client_ids');
        $format = $request->input('format');
        $year = $request->integer('year');
        $quarter = $request->integer('quarter') ?: null;

        // Verify access to all requested clients
        $accessibleClientIds = $accountant->activeClients()->pluck('users.id')->toArray();
        $unauthorized = array_diff($clientIds, $accessibleClientIds);
        if (!empty($unauthorized)) {
            abort(403, 'Accès refusé pour certains clients.');
        }

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

        // Create temp directory and ZIP
        $tempDir = storage_path('app/temp/' . uniqid('mass_export_'));
        mkdir($tempDir, 0755, true);
        $zipPath = $tempDir . '/export.zip';
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
            abort(500, 'Impossible de créer l\'archive.');
        }

        $clients = User::whereIn('id', $clientIds)->with('businessSettings')->get();
        $exportService = app(AccountingExportService::class);
        $pdfService = app(InvoicePdfService::class);
        $filesAdded = 0;

        foreach ($clients as $client) {
            $companyName = $this->sanitizeFilename(
                $client->businessSettings?->company_name ?? $client->name
            );

            try {
                $fileContent = $this->generateExportForClient(
                    $client, $format, $year, $quarter,
                    $periodStart, $periodEnd, $period,
                    $exportService, $pdfService, $tempDir
                );

                if ($fileContent === null) {
                    continue;
                }

                $ext = $this->getExtensionForFormat($format);
                $innerFilename = "{$companyName}_{$period}.{$ext}";

                if (is_array($fileContent)) {
                    $zip->addFile($fileContent['path'], $innerFilename);
                } else {
                    $zip->addFromString($innerFilename, $fileContent);
                }
                $filesAdded++;

                // Record download + notify per client
                $exportType = $this->getExportType($format);
                AccountantDownload::record(
                    $accountant, $client, $exportType, $period,
                    $request->ip(), $request->userAgent()
                );
                $client->notify(new AccountantDownloadNotification($accountant, $exportType, $period));
            } catch (\Exception $e) {
                Log::warning("Mass export failed for client {$client->id}: {$e->getMessage()}");
                continue;
            }
        }

        if ($filesAdded === 0) {
            @unlink($zipPath);
            @rmdir($tempDir);

            return back()->withErrors(['export' => 'Aucune donnée à exporter pour les clients sélectionnés.']);
        }

        $zip->close();

        $formatLabel = str_replace('accounting_', '', $format);
        $zipFilename = "Export_masse_{$formatLabel}_{$period}.zip";

        return response()->download($zipPath, $zipFilename)->deleteFileAfterSend(true);
    }

    /**
     * Consolidated report: one Excel row per client summarizing the period.
     */
    public function consolidatedReport(Request $request)
    {
        $request->validate([
            'client_ids' => 'required|array|min:1',
            'client_ids.*' => 'integer|exists:users,id',
            'year' => 'required|integer|min:2020|max:2100',
            'quarter' => 'nullable|integer|min:1|max:4',
        ]);

        $accountant = auth('accountant')->user();
        $clientIds = $request->input('client_ids');
        $year = $request->integer('year');
        $quarter = $request->integer('quarter') ?: null;

        // Verify access
        $accessibleClientIds = $accountant->activeClients()->pluck('users.id')->toArray();
        $unauthorized = array_diff($clientIds, $accessibleClientIds);
        if (!empty($unauthorized)) {
            abort(403, 'Accès refusé pour certains clients.');
        }

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

        $clients = User::whereIn('id', $clientIds)->with('businessSettings')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rapport consolidé');

        // Headers
        $headers = ['Client', 'N° TVA', 'CA HT', 'TVA', 'TTC', 'Nb Factures', 'Nb Avoirs', 'Dernier export'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB('FFE2E8F0');

        $row = 2;
        $totals = ['ca_ht' => 0, 'vat' => 0, 'ttc' => 0, 'invoices' => 0, 'credit_notes' => 0];

        foreach ($clients as $client) {
            $invoices = $client->userInvoices()
                ->whereIn('status', [Invoice::STATUS_FINALIZED, Invoice::STATUS_SENT, Invoice::STATUS_PAID])
                ->whereBetween('finalized_at', [$periodStart, $periodEnd])
                ->get();

            $invoicesOnly = $invoices->where('type', Invoice::TYPE_INVOICE);
            $creditNotes = $invoices->where('type', Invoice::TYPE_CREDIT_NOTE);

            $caHt = round($invoicesOnly->sum('total_ht'), 2);
            $vat = round($invoices->sum('total_tax'), 2);
            $ttc = round($invoices->sum('total_ttc'), 2);
            $nbInvoices = $invoicesOnly->count();
            $nbCreditNotes = $creditNotes->count();

            $lastDownload = $accountant->downloads()
                ->where('user_id', $client->id)
                ->latest()
                ->first();

            $sheet->setCellValue("A{$row}", $client->businessSettings?->company_name ?? $client->name);
            $sheet->setCellValue("B{$row}", $client->businessSettings?->vat_number ?? '');
            $sheet->setCellValue("C{$row}", $caHt);
            $sheet->setCellValue("D{$row}", $vat);
            $sheet->setCellValue("E{$row}", $ttc);
            $sheet->setCellValue("F{$row}", $nbInvoices);
            $sheet->setCellValue("G{$row}", $nbCreditNotes);
            $sheet->setCellValue("H{$row}", $lastDownload?->created_at?->format('d/m/Y H:i') ?? '-');

            $sheet->getStyle("C{$row}:E{$row}")->getNumberFormat()
                ->setFormatCode('#,##0.00');

            $totals['ca_ht'] += $caHt;
            $totals['vat'] += $vat;
            $totals['ttc'] += $ttc;
            $totals['invoices'] += $nbInvoices;
            $totals['credit_notes'] += $nbCreditNotes;

            $row++;
        }

        // Total row
        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->setCellValue("C{$row}", round($totals['ca_ht'], 2));
        $sheet->setCellValue("D{$row}", round($totals['vat'], 2));
        $sheet->setCellValue("E{$row}", round($totals['ttc'], 2));
        $sheet->setCellValue("F{$row}", $totals['invoices']);
        $sheet->setCellValue("G{$row}", $totals['credit_notes']);

        $sheet->getStyle("A{$row}:H{$row}")->getFont()->setBold(true);
        $sheet->getStyle("C{$row}:E{$row}")->getNumberFormat()
            ->setFormatCode('#,##0.00');

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = "Rapport_consolide_{$period}.xlsx";
        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Generate export content for a single client based on format.
     */
    private function generateExportForClient(
        User $client,
        string $format,
        int $year,
        ?int $quarter,
        Carbon $periodStart,
        Carbon $periodEnd,
        string $period,
        AccountingExportService $exportService,
        InvoicePdfService $pdfService,
        string $tempDir,
    ): string|array|null {
        $invoices = $this->getInvoicesForPeriod($client, $year, $quarter);

        if ($invoices->isEmpty()) {
            return null;
        }

        return match ($format) {
            'faia' => app(AccountantExportController::class)->generateFaiaXml($client, $invoices, $year),
            'excel' => $this->generateExcelContent($client, $invoices, $period),
            'pdf_archive' => $this->generatePdfArchiveContent($invoices, $pdfService, $tempDir),
            'accounting_sage_bob' => $exportService->generateContent($client, $periodStart, $periodEnd, AccountingExport::FORMAT_SAGE_BOB),
            'accounting_sage_100' => $exportService->generateContent($client, $periodStart, $periodEnd, AccountingExport::FORMAT_SAGE_100),
            'accounting_generic' => $exportService->generateContent($client, $periodStart, $periodEnd, AccountingExport::FORMAT_GENERIC),
            default => null,
        };
    }

    /**
     * Get invoices for a specific period.
     */
    private function getInvoicesForPeriod(User $user, int $year, ?int $quarter)
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
     * Generate Excel content as string (for ZIP inclusion).
     */
    private function generateExcelContent(User $user, $invoices, string $period): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Factures');

        $headers = ['Numéro', 'Date', 'Client', 'Type', 'HT', 'TVA', 'TTC', 'Statut'];
        $sheet->fromArray($headers, null, 'A1');
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);

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

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');

        return ob_get_clean();
    }

    /**
     * Generate PDF archive as sub-ZIP file (for inclusion in mass export ZIP).
     */
    private function generatePdfArchiveContent($invoices, InvoicePdfService $pdfService, string $tempDir): ?array
    {
        $subZipPath = $tempDir . '/' . uniqid('pdf_') . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($subZipPath, ZipArchive::CREATE) !== true) {
            return null;
        }

        $count = 0;
        foreach ($invoices as $invoice) {
            if ($invoice->status === 'draft') {
                continue;
            }

            try {
                $pdfContent = $pdfService->getContent($invoice);
                $zip->addFromString($invoice->number . '.pdf', $pdfContent);
                $count++;
            } catch (\Exception $e) {
                continue;
            }
        }

        if ($count === 0) {
            $zip->close();
            @unlink($subZipPath);

            return null;
        }

        $zip->close();

        return ['path' => $subZipPath];
    }

    private function getExportType(string $format): string
    {
        return match ($format) {
            'faia' => AccountantDownload::TYPE_FAIA,
            'excel' => AccountantDownload::TYPE_EXCEL,
            'pdf_archive' => AccountantDownload::TYPE_PDF_ARCHIVE,
            'accounting_sage_bob' => AccountantDownload::TYPE_ACCOUNTING_SAGE_BOB,
            'accounting_sage_100' => AccountantDownload::TYPE_ACCOUNTING_SAGE_100,
            'accounting_generic' => AccountantDownload::TYPE_ACCOUNTING_GENERIC,
            default => $format,
        };
    }

    private function getExtensionForFormat(string $format): string
    {
        return match ($format) {
            'faia' => 'xml',
            'excel' => 'xlsx',
            'pdf_archive' => 'zip',
            'accounting_sage_bob' => 'txt',
            'accounting_sage_100', 'accounting_generic' => 'csv',
            default => 'txt',
        };
    }

    private function sanitizeFilename(string $name): string
    {
        $name = str_replace(['/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $name);
        $name = preg_replace('/\s+/', '_', $name);

        return mb_substr($name, 0, 50);
    }
}
