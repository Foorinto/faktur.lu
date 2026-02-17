<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales.
     */
    public const SUPPORTED_LOCALES = ['fr', 'de', 'en', 'lb'];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);

        if ($this->isSupported($locale)) {
            App::setLocale($locale);
            session(['locale' => $locale]);
        }

        return $next($request);
    }

    /**
     * Determine the locale to use.
     *
     * Priority:
     * 1. URL prefix (for public localized routes)
     * 2. Query parameter ?lang= (for testing/switching)
     * 3. Authenticated user preference
     * 4. Session
     * 5. Browser Accept-Language
     * 6. Default (fr)
     */
    protected function determineLocale(Request $request): string
    {
        // 1. Check URL prefix (e.g., /fr/blog, /de/blog)
        $urlLocale = $request->route('locale');
        if ($urlLocale && $this->isSupported($urlLocale)) {
            return $urlLocale;
        }

        // 2. Check query parameter (for testing/switching)
        if ($request->has('lang') && $this->isSupported($request->get('lang'))) {
            return $request->get('lang');
        }

        // 3. Check authenticated user preference
        if ($request->user() && $request->user()->locale) {
            return $request->user()->locale;
        }

        // 4. Check session
        if ($request->session()->has('locale')) {
            return $request->session()->get('locale');
        }

        // 5. Check browser Accept-Language header
        $browserLocale = $request->getPreferredLanguage(self::SUPPORTED_LOCALES);
        if ($browserLocale) {
            return $browserLocale;
        }

        // 6. Default to French
        return config('app.locale', 'fr');
    }

    /**
     * Check if a locale is supported.
     */
    protected function isSupported(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED_LOCALES);
    }

    /**
     * Get supported locales.
     */
    public static function getSupportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }
}
