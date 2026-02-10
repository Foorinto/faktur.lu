<?php

namespace App\Http\Controllers;

use App\Models\AccountantInvitation;
use App\Notifications\AccountantInvitationNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountantSettingsController extends Controller
{
    /**
     * Show the accountant settings page.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get active accountants
        $accountants = $user->activeAccountants()
            ->get()
            ->map(fn($accountant) => [
                'id' => $accountant->id,
                'name' => $accountant->display_name,
                'email' => $accountant->email,
                'granted_at' => $accountant->pivot->granted_at
                    ? Carbon::parse($accountant->pivot->granted_at)->format('d/m/Y')
                    : null,
                'last_download' => $accountant->downloads()
                    ->where('user_id', $user->id)
                    ->latest()
                    ->first()?->created_at?->format('d/m/Y H:i'),
            ]);

        // Get pending invitations
        $pendingInvitations = $user->accountantInvitations()
            ->pending()
            ->get()
            ->map(fn($invitation) => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'name' => $invitation->name,
                'created_at' => $invitation->created_at->format('d/m/Y'),
                'expires_at' => $invitation->expires_at->format('d/m/Y'),
            ]);

        // Get recent downloads
        $recentDownloads = $user->accountantDownloads()
            ->with('accountant')
            ->latest()
            ->take(10)
            ->get()
            ->map(fn($download) => [
                'id' => $download->id,
                'accountant_name' => $download->accountant->display_name,
                'export_type' => $download->export_type,
                'export_type_label' => $download->export_type_label,
                'period' => $download->period,
                'downloaded_at' => $download->created_at->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Settings/Accountant', [
            'accountants' => $accountants,
            'pendingInvitations' => $pendingInvitations,
            'recentDownloads' => $recentDownloads,
        ]);
    }

    /**
     * Send an invitation to an accountant.
     */
    public function invite(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'name' => 'nullable|string|max:255',
        ]);

        $user = $request->user();

        // Check if already invited or has access
        $existingInvitation = $user->accountantInvitations()
            ->where('email', strtolower($validated['email']))
            ->where('status', AccountantInvitation::STATUS_PENDING)
            ->first();

        if ($existingInvitation) {
            return back()->withErrors(['email' => 'Une invitation est déjà en attente pour cette adresse.']);
        }

        $existingAccess = $user->activeAccountants()
            ->where('accountants.email', strtolower($validated['email']))
            ->first();

        if ($existingAccess) {
            return back()->withErrors(['email' => 'Ce comptable a déjà accès à vos données.']);
        }

        // Create invitation
        $invitation = AccountantInvitation::createForUser(
            $user,
            $validated['email'],
            $validated['name']
        );

        // Send invitation email
        $invitation->user = $user; // Ensure relationship is loaded
        \Notification::route('mail', $invitation->email)
            ->notify(new AccountantInvitationNotification($invitation));

        return back()->with('success', 'Invitation envoyée à ' . $invitation->email);
    }

    /**
     * Resend an invitation.
     */
    public function resendInvitation(Request $request, AccountantInvitation $invitation)
    {
        if ($invitation->user_id !== $request->user()->id) {
            abort(403);
        }

        if (!$invitation->isValid()) {
            // Extend expiration
            $invitation->update(['expires_at' => now()->addDays(7)]);
        }

        // Resend email
        \Notification::route('mail', $invitation->email)
            ->notify(new AccountantInvitationNotification($invitation));

        return back()->with('success', 'Invitation renvoyée.');
    }

    /**
     * Cancel a pending invitation.
     */
    public function cancelInvitation(Request $request, AccountantInvitation $invitation)
    {
        if ($invitation->user_id !== $request->user()->id) {
            abort(403);
        }

        $invitation->revoke();

        return back()->with('success', 'Invitation annulée.');
    }

    /**
     * Revoke an accountant's access.
     */
    public function revokeAccess(Request $request, int $accountantId)
    {
        $user = $request->user();

        $user->accountants()->updateExistingPivot($accountantId, [
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);

        return back()->with('success', 'Accès révoqué.');
    }
}
