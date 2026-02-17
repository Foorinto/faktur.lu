<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocalePrefix
{
    /**
     * Handle an incoming request.
     *
     * Set the application locale based on the URL prefix.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->route('locale');
        $supportedLocales = config('app.supported_locales', ['fr', 'de', 'en', 'lb']);

        if ($locale && in_array($locale, $supportedLocales)) {
            app()->setLocale($locale);
            session(['locale' => $locale]);

            // Share locale with views/Inertia
            view()->share('locale', $locale);
        }

        return $next($request);
    }
}
