<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Security\SecurityAlerter;
use App\Services\DpaPdfService;
use App\Support\DpaDocument;
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
                'version' => $user->dpa_version ?: DpaDocument::VERSION,
                'current_version' => DpaDocument::VERSION,
                'method' => $user->dpa_acceptance_method,
            ],
        ]);
    }

    /**
     * Exemplaire nominatif de l'accord de traitement des données.
     */
    public function downloadDpa(Request $request, DpaPdfService $service)
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
            // L'ancienne adresse reste prévenue des gestes sensibles pendant
            // trente jours : si ce changement n'était pas du titulaire, c'est
            // le seul canal qui lui reste (voir SecurityAlerter).
            $request->user()->previous_email = $ancienEmail;
            $request->user()->email_changed_at = now();
        }

        $request->user()->save();

        // Prévenir l'ANCIENNE adresse autant que la nouvelle, avec le lien de
        // gel : si le changement n'est pas de son fait, c'est le seul signal
        // que le titulaire reçoit, et le seul moyen qu'il a de réagir.
        if ($emailChange) {
            app(SecurityAlerter::class)->alert(
                $request->user(),
                SecurityAlerter::EMAIL_CHANGED,
                ['email_from' => $ancienEmail, 'email_to' => $request->user()->email],
                alsoTo: [$ancienEmail],
            );
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
