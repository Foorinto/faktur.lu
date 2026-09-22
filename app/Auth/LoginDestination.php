<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Où envoyer quelqu'un qui vient de se connecter. Une seule réponse pour la
 * connexion par mot de passe et pour la fin d'un défi par e-mail.
 */
class LoginDestination
{
    public static function redirect(User $user, Request $request): RedirectResponse
    {
        // Un propriétaire d'organisation garde son propre tableau de bord, même
        // s'il collabore ailleurs.
        if (! $user->isOrganizationOwner() && $user->isCollaborator()) {
            return redirect()->route('collaborator.dashboard');
        }

        // Un administrateur va sur son panneau.
        //
        // Son URL est volontairement imprononçable, et depuis qu'elle ne figure
        // plus dans la table des routes publiée, elle n'apparaît nulle part côté
        // client : il faut donc l'y conduire, sans quoi il devrait la retenir par
        // cœur. Le lien « Retour à l'application » du panneau fait le chemin
        // inverse.
        //
        // L'usurpation fait exception : prendre l'identité d'un utilisateur puis
        // se voir renvoyer au panneau annulerait l'intérêt de la manœuvre.
        if ($user->is_admin && ! $request->session()->has('impersonator_id')) {
            return redirect()->intended(route('admin.dashboard', absolute: false));
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
