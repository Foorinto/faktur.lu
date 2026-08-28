/**
 * Le serveur de production refuse toute requête PATCH.
 *
 * LiteSpeed (o2switch) coupe la connexion sur PATCH, quelle que soit l'URL,
 * avec ou sans corps, quel que soit le type de contenu : le navigateur n'a
 * qu'un « Network Error », Chrome un ERR_HTTP2_PROTOCOL_ERROR, et l'utilisateur
 * voit la page se recharger sans que rien n'ait été enregistré.
 *
 * Constaté le 2026-08-28, reproduit avec curl hors navigateur, en HTTP/2 comme
 * en HTTP/1.1, sur la production ET sur le staging :
 *
 *     PATCH  /fr  → Recv failure: Connection reset by peer
 *     PUT    /fr  → 405   (atteint Laravel)
 *     DELETE /fr  → 419   (atteint Laravel)
 *     POST   /fr  → 405   (atteint Laravel)
 *
 * Ce n'est donc ni le navigateur, ni HTTP/2, ni notre `.htaccess` — qui ne
 * porte aucune règle sur les méthodes. Seul PATCH est refusé, et il l'est
 * avant d'arriver à PHP.
 *
 * On envoie un POST porteur de `X-HTTP-METHOD-OVERRIDE`, que Symfony consulte
 * en premier dans `Request::getMethod()`. Les routes restent déclarées en
 * `Route::patch`, les contrôleurs ne voient aucune différence, et le correctif
 * tient en un seul endroit plutôt qu'en dix-huit appels à réécrire.
 *
 * ⚠️ En local, `php artisan serve` accepte PATCH sans broncher : la panne
 * n'existe qu'une fois déployée. C'est pourquoi la réécriture est active
 * partout et non conditionnée à l'environnement — un correctif qui ne
 * s'exécute jamais là où on le teste ne se teste pas.
 */
export function patchViaPost(config) {
    if (String(config?.method ?? '').toLowerCase() !== 'patch') {
        return config;
    }

    config.method = 'post';
    config.headers['X-HTTP-METHOD-OVERRIDE'] = 'PATCH';

    return config;
}
