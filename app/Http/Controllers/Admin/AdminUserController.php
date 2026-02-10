<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AdminUserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::query()
            ->withCount(['userInvoices', 'clients'])
            ->withSum('userInvoices', 'total_ttc');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%");
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            match ($status) {
                'verified' => $query->whereNotNull('email_verified_at'),
                'unverified' => $query->whereNull('email_verified_at'),
                'with_2fa' => $query->whereNotNull('two_factor_confirmed_at'),
                'inactive' => $query->where('is_active', false),
                'deleted' => $query->onlyTrashed(),
                default => null,
            };
        }

        // Sort
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $allowedSorts = ['name', 'email', 'created_at', 'user_invoices_count', 'user_invoices_sum_total_ttc'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        $users = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => [
                'search' => $request->get('search', ''),
                'status' => $request->get('status', ''),
                'sort' => $sortField,
                'direction' => $sortDirection,
            ],
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show(int $user)
    {
        $user = User::withTrashed()->findOrFail($user);

        $user->load(['userInvoices' => function ($query) {
            $query->latest()->limit(10);
        }, 'clients']);

        $stats = [
            'total_invoices' => $user->userInvoices()->count(),
            'total_revenue' => $user->userInvoices()->where('status', 'paid')->sum('total_ttc'),
            'paid_invoices' => $user->userInvoices()->where('status', 'paid')->count(),
            'pending_invoices' => $user->userInvoices()->whereIn('status', ['draft', 'sent'])->count(),
            'total_clients' => $user->clients()->count(),
        ];

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'stats' => $stats,
            'recentInvoices' => $user->userInvoices,
        ]);
    }

    /**
     * Toggle user active status.
     */
    public function toggleActive(User $user)
    {
        $user->update([
            'is_active' => !$user->is_active,
        ]);

        $status = $user->is_active ? 'activé' : 'désactivé';

        return back()->with('success', "Compte {$status} avec succès.");
    }

    /**
     * Send password reset to user.
     */
    public function resetPassword(User $user)
    {
        $tempPassword = Str::random(12);

        $user->update([
            'password' => Hash::make($tempPassword),
        ]);

        return back()->with('success', 'Mot de passe réinitialisé. L\'utilisateur devra utiliser "Mot de passe oublié".');
    }

    /**
     * Reset user's 2FA.
     */
    public function reset2fa(User $user)
    {
        $user->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ])->save();

        return back()->with('success', '2FA réinitialisé avec succès.');
    }

    /**
     * Impersonate a user.
     */
    public function impersonate(Request $request, User $user)
    {
        $request->session()->put('impersonating', [
            'admin_session_id' => $request->session()->get('admin_session_id'),
            'user_id' => $user->id,
            'user_name' => $user->name,
            'started_at' => now()->toIso8601String(),
        ]);

        auth()->login($user);

        return redirect()->route('dashboard')->with('info', "Connecté en tant que {$user->name}");
    }

    /**
     * Stop impersonating and return to admin.
     */
    public function stopImpersonation(Request $request)
    {
        $impersonating = $request->session()->get('impersonating');

        if (!$impersonating) {
            return redirect()->route('admin.dashboard');
        }

        $request->session()->forget('impersonating');
        auth()->logout();

        return redirect()->route('admin.dashboard')->with('success', 'Impersonation terminée.');
    }

    /**
     * Delete a user (soft delete).
     */
    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur supprimé (récupérable).');
    }

    /**
     * Restore a soft-deleted user.
     */
    public function restore(int $user)
    {
        $user = User::withTrashed()->findOrFail($user);
        $user->restore();

        return back()->with('success', 'Utilisateur restauré avec succès.');
    }

    /**
     * Permanently delete a user.
     */
    public function forceDelete(int $user)
    {
        $user = User::withTrashed()->findOrFail($user);
        $user->forceDelete();

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur supprimé définitivement.');
    }
}
