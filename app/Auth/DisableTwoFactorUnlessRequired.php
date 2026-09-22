<?php

namespace App\Auth;

use App\Models\User;
use App\Security\TwoFactorPolicy;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;

/**
 * Remplace l'action de Fortify dans le conteneur : un compte exposé ne
 * désactive pas son application d'authentification s'il n'a pas le code par
 * e-mail pour prendre le relais. Le profil cache le bouton ; ceci arrête la
 * requête forcée.
 */
class DisableTwoFactorUnlessRequired extends DisableTwoFactorAuthentication
{
    public function __construct(private TwoFactorPolicy $policy) {}

    public function __invoke($user)
    {
        if ($user instanceof User && ! $this->policy->canDisableApp($user)) {
            throw ValidationException::withMessages([
                'two_factor' => __('app.two_factor_notice.required'),
            ]);
        }

        parent::__invoke($user);
    }
}
