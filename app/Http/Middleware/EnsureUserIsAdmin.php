<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->is_admin) {
            abort(403, 'Accès réservé aux administrateurs.');
        }

        // 2FA OBLIGATOIRE pour l'administration. Le panneau donne accès à tous
        // les comptes et à des actions destructives : le mot de passe seul n'y
        // suffit pas. Un admin sans second facteur est renvoyé vers son profil
        // pour l'activer (route hors admin.user, donc toujours atteignable).
        $deuxFacteursActif = !empty($user->two_factor_secret) && $user->two_factor_confirmed_at !== null;
        if (!$deuxFacteursActif) {
            return redirect()->route('profile.edit')
                ->with('error', __('app.admin_requires_2fa'));
        }

        return $next($request);
    }
}
