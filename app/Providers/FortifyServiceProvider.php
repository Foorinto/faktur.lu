<?php

namespace App\Providers;

use App\Auth\ConfirmPasswordWithTwoFactor;
use App\Auth\DisableTwoFactorUnlessRequired;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Laravel\Fortify\Actions\ConfirmPassword;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ⚠️ Fortify résout l'action de confirmation du mot de passe par le
        // conteneur : la remplacer ici impose le code 2FA sur sa route comme
        // sur la nôtre. Voir App\Auth\ConfirmPasswordWithTwoFactor.
        $this->app->bind(ConfirmPassword::class, ConfirmPasswordWithTwoFactor::class);

        // Même mécanique pour la désactivation : un compte exposé garde au
        // moins un second facteur (FEAT-124).
        $this->app->bind(DisableTwoFactorAuthentication::class, DisableTwoFactorUnlessRequired::class);

        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configure Inertia views for 2FA
        Fortify::twoFactorChallengeView(function () {
            return Inertia::render('Auth/TwoFactorChallenge');
        });

        Fortify::confirmPasswordView(function () {
            return Inertia::render('Auth/ConfirmPassword');
        });

        // Rate limiting for 2FA
        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
