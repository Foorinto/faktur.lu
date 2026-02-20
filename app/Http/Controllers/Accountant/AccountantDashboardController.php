<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountantDashboardController extends Controller
{
    /**
     * Show the accountant dashboard with list of clients.
     */
    public function index(Request $request)
    {
        $accountant = auth('accountant')->user();

        $clients = $accountant->activeClients()
            ->with('businessSettings')
            ->get()
            ->map(fn($client) => [
                'id' => $client->id,
                'name' => $client->businessSettings?->company_name ?? $client->name,
                'email' => $client->email,
                'vat_number' => $client->businessSettings?->vat_number,
                'last_invoice_at' => $client->userInvoices()
                    ->where('status', '!=', 'draft')
                    ->latest('finalized_at')
                    ->first()?->finalized_at?->format('d/m/Y'),
                'invoices_count' => $client->userInvoices()
                    ->where('status', '!=', 'draft')
                    ->count(),
            ]);

        return Inertia::render('Accountant/Dashboard', [
            'accountant' => [
                'name' => $accountant->display_name,
                'email' => $accountant->email,
            ],
            'clients' => $clients,
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
