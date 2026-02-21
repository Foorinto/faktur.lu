<?php

namespace App\Http\Controllers\Accountant;

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

        // Get available years (SQLite compatible)
        $years = $user->userInvoices()
            ->where('status', '!=', 'draft')
            ->whereNotNull('finalized_at')
            ->selectRaw("strftime('%Y', finalized_at) as year")
            ->distinct()
            ->orderByDesc('year')
            ->pluck('year')
            ->map(fn($year) => (int) $year)
            ->values();

        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

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
            'recentDownloads' => $recentDownloads,
        ]);
    }
}
