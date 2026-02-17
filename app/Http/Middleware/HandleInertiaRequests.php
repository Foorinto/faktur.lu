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
                'user' => $request->user() ? array_merge(
                    $request->user()->toArray(),
                    [
                        'is_on_trial' => $request->user()->isOnGenericTrial(),
                        'trial_days_remaining' => $request->user()->trialDaysRemaining(),
                        'trial_ends_at' => $request->user()->trial_ends_at?->toISOString(),
                        'is_read_only' => $request->user()->isReadOnly(),
                        'plan_name' => $request->user()->plan,
                    ]
                ) : null,
            ],
            'csrf_token' => csrf_token(),
            'impersonating' => $request->session()->get('impersonating'),
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'locale' => $locale,
            'supportedLocales' => config('app.supported_locales', ['fr', 'de', 'en', 'lb']),
            'availableLocales' => config('app.locale_names', [
                'fr' => 'Français',
                'de' => 'Deutsch',
                'en' => 'English',
                'lb' => 'Lëtzebuergesch',
            ]),
            'translations' => $this->getTranslations($locale),
            'unreadSupportCount' => fn () => $this->getUnreadSupportCount($request),
        ];
    }

    /**
     * Get the count of unread support messages for the current user.
     */
    protected function getUnreadSupportCount(Request $request): int
    {
        $user = $request->user();

        if (!$user) {
            return 0;
        }

        return $user->supportTickets()
            ->where(function ($query) {
                $query->whereHas('messages', function ($q) {
                    $q->where('is_internal', false)
                        ->where('sender_type', '!=', \App\Models\User::class)
                        ->whereRaw('support_messages.created_at > COALESCE(support_tickets.user_last_read_at, support_tickets.created_at)');
                });
            })
            ->count();
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
