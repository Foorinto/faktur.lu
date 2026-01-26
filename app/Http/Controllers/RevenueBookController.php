<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RevenueBookController extends Controller
{
    /**
     * Display the revenue book.
     */
    public function index(Request $request): Response
    {
        // Default to current year
        $startDate = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfYear()->format('Y-m-d'));

        $invoices = Invoice::query()
            ->with('client')
            ->where('status', Invoice::STATUS_PAID)
            ->whereNotNull('paid_at')
            ->whereDate('paid_at', '>=', $startDate)
            ->whereDate('paid_at', '<=', $endDate)
            ->orderBy('paid_at', 'asc')
            ->get();

        // Calculate totals
        $totals = [
            'ht' => $invoices->sum('total_ht'),
            'vat' => $invoices->sum('total_vat'),
            'ttc' => $invoices->sum('total_ttc'),
            'count' => $invoices->count(),
        ];

        // Get VAT breakdown from invoice items
        $vatBreakdown = $this->getVatBreakdown($invoices->pluck('id')->toArray());

        // Get available years for quick selection
        $years = Invoice::query()
            ->where('status', Invoice::STATUS_PAID)
            ->whereNotNull('paid_at')
            ->selectRaw('DISTINCT strftime("%Y", paid_at) as year')
            ->orderByDesc('year')
            ->pluck('year')
            ->filter()
            ->values();

        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        return Inertia::render('Reports/RevenueBook', [
            'invoices' => $invoices,
            'totals' => $totals,
            'vatBreakdown' => $vatBreakdown,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'years' => $years,
            'periods' => $this->getPredefinedPeriods(),
        ]);
    }

    /**
     * Export the revenue book as PDF.
     */
    public function exportPdf(Request $request): HttpResponse
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfYear()->format('Y-m-d'));

        $invoices = Invoice::query()
            ->with('client')
            ->where('status', Invoice::STATUS_PAID)
            ->whereNotNull('paid_at')
            ->whereDate('paid_at', '>=', $startDate)
            ->whereDate('paid_at', '<=', $endDate)
            ->orderBy('paid_at', 'asc')
            ->get();

        $totals = [
            'ht' => $invoices->sum('total_ht'),
            'vat' => $invoices->sum('total_vat'),
            'ttc' => $invoices->sum('total_ttc'),
            'count' => $invoices->count(),
        ];

        $vatBreakdown = $this->getVatBreakdown($invoices->pluck('id')->toArray());

        // Get business settings for header
        $settings = \App\Models\BusinessSettings::first();

        $pdf = Pdf::loadView('pdf.revenue-book', [
            'invoices' => $invoices,
            'totals' => $totals,
            'vatBreakdown' => $vatBreakdown,
            'startDate' => Carbon::parse($startDate),
            'endDate' => Carbon::parse($endDate),
            'settings' => $settings,
            'generatedAt' => now(),
        ]);

        $pdf->setPaper('A4', 'landscape');

        $filename = sprintf(
            'livre-recettes_%s_%s.pdf',
            Carbon::parse($startDate)->format('Y-m-d'),
            Carbon::parse($endDate)->format('Y-m-d')
        );

        return $pdf->download($filename);
    }

    /**
     * Export the revenue book as CSV.
     */
    public function exportCsv(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfYear()->format('Y-m-d'));

        $invoices = Invoice::query()
            ->with('client')
            ->where('status', Invoice::STATUS_PAID)
            ->whereNotNull('paid_at')
            ->whereDate('paid_at', '>=', $startDate)
            ->whereDate('paid_at', '<=', $endDate)
            ->orderBy('paid_at', 'asc')
            ->get();

        $filename = sprintf(
            'livre-recettes_%s_%s.csv',
            Carbon::parse($startDate)->format('Y-m-d'),
            Carbon::parse($endDate)->format('Y-m-d')
        );

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function () use ($invoices) {
            $file = fopen('php://output', 'w');

            // BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // Header row
            fputcsv($file, [
                'Date de paiement',
                'N° Facture',
                'Client',
                'Total HT',
                'Total TVA',
                'Total TTC',
                'Devise',
            ], ';');

            foreach ($invoices as $invoice) {
                fputcsv($file, [
                    $invoice->paid_at->format('d/m/Y'),
                    $invoice->number,
                    $invoice->client->name,
                    number_format($invoice->total_ht, 2, ',', ''),
                    number_format($invoice->total_vat, 2, ',', ''),
                    number_format($invoice->total_ttc, 2, ',', ''),
                    $invoice->currency,
                ], ';');
            }

            // Empty row before totals
            fputcsv($file, [], ';');

            // Totals row
            fputcsv($file, [
                'TOTAL',
                '',
                '',
                number_format($invoices->sum('total_ht'), 2, ',', ''),
                number_format($invoices->sum('total_vat'), 2, ',', ''),
                number_format($invoices->sum('total_ttc'), 2, ',', ''),
                '',
            ], ';');

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get VAT breakdown by rate.
     */
    private function getVatBreakdown(array $invoiceIds): array
    {
        if (empty($invoiceIds)) {
            return [];
        }

        $breakdown = InvoiceItem::query()
            ->whereIn('invoice_id', $invoiceIds)
            ->select('vat_rate')
            ->selectRaw('SUM(total_ht) as total_ht')
            ->selectRaw('SUM(total_vat) as total_vat')
            ->groupBy('vat_rate')
            ->orderByDesc('vat_rate')
            ->get();

        return $breakdown->map(function ($item) {
            return [
                'rate' => (float) $item->vat_rate,
                'base' => (float) $item->total_ht,
                'amount' => (float) $item->total_vat,
            ];
        })->toArray();
    }

    /**
     * Get predefined periods for quick selection.
     */
    private function getPredefinedPeriods(): array
    {
        $now = Carbon::now();

        return [
            [
                'label' => 'Mois en cours',
                'start' => $now->copy()->startOfMonth()->format('Y-m-d'),
                'end' => $now->copy()->endOfMonth()->format('Y-m-d'),
            ],
            [
                'label' => 'Mois précédent',
                'start' => $now->copy()->subMonth()->startOfMonth()->format('Y-m-d'),
                'end' => $now->copy()->subMonth()->endOfMonth()->format('Y-m-d'),
            ],
            [
                'label' => 'Trimestre en cours',
                'start' => $now->copy()->startOfQuarter()->format('Y-m-d'),
                'end' => $now->copy()->endOfQuarter()->format('Y-m-d'),
            ],
            [
                'label' => 'Trimestre précédent',
                'start' => $now->copy()->subQuarter()->startOfQuarter()->format('Y-m-d'),
                'end' => $now->copy()->subQuarter()->endOfQuarter()->format('Y-m-d'),
            ],
            [
                'label' => 'Année en cours',
                'start' => $now->copy()->startOfYear()->format('Y-m-d'),
                'end' => $now->copy()->endOfYear()->format('Y-m-d'),
            ],
            [
                'label' => 'Année précédente',
                'start' => $now->copy()->subYear()->startOfYear()->format('Y-m-d'),
                'end' => $now->copy()->subYear()->endOfYear()->format('Y-m-d'),
            ],
        ];
    }
}
