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
        }

        return $next($request);
    }

    /**
     * Determine the locale to use.
     */
    protected function determineLocale(Request $request): string
    {
        // 1. Check query parameter (for testing/switching)
        if ($request->has('lang') && $this->isSupported($request->get('lang'))) {
            return $request->get('lang');
        }

        // 2. Check authenticated user preference
        if ($request->user() && $request->user()->locale) {
            return $request->user()->locale;
        }

        // 3. Check session
        if ($request->session()->has('locale')) {
            return $request->session()->get('locale');
        }

        // 4. Check browser Accept-Language header
        $browserLocale = $request->getPreferredLanguage(self::SUPPORTED_LOCALES);
        if ($browserLocale) {
            return $browserLocale;
        }

        // 5. Default to French
        return config('app.locale', 'fr');
    }

    /**
     * Check if a locale is supported.
     */
    protected function isSupported(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED_LOCALES);
    }
}
