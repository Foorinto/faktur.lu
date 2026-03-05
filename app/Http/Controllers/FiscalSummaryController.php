<?php

namespace App\Http\Controllers;

use App\Models\BusinessSettings;
use App\Services\DashboardService;
use App\Services\FiscalSummaryService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FiscalSummaryController extends Controller
{
    public function __construct(
        protected FiscalSummaryService $fiscalSummaryService,
        protected DashboardService $dashboardService
    ) {}

    /**
     * Display the fiscal summary page.
     */
    public function index(Request $request): Response
    {
        $year = (int) $request->input('year', now()->year);

        return Inertia::render('Reports/FiscalSummary', [
            'year' => $year,
            'years' => $this->fiscalSummaryService->getAvailableYears(),
            'summary' => $this->fiscalSummaryService->getSummary($year),
        ]);
    }

    /**
     * Export the fiscal summary as PDF.
     */
    public function exportPdf(Request $request): HttpResponse
    {
        $year = (int) $request->input('year', now()->year);
        $summary = $this->fiscalSummaryService->getSummary($year);
        $settings = BusinessSettings::first();

        $pdf = Pdf::loadView('pdf.fiscal-summary', [
            'year' => $year,
            'summary' => $summary,
            'settings' => $settings,
            'generatedAt' => now(),
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->download("recapitulatif-fiscal-{$year}.pdf");
    }

    /**
     * Export the fiscal summary as CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $year = (int) $request->input('year', now()->year);
        $summary = $this->fiscalSummaryService->getSummary($year);

        $filename = "recapitulatif-fiscal-{$year}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        $callback = function () use ($summary, $year, $months) {
            $file = fopen('php://output', 'w');

            // BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Title
            fputcsv($file, ["Récapitulatif fiscal {$year}"], ';');
            fputcsv($file, [], ';');

            // Revenue section
            fputcsv($file, ['REVENUS MENSUELS'], ';');
            fputcsv($file, ['Mois', 'CA HT (€)'], ';');
            foreach ($summary['revenue']['by_month'] as $month => $amount) {
                fputcsv($file, [
                    $months[$month],
                    number_format($amount, 2, ',', ''),
                ], ';');
            }
            fputcsv($file, [
                'TOTAL',
                number_format($summary['revenue']['total_ht'], 2, ',', ''),
            ], ';');
            fputcsv($file, [], ';');

            // Expenses section
            fputcsv($file, ['DÉPENSES PAR CATÉGORIE'], ';');
            fputcsv($file, ['Catégorie', 'HT (€)', 'TVA déductible (€)', 'Nb', 'Ligne Form. 152'], ';');
            foreach ($summary['expenses']['by_category'] as $cat => $data) {
                fputcsv($file, [
                    $data['form152_label'],
                    number_format($data['total_ht'], 2, ',', ''),
                    number_format($data['total_vat'], 2, ',', ''),
                    $data['count'],
                    $data['form152_label'],
                ], ';');
            }
            fputcsv($file, [
                'TOTAL',
                number_format($summary['expenses']['total_ht'], 2, ',', ''),
                number_format($summary['expenses']['total_vat_deductible'], 2, ',', ''),
            ], ';');
            fputcsv($file, [], ';');

            // VAT section
            fputcsv($file, ['TVA'], ';');
            fputcsv($file, ['TVA collectée', number_format($summary['vat']['collected'], 2, ',', '')], ';');
            fputcsv($file, ['TVA déductible', number_format($summary['vat']['deductible'], 2, ',', '')], ';');
            fputcsv($file, ['Solde TVA', number_format($summary['vat']['balance'], 2, ',', '')], ';');
            fputcsv($file, [], ';');

            // Net profit
            fputcsv($file, ['RÉSULTAT'], ';');
            fputcsv($file, ['Bénéfice imposable (CA HT - Dépenses HT)', number_format($summary['net_profit'], 2, ',', '')], ';');

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
