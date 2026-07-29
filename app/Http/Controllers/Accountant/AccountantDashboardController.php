<?php

namespace App\Http\Controllers\Accountant;

use App\Helpers\DatabaseHelper;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountantDashboardController extends Controller
{
    /**
     * Show the accountant dashboard with list of clients.
     */
    public function index(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer|min:2020|max:2100',
            'quarter' => 'nullable|integer|min:1|max:4',
            'search' => 'nullable|string|max:100',
        ]);

        $accountant = auth('accountant')->user();
        $year = $request->integer('year') ?: now()->year;
        $quarter = $request->integer('quarter') ?: null;

        // Build period boundaries
        if ($quarter) {
            $startMonth = ($quarter - 1) * 3 + 1;
            $periodStart = Carbon::create($year, $startMonth, 1)->startOfDay();
            $periodEnd = $periodStart->copy()->addMonths(3)->subDay()->endOfDay();
        } else {
            $periodStart = Carbon::create($year, 1, 1)->startOfDay();
            $periodEnd = Carbon::create($year, 12, 31)->endOfDay();
        }

        $clients = $accountant->activeClients()
            ->with('businessSettings')
            ->get()
            ->map(function ($client) use ($accountant, $periodStart, $periodEnd) {
                $invoices = $client->userInvoices()
                    ->whereIn('status', [Invoice::STATUS_FINALIZED, Invoice::STATUS_SENT, Invoice::STATUS_PAID])
                    ->whereBetween('finalized_at', [$periodStart, $periodEnd])
                    ->get();

                $invoicesOnly = $invoices->where('type', Invoice::TYPE_INVOICE);
                $creditNotes = $invoices->where('type', Invoice::TYPE_CREDIT_NOTE);

                $lastDownload = $accountant->downloads()
                    ->where('user_id', $client->id)
                    ->latest()
                    ->first();

                return [
                    'id' => $client->id,
                    'name' => $client->businessSettings?->company_name ?? $client->name,
                    'email' => $client->email,
                    'vat_number' => $client->businessSettings?->vat_number,
                    'ca_ht' => round($invoicesOnly->sum('total_ht'), 2),
                    'total_vat' => round($invoices->sum('total_tax'), 2),
                    'total_ttc' => round($invoices->sum('total_ttc'), 2),
                    'invoices_count' => $invoicesOnly->count(),
                    'credit_notes_count' => $creditNotes->count(),
                    'last_export_at' => $lastDownload?->created_at?->format('d/m/Y H:i'),
                ];
            });

        // Apply search filter
        $search = trim($request->string('search')->value());
        if ($search) {
            $clients = $clients->filter(
                fn ($c) => str_contains(mb_strtolower($c['name']), mb_strtolower($search))
                    || str_contains(mb_strtolower($c['vat_number'] ?? ''), mb_strtolower($search))
            )->values();
        }

        return Inertia::render('Accountant/Dashboard', [
            'accountant' => [
                'name' => $accountant->display_name,
                'email' => $accountant->email,
            ],
            'clients' => $clients,
            'filters' => [
                'year' => $year,
                'quarter' => $quarter,
                'search' => $search,
            ],
            'years' => collect(range(now()->year, 2020, -1)),
            'formats' => [
                ['value' => 'faia', 'label' => 'Export FAIA (XML)'],
                ['value' => 'excel', 'label' => 'Export Excel'],
                ['value' => 'pdf_archive', 'label' => 'Archive PDF'],
                ['value' => 'accounting_sage_bob', 'label' => 'Sage BOB 50 (ASCII)'],
                ['value' => 'accounting_sage_100', 'label' => 'Sage 100 (CSV)'],
                ['value' => 'accounting_generic', 'label' => 'Export comptable (CSV)'],
            ],
        ]);
    }

    /**
     * Show a client's export page.
     */
    public function client(Request $request, User $user)
    {
        $accountant = auth('accountant')->user();

        $request->validate([
            'year' => 'nullable|integer|min:2020|max:2100',
            'quarter' => 'nullable|integer|min:1|max:4',
        ]);

        // Années disponibles. Passe par DatabaseHelper : strftime() n'existe pas
        // en MySQL, et cette page tourne en production sur MySQL.
        $years = $user->userInvoices()
            ->where('status', '!=', 'draft')
            ->whereNotNull('finalized_at')
            ->selectRaw(DatabaseHelper::year('finalized_at').' as year')
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn($year) => (int) $year)
            ->values();

        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        $selectedYear = $request->integer('year') ?: ($years->first() ?? now()->year);
        $selectedQuarter = $request->integer('quarter') ?: null;

        // Get invoices for selected period
        $invoiceQuery = $user->userInvoices()
            ->whereIn('status', [Invoice::STATUS_FINALIZED, Invoice::STATUS_SENT, Invoice::STATUS_PAID])
            ->whereNotNull('finalized_at')
            ->whereRaw(DatabaseHelper::year('finalized_at').' = ?', [$selectedYear]);

        if ($selectedQuarter) {
            $startMonth = ($selectedQuarter - 1) * 3 + 1;
            $endMonth = $startMonth + 2;
            $invoiceQuery->whereRaw(DatabaseHelper::month('finalized_at').' BETWEEN ? AND ?', [$startMonth, $endMonth]);
        }

        $invoices = $invoiceQuery
            ->with('client:id,name')
            ->latest('finalized_at')
            ->get()
            ->map(fn ($inv) => [
                'id' => $inv->id,
                'number' => $inv->number,
                'type' => $inv->type,
                'status' => $inv->status,
                'client_name' => $inv->client?->name,
                'total_ht' => round($inv->total_ht, 2),
                'total_ttc' => round($inv->total_ttc, 2),
                'issued_at' => $inv->issued_at?->format('d/m/Y'),
                'finalized_at' => $inv->finalized_at?->format('d/m/Y'),
                'paid_at' => $inv->paid_at?->format('d/m/Y'),
                'currency' => $inv->currency,
            ]);

        // Summary stats
        $invoicesOnly = $invoices->where('type', Invoice::TYPE_INVOICE);
        $creditNotes = $invoices->where('type', Invoice::TYPE_CREDIT_NOTE);
        $summary = [
            'invoices_count' => $invoicesOnly->count(),
            'credit_notes_count' => $creditNotes->count(),
            'total_ht' => round($invoicesOnly->sum('total_ht'), 2),
            'total_ttc' => round($invoices->sum('total_ttc'), 2),
            'paid_count' => $invoices->where('status', 'paid')->count(),
            'unpaid_count' => $invoices->whereIn('status', ['finalized', 'sent'])->count(),
        ];

        // Get recent downloads
        $recentDownloads = $accountant->downloads()
            ->where('user_id', $user->id)
            ->latest()
            ->take(5)
            ->get()
            ->map(fn($download) => [
                'id' => $download->id,
                'export_type' => $download->export_type,
                'export_type_label' => $download->export_type_label,
                'period' => $download->period,
                'downloaded_at' => $download->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Accountant/Client', [
            'client' => [
                'id' => $user->id,
                'name' => $user->businessSettings?->company_name ?? $user->name,
                'email' => $user->email,
                'vat_number' => $user->businessSettings?->vat_number,
            ],
            'years' => $years,
            'selectedYear' => $selectedYear,
            'selectedQuarter' => $selectedQuarter,
            'invoices' => $invoices,
            'summary' => $summary,
            'recentDownloads' => $recentDownloads,
        ]);
    }
}
