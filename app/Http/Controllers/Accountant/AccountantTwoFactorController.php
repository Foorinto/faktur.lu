<?php

namespace App\Http\Controllers\Accountant;

use App\Http\Controllers\Controller;
use App\Models\Accountant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;

/**
 * Double facteur du portail comptable.
 *
 * Deux moments distincts, à ne pas confondre :
 *
 *  - l'ENRÔLEMENT, imposé au premier accès. Le comptable est authentifié mais
 *    ne peut atteindre aucune donnée tant qu'il n'a pas confirmé son second
 *    facteur ;
 *  - le DÉFI, à chaque connexion ultérieure. Le mot de passe a été vérifié
 *    mais la session n'est PAS ouverte : l'identifiant attend en session, et
 *    rien n'est accessible avant le code.
 *
 * Cette séparation est ce qui fait tenir le dispositif. Ouvrir la session
 * avant le code reviendrait à n'avoir qu'un seul facteur assorti d'une
 * formalité.
 */
class AccountantTwoFactorController extends Controller
{
    /** Clé de session portant l'identifiant en attente de second facteur. */
    public const SESSION_KEY = 'accountant.two_factor.id';

    // --- Enrôlement -------------------------------------------------------

    public function showEnrollment(Request $request)
    {
        $accountant = $request->user('accountant');

        // Un secret est généré dès l'affichage, sans être confirmé : le QR
        // code doit exister pour être scanné. Tant que la confirmation n'a pas
        // eu lieu, `hasEnabledTwoFactorAuthentication()` reste faux et l'accès
        // demeure fermé.
        if (! $accountant->two_factor_secret) {
            app(EnableTwoFactorAuthentication::class)($accountant);
            $accountant->refresh();
        }

        return Inertia::render('Accountant/TwoFactorEnroll', [
            'qrCodeSvg' => $accountant->twoFactorQrCodeSvg(),
            'setupKey' => decrypt($accountant->two_factor_secret),
        ]);
    }

    public function confirmEnrollment(Request $request)
    {
        $request->validate(['code' => ['required', 'string']]);

        $accountant = $request->user('accountant');

        try {
            app(ConfirmTwoFactorAuthentication::class)($accountant, $request->input('code'));
        } catch (ValidationException $e) {
            return back()->withErrors(['code' => __('app.accountant_2fa_invalid_code')]);
        }

        $accountant->refresh();

        return redirect()
            ->route('accountant.two-factor.recovery')
            ->with('success', __('app.accountant_2fa_enabled'));
    }

    /**
     * Codes de secours, montrés une seule fois.
     *
     * Un comptable qui perd son téléphone sans code de secours perd l'accès
     * aux dossiers de tous ses clients : l'écran est une étape du parcours, pas
     * une option enfouie dans un menu.
     */
    public function showRecoveryCodes(Request $request)
    {
        return Inertia::render('Accountant/TwoFactorRecoveryCodes', [
            'recoveryCodes' => $request->user('accountant')->recoveryCodes(),
        ]);
    }

    // --- Défi à la connexion ---------------------------------------------

    public function showChallenge(Request $request)
    {
        if (! $request->session()->has(self::SESSION_KEY)) {
            return redirect()->route('accountant.login');
        }

        return Inertia::render('Accountant/TwoFactorChallenge');
    }

    public function verifyChallenge(Request $request)
    {
        $id = $request->session()->get(self::SESSION_KEY);

        if (! $id) {
            return redirect()->route('accountant.login');
        }

        $accountant = Accountant::find($id);

        if (! $accountant) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('accountant.login');
        }

        $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        if (! $this->passesChallenge($request, $accountant)) {
            return back()->withErrors(['code' => __('app.accountant_2fa_invalid_code')]);
        }

        $request->session()->forget(self::SESSION_KEY);

        Auth::guard('accountant')->login($accountant, $request->session()->pull('accountant.two_factor.remember', false));
        $request->session()->regenerate();

        return redirect()->intended(route('accountant.dashboard'));
    }

    /**
     * Code temporel, ou code de secours — qui est alors consommé.
     */
    private function passesChallenge(Request $request, Accountant $accountant): bool
    {
        if ($code = $request->input('recovery_code')) {
            $valide = collect($accountant->recoveryCodes())->first(fn ($stocke) => hash_equals($stocke, $code));

            if (! $valide) {
                return false;
            }

            // Un code de secours ne sert qu'une fois : le laisser en place
            // reviendrait à créer un mot de passe permanent de contournement.
            $accountant->replaceRecoveryCode($valide);

            return true;
        }

        return app(TwoFactorAuthenticationProvider::class)->verify(
            decrypt($accountant->two_factor_secret),
            (string) $request->input('code')
        );
    }
}
