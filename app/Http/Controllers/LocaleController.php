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
        $supportedLocales = config('app.supported_locales', ['fr', 'de', 'en', 'lb']);
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
        $supportedLocales = config('app.supported_locales', ['fr', 'de', 'en', 'lb']);

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

        // Replace locale in path or add it
        $newPath = $this->replaceLocaleInPath($path, $locale, $supportedLocales);

        // If we're on a blog sub-page (tag, category) without a direct translation,
        // fallback to the blog index in the new locale
        if ($this->shouldFallbackToBlogIndex($newPath, $locale, $supportedLocales)) {
            $newPath = '/' . $locale . '/blog';
        }

        return redirect()->to($newPath)
            ->cookie('locale', $locale, 60 * 24 * 365); // 1 year
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
     */
    private function replaceLocaleInPath(string $path, string $newLocale, array $supportedLocales): string
    {
        // Remove leading slash for easier processing
        $path = ltrim($path, '/');

        // Check if path starts with a locale
        $segments = explode('/', $path);
        $firstSegment = $segments[0] ?? '';

        if (in_array($firstSegment, $supportedLocales)) {
            // Replace the existing locale
            $segments[0] = $newLocale;
            return '/' . implode('/', $segments);
        }

        // No locale prefix, add one
        return '/' . $newLocale . '/' . $path;
    }
}
