<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

/**
 * Sert les traductions du front dans un fichier à part, mis en cache par le
 * navigateur.
 *
 * Elles étaient jusqu'ici réinjectées dans le HTML de CHAQUE page, via la prop
 * Inertia `translations` : 323 Ko bruts, 90 Ko transférés, à chaque navigation,
 * alors qu'une page n'en utilise qu'une poignée. Cela représentait plus de la
 * moitié du poids d'un document.
 *
 * Servies à part, elles ne sont téléchargées qu'une fois : l'URL porte une
 * empreinte du contenu, ce qui autorise un cache immuable et long. Un
 * changement de traduction change l'empreinte, donc l'URL, donc le cache est
 * contourné sans purge à faire.
 */
class TranslationsController extends Controller
{
    /** Langues servies. Une locale inconnue retombe sur le français. */
    private const LOCALES = ['fr', 'en', 'de', 'lb', 'pt'];

    public function show(string $locale): JsonResponse
    {
        if (! in_array($locale, self::LOCALES, true)) {
            $locale = 'fr';
        }

        // La clé de cache porte l'empreinte : modifier un fichier de langue
        // change l'empreinte, donc la clé, donc le contenu servi. Le cache
        // s'invalide de lui-même, sans purge à penser.
        $payload = Cache::remember(
            "translations-payload-{$locale}-".self::fingerprint(),
            now()->addDay(),
            fn () => \App\Http\Middleware\HandleInertiaRequests::translationsFor($locale)
        );

        return response()
            ->json($payload)
            // Immuable : l'empreinte étant dans l'URL, ce contenu ne changera
            // jamais pour cette adresse. Un an est la valeur recommandée.
            ->header('Cache-Control', 'public, max-age=31536000, immutable');
    }

    /**
     * Empreinte du contenu, injectée dans l'URL.
     *
     * Calculée sur les fichiers de langue eux-mêmes plutôt que sur le payload :
     * c'est moins cher, et toute modification d'un fichier la fait changer.
     */
    public static function fingerprint(): string
    {
        // Volontairement PAS mise en cache. Elle l'était, et ajouter une clé de
        // traduction ne changeait alors plus l'empreinte : le navigateur gardait
        // l'ancien fichier et affichait la clé brute. Cinq `filemtime` par
        // requête coûtent moins qu'une classe entière de bugs de cache.
        $empreintes = [];

        foreach (self::LOCALES as $locale) {
            $chemin = lang_path("{$locale}/app.php");
            $empreintes[] = is_file($chemin) ? (string) filemtime($chemin) : '0';
        }

        return substr(md5(implode('-', $empreintes)), 0, 12);
    }
}
