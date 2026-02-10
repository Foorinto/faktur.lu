<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $locale = App::getLocale();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'csrf_token' => csrf_token(),
            'impersonating' => $request->session()->get('impersonating'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'locale' => $locale,
            'availableLocales' => [
                'fr' => 'Français',
                'de' => 'Deutsch',
                'en' => 'English',
                'lb' => 'Lëtzebuergesch',
            ],
            'translations' => fn () => $this->getTranslations($locale),
        ];
    }

    /**
     * Get translations for the given locale.
     */
    protected function getTranslations(string $locale): array
    {
        $appPath = lang_path("{$locale}/app.php");

        if (file_exists($appPath)) {
            return [
                'app' => require $appPath,
            ];
        }

        // Fallback to French
        $fallbackPath = lang_path('fr/app.php');
        if (file_exists($fallbackPath)) {
            return [
                'app' => require $fallbackPath,
            ];
        }

        return [];
    }
}
