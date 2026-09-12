<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Features;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $user instanceof MustVerifyEmail,
            'status' => session('status'),
            'confirmsTwoFactorAuthentication' => Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm'),
            // Les comptes créés avant la mise en place de la trace n'ont pas de
            // date d'acceptation : la section le dit plutôt que de laisser
            // croire à un document non signé.
            'dpa' => [
                'accepted_at' => $user->dpa_accepted_at?->toIso8601String(),
                'version' => $user->dpa_version ?: \App\Support\DpaDocument::VERSION,
                'current_version' => \App\Support\DpaDocument::VERSION,
                'method' => $user->dpa_acceptance_method,
            ],
        ]);
    }

    /**
     * Exemplaire nominatif de l'accord de traitement des données.
     */
    public function downloadDpa(Request $request, \App\Services\DpaPdfService $service)
    {
        return $service->download($request->user());
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $ancienEmail = $request->user()->email;

        $request->user()->fill($request->validated());

        $emailChange = $request->user()->isDirty('email');
        if ($emailChange) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        // Prévenir l'ANCIENNE adresse : si le changement n'est pas de son fait,
        // c'est le seul signal que le titulaire reçoit.
        if ($emailChange) {
            \Illuminate\Support\Facades\Notification::route('mail', $ancienEmail)
                ->notify(new \App\Notifications\EmailChangedNotification(
                    $ancienEmail,
                    $request->user()->email,
                    $request->user()->locale ?? 'fr',
                ));
        }

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
