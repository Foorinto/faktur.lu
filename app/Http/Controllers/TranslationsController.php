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

        $payload = Cache::rememberForever(
            "translations-payload-{$locale}",
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
        return Cache::rememberForever('translations-fingerprint', function () {
            $empreintes = [];

            foreach (self::LOCALES as $locale) {
                $chemin = lang_path("{$locale}/app.php");
                $empreintes[] = is_file($chemin) ? (string) filemtime($chemin) : '0';
            }

            return substr(md5(implode('-', $empreintes)), 0, 12);
        });
    }

    /** Vide les caches liés, à appeler après un changement de traduction. */
    public static function clearCache(): void
    {
        Cache::forget('translations-fingerprint');

        foreach (self::LOCALES as $locale) {
            Cache::forget("translations-payload-{$locale}");
        }
    }
}
