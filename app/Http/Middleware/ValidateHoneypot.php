<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Anti-bot honeypot + time-based check pour les formulaires publics.
 *
 * Verifie 2 choses :
 * 1. Le champ honeypot 'homepage_url' DOIT etre vide (les bots remplissent
 *    tous les champs visibles).
 * 2. Le timestamp 'form_loaded_at' (en secondes) doit etre present et indiquer
 *    qu'au moins 1.5 seconde s'est ecoulee depuis le chargement du formulaire
 *    (les bots soumettent en quelques ms).
 *
 * En cas d'echec, on rejette avec une 422 silencieuse au lieu de la 419 CSRF
 * habituelle, pour ne pas donner de feedback utile au bot.
 */
class ValidateHoneypot
{
    private const HONEYPOT_FIELD = 'homepage_url';
    private const TIMESTAMP_FIELD = 'form_loaded_at';
    private const MIN_SECONDS = 1.5;

    public function handle(Request $request, Closure $next): Response
    {
        // Honeypot rempli -> bot
        if (! empty($request->input(self::HONEYPOT_FIELD))) {
            return $this->reject($request, 'honeypot_filled');
        }

        // Timestamp absent ou invalide -> bot
        $loadedAt = $request->input(self::TIMESTAMP_FIELD);
        if (! is_numeric($loadedAt)) {
            return $this->reject($request, 'timestamp_missing');
        }

        // Submit trop rapide -> bot
        $elapsed = microtime(true) - (float) $loadedAt;
        if ($elapsed < self::MIN_SECONDS) {
            return $this->reject($request, 'submit_too_fast', ['elapsed' => $elapsed]);
        }

        // Retire les champs anti-bot du request avant de passer au controller
        $request->request->remove(self::HONEYPOT_FIELD);
        $request->request->remove(self::TIMESTAMP_FIELD);

        return $next($request);
    }

    /**
     * Reponse silencieuse avec un message generique (ne pas alerter le bot).
     * On log pour pouvoir analyser les patterns d'attaque cote serveur.
     */
    private function reject(Request $request, string $reason, array $context = []): Response
    {
        Log::warning('Honeypot triggered', array_merge([
            'reason' => $reason,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'route' => $request->path(),
        ], $context));

        // Reponse identique a un succes apparent pour ne pas donner d'indice au bot
        if ($request->expectsJson() || $request->header('X-Inertia')) {
            return back()->withErrors([
                '_form' => __('Une erreur est survenue. Veuillez reessayer.'),
            ]);
        }

        return back()->with('error', __('Une erreur est survenue. Veuillez reessayer.'));
    }
}
