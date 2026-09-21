<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Laravel\Fortify\Actions\ConfirmPassword;

/**
 * La confirmation de mot de passe de Fortify, augmentée du code 2FA.
 *
 * ⚠️ Fortify enregistre sa propre route `POST user/confirm-password`, en plus
 * de la nôtre, et les deux posent la même clé de session
 * `auth.password_confirmed_at`. Demander le code 2FA sur notre page seule
 * laisserait la sienne ouverte au mot de passe seul : c'est par là qu'une
 * session volée irait désactiver la 2FA. Fortify résout son action par le
 * conteneur, on la remplace donc à la source, et les deux routes obéissent à
 * la même règle.
 *
 * Fortify appelle l'action avec le seul mot de passe. Le code se lit sur la
 * requête courante : implicite, mais c'est le prix d'une règle unique.
 */
class ConfirmPasswordWithTwoFactor extends ConfirmPassword
{
    public function __construct(private Reauthenticator $reauthenticator) {}

    /**
     * @param  User  $user
     */
    public function __invoke(StatefulGuard $guard, $user, ?string $password = null): bool
    {
        $this->reauthenticator->verify(
            $user,
            $password,
            request()->input('two_factor_code'),
            passwordField: 'password',
        );

        return true;
    }
}
