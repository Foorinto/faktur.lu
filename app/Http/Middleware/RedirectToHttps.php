<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Renvoie les requêtes en clair vers HTTPS.
 *
 * L'apex servait la même page sur les deux protocoles : `http://faktur.lu/fr`
 * répondait 200 avec le contenu, au lieu de rediriger. Deux conséquences —
 * un contenu dupliqué que les moteurs peuvent indexer sous deux URL, et des
 * pages servies sans chiffrement à qui arrive par un vieux lien.
 *
 * L'en-tête HSTS était bien présent, mais il ne protège qu'APRÈS une première
 * visite en HTTPS : un robot ou un visiteur qui arrive directement en `http://`
 * n'en bénéficie pas.
 *
 * ⚠️ Pourquoi ici et pas dans `.htaccess` : o2switch réécrit ce fichier via
 * cPanel — `deploy.sh` le stashe à chaque déploiement pour cette raison. Une
 * règle posée là disparaîtrait sans prévenir.
 */
class RedirectToHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->doitRediriger($request)) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        return $next($request);
    }

    private function doitRediriger(Request $request): bool
    {
        if ($request->isSecure()) {
            return false;
        }

        // Le site doit lui-même être en HTTPS : sur un poste de développement
        // servi en http://127.0.0.1, rediriger rendrait l'application
        // inaccessible.
        if (! str_starts_with((string) config('app.url'), 'https://')) {
            return false;
        }

        // Seulement NOTRE hôte.
        //
        // Une requête arrivant sous un autre nom — un accès direct par IP, un
        // en-tête Host inattendu, un domaine parqué — serait renvoyée vers une
        // adresse qui n'est pas la sienne. On ne redirige que ce dont on est
        // sûr.
        if ($request->getHost() !== parse_url((string) config('app.url'), PHP_URL_HOST)) {
            return false;
        }

        // SEULEMENT les méthodes sûres.
        //
        // Une 301 sur un POST fait rejouer la requête en GET par la plupart des
        // clients HTTP : un webhook mal configuré en http:// cesserait de
        // fonctionner au lieu d'être corrigé. Le .htaccess racine documente déjà
        // ce piège pour la redirection des slashs finaux.
        //
        // Rediriger ne protégerait de toute façon rien : les données du POST ont
        // déjà traversé le réseau en clair au moment où on décide.
        return $request->isMethodSafe();
    }
}
