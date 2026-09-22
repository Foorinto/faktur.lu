<?php

namespace App\Http\Controllers\Auth;

use App\Auth\Reauthenticator;
use App\Http\Controllers\Controller;
use App\Security\SecurityAlerter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(Request $request, Reauthenticator $reauthenticator, SecurityAlerter $alerter): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required'],
            'two_factor_code' => ['nullable', 'string'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        // Le mot de passe courant, plus le code 2FA si elle est active : une
        // session volée ne doit pas pouvoir en poser un nouveau et enfermer le
        // titulaire dehors.
        $reauthenticator->verify(
            $request->user(),
            $validated['current_password'],
            $request->input('two_factor_code'),
        );

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        // Révoquer les autres sessions : un mot de passe changé doit déconnecter
        // les appareils restés ouverts ailleurs (une session volée ne survit
        // pas au changement). Sessions en base : on supprime les siennes sauf
        // la session courante.
        if (config('session.driver') === 'database') {
            DB::table(config('session.table', 'sessions'))
                ->where('user_id', $request->user()->id)
                ->where('id', '!=', $request->session()->getId())
                ->delete();
        }

        // Le titulaire est prévenu, avec le lien de gel : un mot de passe
        // changé par quelqu'un d'autre est le premier pas d'une prise de
        // contrôle, et l'alerte est sa seule chance de le voir.
        $alerter->alert($request->user(), SecurityAlerter::PASSWORD_CHANGED);

        return back();
    }
}
