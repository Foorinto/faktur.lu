<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    /**
     * Redirect from root to the appropriate locale.
     *
     * Detection priority:
     * 1. Cookie (previous choice)
     * 2. Browser Accept-Language header
     * 3. Default (fr)
     */
    public function redirect(Request $request): RedirectResponse
    {
        $supportedLocales = config('app.supported_locales', ['fr', 'de', 'en', 'lb', 'pt']);
        $defaultLocale = config('app.locale', 'fr');

        // 1. Check cookie for previous choice
        $cookieLocale = $request->cookie('locale');
        if ($cookieLocale && in_array($cookieLocale, $supportedLocales)) {
            return redirect()->to('/' . $cookieLocale . '/');
        }

        // 2. Check session
        $sessionLocale = session('locale');
        if ($sessionLocale && in_array($sessionLocale, $supportedLocales)) {
            return redirect()->to('/' . $sessionLocale . '/');
        }

        // 3. Detect from browser Accept-Language header
        $browserLocale = $this->detectBrowserLocale($request, $supportedLocales);
        if ($browserLocale) {
            return redirect()->to('/' . $browserLocale . '/');
        }

        // 4. Default to configured locale
        return redirect()->to('/' . $defaultLocale . '/');
    }

    /**
     * Detect the preferred locale from browser Accept-Language header.
     */
    private function detectBrowserLocale(Request $request, array $supportedLocales): ?string
    {
        $acceptLanguage = $request->header('Accept-Language');

        if (!$acceptLanguage) {
            return null;
        }

        // Parse Accept-Language header (e.g., "fr-FR,fr;q=0.9,en;q=0.8,de;q=0.7")
        $languages = [];
        foreach (explode(',', $acceptLanguage) as $part) {
            $part = trim($part);
            $qValue = 1.0;

            if (str_contains($part, ';q=')) {
                [$lang, $q] = explode(';q=', $part);
                $qValue = (float) $q;
            } else {
                $lang = $part;
            }

            // Extract primary language code (e.g., "fr" from "fr-FR")
            $primaryLang = strtolower(substr($lang, 0, 2));
            $languages[$primaryLang] = $qValue;
        }

        // Sort by quality value descending
        arsort($languages);

        // Find first supported locale
        foreach (array_keys($languages) as $lang) {
            if (in_array($lang, $supportedLocales)) {
                return $lang;
            }
        }

        return null;
    }

    /**
     * Switch locale and redirect to the same page in the new locale.
     */
    public function switchLocale(Request $request, string $locale): RedirectResponse
    {
        $supportedLocales = config('app.supported_locales', ['fr', 'de', 'en', 'lb', 'pt']);

        if (!in_array($locale, $supportedLocales)) {
            $locale = config('app.locale', 'fr');
        }

        // Store in session and cookie
        session(['locale' => $locale]);

        // Get the intended URL or referer, and replace the locale prefix
        $referer = $request->header('Referer', '/');
        $path = parse_url($referer, PHP_URL_PATH) ?? '/';

        // If we're on a blog article, try to find the translation and redirect to it
        $blogTranslationPath = $this->findBlogTranslationPath($path, $locale, $supportedLocales);
        if ($blogTranslationPath !== null) {
            return redirect()->to($blogTranslationPath)
                ->cookie('locale', $locale, 60 * 24 * 365);
        }

        // Pages sectorielles : le chemin est un motif, pas un slug fixe, donc
        // LOCALIZED_SLUGS ne peut rien pour lui. Sans ce cas, changer de langue
        // depuis /de/rechnungssoftware-krankenpfleger-luxemburg menait à
        // /fr/rechnungssoftware-krankenpfleger-luxemburg, qui n'existe pas.
        $cheminSectoriel = $this->findSectorTranslationPath($path, $locale, $supportedLocales);
        if ($cheminSectoriel !== null) {
            return redirect()->to($cheminSectoriel)
                ->cookie('locale', $locale, 60 * 24 * 365);
        }

        // Replace locale in path or add it
        $newPath = $this->replaceLocaleInPath($path, $locale, $supportedLocales);

        // If we're on a blog sub-page (tag, category) without a direct translation,
        // fallback to the blog index in the new locale
        if ($this->shouldFallbackToBlogIndex($newPath, $locale, $supportedLocales)) {
            $newPath = '/' . $locale . '/blog';
        }

        // Filet : certaines pages n'existent que dans une langue — les
        // comparatifs « alternative à », par exemple. Leur chemin sous un autre
        // préfixe ne correspond à aucune route, et le lecteur qui change de
        // langue tombait sur un 404. La page d'accueil de la langue demandée
        // est une mauvaise réponse, mais moins mauvaise qu'une impasse.
        if (! $this->cheminResoud($newPath)) {
            $newPath = '/' . $locale;
        }

        return redirect()->to($newPath)
            ->cookie('locale', $locale, 60 * 24 * 365); // 1 year
    }

    /**
     * Le chemin traduit correspond-il à une route ?
     *
     * Fiable parce que l'application ne déclare aucune route fourre-tout : si
     * un `Route::fallback` apparaissait, tout chemin correspondrait et ce filet
     * cesserait silencieusement de protéger quoi que ce soit.
     */
    private function cheminResoud(string $chemin): bool
    {
        try {
            app('router')->getRoutes()->match(Request::create($chemin, 'GET'));

            return true;
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException|\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e) {
            return false;
        }
    }

    /**
     * Si le chemin est une page sectorielle, renvoie son équivalent traduit.
     *
     * Les slugs diffèrent d'une langue à l'autre — « krankenpfleger » et
     * « infirmier » désignent la même page — donc un simple échange de préfixe
     * ne suffit pas.
     */
    private function findSectorTranslationPath(string $path, string $newLocale, array $supportedLocales): ?string
    {
        $segments = explode('/', trim($path, '/'));

        if (count($segments) !== 2 || ! in_array($segments[0], $supportedLocales)) {
            return null;
        }

        $cle = \App\Http\Controllers\SectorPageController::keyFromPath($segments[0], $segments[1]);

        if ($cle === null) {
            return null;
        }

        return \App\Http\Controllers\SectorPageController::pathFor($cle, $newLocale);
    }

    /**
     * If the referer path is a blog article with a known translation, return the translated URL.
     * Returns null otherwise.
     */
    private function findBlogTranslationPath(string $path, string $newLocale, array $supportedLocales): ?string
    {
        $segments = explode('/', ltrim($path, '/'));

        // Expected: /{locale}/blog/{slug}
        if (count($segments) !== 3) {
            return null;
        }

        if (!in_array($segments[0], $supportedLocales) || $segments[1] !== 'blog') {
            return null;
        }

        $slug = $segments[2];

        // Skip reserved sub-paths like 'tag', 'categorie', 'kategorie', 'category'
        if (in_array($slug, ['tag', 'categorie', 'kategorie', 'category'])) {
            return null;
        }

        $post = \App\Models\BlogPost::where('slug', $slug)->first();
        if (!$post) {
            return null;
        }

        $translation = $post->findTranslation($newLocale);
        if (!$translation) {
            return null;
        }

        return '/' . $newLocale . '/blog/' . $translation->slug;
    }

    /**
     * Determine if we should fallback to blog index (for article/category/tag pages
     * whose slugs are locale-specific and can't be auto-translated).
     */
    private function shouldFallbackToBlogIndex(string $path, string $locale, array $supportedLocales): bool
    {
        $segments = explode('/', ltrim($path, '/'));

        if (count($segments) < 3) {
            return false;
        }

        $firstSegment = $segments[0] ?? '';
        $secondSegment = $segments[1] ?? '';

        if (!in_array($firstSegment, $supportedLocales)) {
            return false;
        }

        // /{locale}/blog/{slug} or /{locale}/blog/tag/{slug} or /{locale}/blog/categorie/{slug}
        // Any blog sub-path needs to fall back to the blog index
        return $secondSegment === 'blog';
    }

    /**
     * Replace the locale prefix in a path.
     * Si le chemin contient un slug localisé connu (outils, fonctionnalités, etc.),
     * on traduit aussi le slug vers la nouvelle langue. Sinon, simple swap du préfixe.
     */
    private function replaceLocaleInPath(string $path, string $newLocale, array $supportedLocales): string
    {
        // Remove leading slash for easier processing
        $path = ltrim($path, '/');

        // Check if path starts with a locale
        $segments = explode('/', $path);
        $firstSegment = $segments[0] ?? '';

        if (!in_array($firstSegment, $supportedLocales)) {
            // No locale prefix, add one
            return '/' . $newLocale . '/' . $path;
        }

        $oldLocale = $firstSegment;
        $remainingSegments = array_slice($segments, 1);
        $remainingPath = implode('/', $remainingSegments);

        // Try to translate localized slugs (tools, features, about, partners, etc.)
        $translatedRemaining = $this->translateLocalizedSlug($remainingPath, $oldLocale, $newLocale);

        return '/' . $newLocale . ($translatedRemaining !== '' ? '/' . $translatedRemaining : '');
    }

    /**
     * Map des slugs localisés. Doit rester aligné avec
     * resources/js/Composables/useLocalizedRoute.js localizedSlugs.
     * Chaque clé est une "logical route" et chaque valeur un dict locale => slug.
     */
    private const LOCALIZED_SLUGS = [
        'about' => [
            'fr' => 'a-propos', 'de' => 'ueber-uns', 'en' => 'about', 'lb' => 'iwwer-eis', 'pt' => 'sobre',
        ],
        'why_faktur' => [
            'fr' => 'pourquoi-faktur-lu', 'de' => 'warum-faktur-lu', 'en' => 'why-faktur-lu', 'lb' => 'firwat-faktur-lu', 'pt' => 'porque-faktur-lu',
        ],
        'partners' => [
            'fr' => 'partenaires', 'de' => 'partner', 'en' => 'partners', 'lb' => 'partneren', 'pt' => 'parceiros',
        ],
        'pricing' => [
            'fr' => 'tarifs', 'de' => 'preise', 'en' => 'pricing', 'lb' => 'präisser', 'pt' => 'precos',
        ],
        'faia-validator' => [
            'fr' => 'validateur-faia', 'de' => 'faia-validator', 'en' => 'faia-validator', 'lb' => 'faia-validator', 'pt' => 'validador-faia',
        ],
        'features.index' => [
            'fr' => 'fonctionnalites', 'de' => 'funktionen', 'en' => 'features', 'lb' => 'funktiounen', 'pt' => 'funcionalidades',
        ],
        'tools' => [
            'fr' => 'outils', 'de' => 'werkzeuge', 'en' => 'tools', 'lb' => 'handgeschir', 'pt' => 'ferramentas',
        ],
        'tools.vat_calculator' => [
            'fr' => 'outils/calculateur-tva', 'de' => 'werkzeuge/mwst-rechner', 'en' => 'tools/vat-calculator', 'lb' => 'handgeschir/tva-rechner', 'pt' => 'ferramentas/calculadora-iva',
        ],
        'tools.vat_exemption' => [
            'fr' => 'outils/franchise-tva', 'de' => 'werkzeuge/mwst-befreiung', 'en' => 'tools/vat-exemption', 'lb' => 'handgeschir/tva-befreiung', 'pt' => 'ferramentas/isencao-iva',
        ],
        'tools.iban_validator' => [
            'fr' => 'outils/validateur-iban', 'de' => 'werkzeuge/iban-pruefer', 'en' => 'tools/iban-validator', 'lb' => 'handgeschir/iban-validateur', 'pt' => 'ferramentas/validador-iban',
        ],
        'tools.invoice_generator' => [
            'fr' => 'outils/generateur-facture', 'de' => 'werkzeuge/rechnungsgenerator', 'en' => 'tools/invoice-generator', 'lb' => 'handgeschir/rechnungsgenerator', 'pt' => 'ferramentas/gerador-fatura',
        ],
        'tools.templates' => [
            'fr' => 'outils/modeles-facture', 'de' => 'werkzeuge/vorlagen', 'en' => 'tools/templates', 'lb' => 'handgeschir/modellen', 'pt' => 'ferramentas/modelos',
        ],
        'legal.mentions' => [
            'fr' => 'mentions-legales', 'de' => 'impressum', 'en' => 'legal-notice', 'lb' => 'impressum', 'pt' => 'aviso-legal',
        ],
        'legal.privacy' => [
            'fr' => 'confidentialite', 'de' => 'datenschutz', 'en' => 'privacy', 'lb' => 'dateschutz', 'pt' => 'privacidade',
        ],
        'legal.terms' => [
            'fr' => 'cgu', 'de' => 'agb', 'en' => 'terms', 'lb' => 'cgu', 'pt' => 'termos',
        ],
        'contact' => [
            'fr' => 'contact', 'de' => 'contact', 'en' => 'contact', 'lb' => 'contact', 'pt' => 'contacto',
        ],
    ];

    /**
     * Si remainingPath correspond a un slug localise connu, retourne la version
     * traduite dans la nouvelle locale. Sinon retourne le path inchange.
     */
    private function translateLocalizedSlug(string $remainingPath, string $oldLocale, string $newLocale): string
    {
        if ($remainingPath === '') {
            return '';
        }

        // Cherche dans LOCALIZED_SLUGS si le path matche un slug de l'ancienne locale.
        // On essaie d'abord le path complet (utile pour les slugs multi-segments comme outils/franchise-tva).
        // Si pas de match, on essaie segment par segment du plus long au plus court.
        foreach (self::LOCALIZED_SLUGS as $logical => $slugMap) {
            if (!isset($slugMap[$oldLocale]) || !isset($slugMap[$newLocale])) {
                continue;
            }
            $oldSlug = $slugMap[$oldLocale];

            // Match exact
            if ($remainingPath === $oldSlug) {
                return $slugMap[$newLocale];
            }

            // Match comme préfixe (ex: outils/franchise-tva matche le slug "outils/franchise-tva"
            // mais aussi le path "outils" est un préfixe de "outils/franchise-tva"). On veut le match
            // exact en priorité, puis le plus long préfixe.
        }

        // Trier par longueur de slug décroissante pour matcher le plus spécifique en premier
        $sortedSlugs = self::LOCALIZED_SLUGS;
        uasort($sortedSlugs, fn ($a, $b) => strlen($b[$oldLocale] ?? '') <=> strlen($a[$oldLocale] ?? ''));

        foreach ($sortedSlugs as $logical => $slugMap) {
            if (!isset($slugMap[$oldLocale]) || !isset($slugMap[$newLocale])) {
                continue;
            }
            $oldSlug = $slugMap[$oldLocale];
            if ($oldSlug === '') {
                continue;
            }

            // Match comme préfixe avec /
            if (str_starts_with($remainingPath . '/', $oldSlug . '/')) {
                $rest = substr($remainingPath, strlen($oldSlug));
                return $slugMap[$newLocale] . $rest;
            }
        }

        return $remainingPath;
    }
}
