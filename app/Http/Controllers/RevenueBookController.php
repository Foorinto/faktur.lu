<?php

namespace App\Http\Controllers;

use App\Helpers\DatabaseHelper;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\PlanService;
use App\Services\VentilationEncaissements;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RevenueBookController extends Controller
{
    use \App\Support\VentileLaTvaParTaux;

    /**
     * Display the revenue book.
     */
    public function index(Request $request): Response
    {
        // Default to current year
        $startDate = $request->input('start_date', Carbon::now()->startOfYear()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfYear()->format('Y-m-d'));

        // Le livre de recettes est consultable par tous ; l'historique et les
        // exports sont la part vendue. Un compte gratuit voit donc l'année en
        // cours, et les années passées lui sont montrées verrouillées plutôt
        // que cachées : une limite invisible ne se comprend pas, et surtout
        // elle ne se lève jamais.
        $historiqueComplet = app(PlanService::class)
            ->hasFeature($request->user(), 'accounting_exports');

        if (! $historiqueComplet) {
            [$startDate, $endDate] = $this->bornerALAnneeEnCours($startDate, $endDate);
        }

        $invoices = Invoice::query()
            // `vat_breakdown` lit les lignes ET les remises de chaque facture :
            // sans chargement anticipé, un exercice complet ferait deux requêtes
            // par facture.
            ->with(['client', 'items', 'discounts'])
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
        $vatBreakdown = $this->ventilationTvaParTaux($invoices);

        // Get available years for quick selection
        $years = Invoice::query()
            ->where('status', Invoice::STATUS_PAID)
            ->whereNotNull('paid_at')
            ->selectRaw(DatabaseHelper::distinctYear('paid_at'))
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
            'parMoyenDePaiement' => app(VentilationEncaissements::class)
                ->surPeriode($request->user()->id, $startDate, $endDate),
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'years' => $years,
            'periods' => $this->getPredefinedPeriods($historiqueComplet),
            'historiqueComplet' => $historiqueComplet,
            'anneeAutorisee' => (int) Carbon::now()->year,
            'anneesVerrouillees' => $historiqueComplet ? [] : $this->anneesVerrouillees($years),
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
            // `vat_breakdown` lit les lignes ET les remises de chaque facture :
            // sans chargement anticipé, un exercice complet ferait deux requêtes
            // par facture.
            ->with(['client', 'items', 'discounts'])
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

        $vatBreakdown = $this->ventilationTvaParTaux($invoices);

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

        $prefixes = [
            'fr' => 'livre-recettes', 'de' => 'einnahmenbuch',
            'en' => 'revenue-book', 'lb' => 'recettenbuch', 'pt' => 'livro-receitas',
        ];
        $prefix = $prefixes[app()->getLocale()] ?? $prefixes['fr'];

        $filename = sprintf(
            '%s_%s_%s.pdf',
            $prefix,
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
            // `vat_breakdown` lit les lignes ET les remises de chaque facture :
            // sans chargement anticipé, un exercice complet ferait deux requêtes
            // par facture.
            ->with(['client', 'items', 'discounts'])
            ->where('status', Invoice::STATUS_PAID)
            ->whereNotNull('paid_at')
            ->whereDate('paid_at', '>=', $startDate)
            ->whereDate('paid_at', '<=', $endDate)
            ->orderBy('paid_at', 'asc')
            ->get();

        $prefixes = [
            'fr' => 'livre-recettes', 'de' => 'einnahmenbuch',
            'en' => 'revenue-book', 'lb' => 'recettenbuch', 'pt' => 'livro-receitas',
        ];
        $prefix = $prefixes[app()->getLocale()] ?? $prefixes['fr'];

        $filename = sprintf(
            '%s_%s_%s.csv',
            $prefix,
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
     * Get predefined periods for quick selection.
     */
    private function getPredefinedPeriods(bool $historiqueComplet = true): array
    {
        $now = Carbon::now();
        $debutAnnee = $now->copy()->startOfYear()->format('Y-m-d');
        $finAnnee = $now->copy()->endOfYear()->format('Y-m-d');

        $periodes = [
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

        // Une période qui déborde de l'année en cours est marquée verrouillée
        // plutôt que retirée. « Mois précédent » consulté en janvier vise
        // décembre de l'an dernier : le bouton doit le dire, pas disparaître ni
        // ramener silencieusement autre chose.
        foreach ($periodes as $i => $periode) {
            $periodes[$i]['verrouille'] = ! $historiqueComplet
                && ($periode['start'] < $debutAnnee || $periode['end'] > $finAnnee);
        }

        return $periodes;
    }

    /**
     * Ramène une période demandée dans l'année en cours.
     *
     * ⚠️ Ce bornage vit côté serveur, et c'est le seul qui compte : la page
     * peut bien griser des boutons, il suffit d'écrire `?start_date=2024-01-01`
     * dans la barre d'adresse pour la contourner.
     *
     * Une plage entièrement hors de l'année devient vide après rognage ; on
     * rend alors l'année complète, qui est ce que l'utilisateur a le droit de
     * voir, plutôt qu'un tableau vide sans explication.
     *
     * @return array{0: string, 1: string}
     */
    private function bornerALAnneeEnCours(string $debut, string $fin): array
    {
        $debutAnnee = Carbon::now()->startOfYear()->format('Y-m-d');
        $finAnnee = Carbon::now()->endOfYear()->format('Y-m-d');

        $debut = max($debut, $debutAnnee);
        $fin = min($fin, $finAnnee);

        if ($debut > $fin) {
            return [$debutAnnee, $finAnnee];
        }

        return [$debut, $fin];
    }

    /**
     * Années hors de portée, avec leur total encaissé.
     *
     * Le montant est délibérément affiché : c'est lui qui rend la limite
     * compréhensible. « 2025 — 12 340 € » se lit comme une donnée qu'on possède
     * et qu'on peut rouvrir, là où une année absente ne se lit pas du tout.
     *
     * @param  \Illuminate\Support\Collection<int, int|string>  $annees
     * @return array<int, array{annee: int, total: float}>
     */
    private function anneesVerrouillees($annees): array
    {
        $anneeEnCours = (int) Carbon::now()->year;

        $totaux = Invoice::query()
            ->where('status', Invoice::STATUS_PAID)
            ->whereNotNull('paid_at')
            ->selectRaw(DatabaseHelper::year('paid_at') . ' as year, SUM(total_ttc) as total')
            ->groupBy('year')
            ->pluck('total', 'year')
            // ⚠️ MySQL renvoie l'année de YEAR() en chaîne sur l'hébergement
            // mutualisé, SQLite en chaîne aussi via strftime. Les clés sont
            // ramenées à l'entier ici plutôt que de compter sur la conversion
            // implicite des clés numériques : un total qui ne se retrouve pas
            // s'affiche « 0 € » sans que rien n'échoue, et c'est invisible en
            // test. Voir la famille de pièges o2switch.
            ->mapWithKeys(fn ($total, $annee) => [(int) $annee => $total]);

        return collect($annees)
            ->map(fn ($annee) => (int) $annee)
            ->filter(fn (int $annee) => $annee !== $anneeEnCours)
            ->sortDesc()
            ->map(fn (int $annee) => [
                'annee' => $annee,
                'total' => round((float) ($totaux[$annee] ?? 0), 2),
            ])
            ->values()
            ->all();
    }
}
