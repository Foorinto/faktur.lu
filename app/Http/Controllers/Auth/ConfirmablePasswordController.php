<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Actions\ConfirmPassword;

class ConfirmablePasswordController extends Controller
{
    /**
     * Show the confirm password view.
     */
    public function show(Request $request): Response
    {
        return Inertia::render('Auth/ConfirmPassword', [
            // L'écran ne montre le champ du code que si la 2FA est active :
            // le demander à qui ne l'a pas serait une impasse.
            'requiresTwoFactor' => $request->user()->hasEnabledTwoFactorAuthentication(),
        ]);
    }

    /**
     * Confirm the user's password.
     */
    public function store(Request $request): RedirectResponse
    {
        // La même action que Fortify, remplacée dans le conteneur : mot de
        // passe, plus code 2FA si elle est active. Elle lève une
        // ValidationException portée par le bon champ.
        app(ConfirmPassword::class)(Auth::guard('web'), $request->user(), $request->input('password'));

        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
