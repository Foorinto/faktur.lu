<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        // Double authentification.
        //
        // La connexion est servie par Breeze tandis que la 2FA vient de Fortify :
        // l'action RedirectIfTwoFactorAuthenticatable de Fortify n'est jamais
        // exécutée ici. Sans ce branchement, un utilisateur ayant activé la 2FA
        // se connectait avec son seul mot de passe — la protection était donc
        // affichée mais inopérante.
        //
        // On défait la session ouverte par authenticate(), on mémorise le
        // challengé comme le fait Fortify, et on renvoie vers le défi. La
        // vérification du code, la régénération de session et la connexion
        // finale restent assurées par Fortify (two-factor.login.store).
        if ($this->requiresTwoFactorChallenge($request->user())) {
            $userId = $request->user()->getKey();

            Auth::guard('web')->logout();

            $request->session()->put([
                'login.id' => $userId,
                'login.remember' => $request->boolean('remember'),
            ]);

            return redirect()->route('two-factor.login');
        }

        $request->session()->regenerate();

        // Org owner takes precedence (dual-role user keeps their own dashboard).
        $user = $request->user();
        if (!$user->isOrganizationOwner() && $user->isCollaborator()) {
            return redirect()->route('collaborator.dashboard');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }


    /**
     * L'utilisateur a-t-il une double authentification réellement active ?
     *
     * On exige les deux : un secret ET une confirmation. Un secret généré mais
     * jamais confirmé (QR affiché, code jamais saisi) ne doit pas verrouiller
     * l'accès — sinon un utilisateur pourrait s'enfermer dehors en quittant la
     * page d'activation à mi-chemin.
     */
    private function requiresTwoFactorChallenge(?\App\Models\User $user): bool
    {
        return $user !== null
            && ! empty($user->two_factor_secret)
            && $user->two_factor_confirmed_at !== null;
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
