<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ferme le portail comptable tant que le second facteur n'est pas confirmé.
 *
 * L'enrôlement n'est pas proposé, il est imposé : un comptable accède aux
 * données de plusieurs entreprises, et un facteur unique y protège plus de
 * monde qu'ailleurs. Le compte reste authentifié pendant l'enrôlement — sans
 * quoi il n'y aurait aucun moyen d'y procéder — mais ne peut atteindre aucune
 * donnée de client.
 */
class EnsureAccountantHasTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $accountant = $request->user('accountant');

        if ($accountant && ! $accountant->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('accountant.two-factor.enroll');
        }

        return $next($request);
    }
}
