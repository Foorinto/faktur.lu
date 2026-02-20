<?php

namespace App\Providers;

use App\Listeners\LogAuthenticationEvents;
use App\Models\AdminSession;
use App\Services\Peppol\PeppolAccessPointInterface;
use App\Services\Peppol\SimulationService;
use App\Services\Peppol\StorecoveService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind Peppol Access Point interface to implementation based on config
        $this->app->bind(PeppolAccessPointInterface::class, function ($app) {
            return match (config('peppol.provider')) {
                'storecove' => new StorecoveService(),
                default => new SimulationService(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        // Force HTTPS in production
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        // Register audit logging for authentication events
        Event::subscribe(LogAuthenticationEvents::class);

        // Register morph map for polymorphic relations
        Relation::morphMap([
            'admin' => AdminSession::class,
        ]);

        // Configure rate limiters
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // API générale - 60 requêtes/minute par utilisateur ou IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return $this->rateLimitResponse($headers);
                });
        });

        // CRUD standard - 120 requêtes/minute
        RateLimiter::for('crud', function (Request $request) {
            return Limit::perMinute(120)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return $this->rateLimitResponse($headers);
                });
        });

        // Dashboard API - 60 requêtes/minute
        RateLimiter::for('dashboard', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return $this->rateLimitResponse($headers);
                });
        });

        // Génération PDF - 10/minute (opération coûteuse)
        RateLimiter::for('pdf', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return $this->rateLimitResponse($headers, 'Trop de générations PDF. Veuillez patienter.');
                });
        });

        // Preview HTML - 60/minute (moins coûteux que PDF)
        RateLimiter::for('preview', function (Request $request) {
            return Limit::perMinute(60)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return $this->rateLimitResponse($headers, 'Trop de previews. Veuillez patienter.');
                });
        });

        // Export FAIA - 5/heure (très coûteux)
        RateLimiter::for('export', function (Request $request) {
            return Limit::perHour(5)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return $this->rateLimitResponse($headers, 'Trop d\'exports. Veuillez réessayer plus tard.');
                });
        });

        // Envoi email - 20/heure
        RateLimiter::for('email', function (Request $request) {
            return Limit::perHour(20)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return $this->rateLimitResponse($headers, 'Trop d\'emails envoyés. Veuillez patienter.');
                });
        });

        // Inscription - 3/heure par IP uniquement
        RateLimiter::for('register', function (Request $request) {
            return Limit::perHour(3)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return $this->rateLimitResponse($headers, 'Trop de tentatives d\'inscription.');
                });
        });

        // Password reset - 3/heure par IP
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perHour(3)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return $this->rateLimitResponse($headers, 'Trop de demandes de réinitialisation.');
                });
        });

        // Login - 5/minute par IP + email
        RateLimiter::for('login', function (Request $request) {
            $key = $request->input('email', '') . '|' . $request->ip();
            return Limit::perMinute(5)
                ->by($key)
                ->response(function (Request $request, array $headers) {
                    return $this->rateLimitResponse($headers, 'Trop de tentatives de connexion.');
                });
        });

        // Audit logs export - 10/heure
        RateLimiter::for('audit-export', function (Request $request) {
            return Limit::perHour(10)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return $this->rateLimitResponse($headers);
                });
        });

        // Admin login - strict rate limiting (3/minute par IP)
        RateLimiter::for('admin-login', function (Request $request) {
            return Limit::perMinute(3)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return $this->rateLimitResponse($headers, 'Trop de tentatives. Veuillez patienter.');
                });
        });

        // Admin 2FA - separate rate limiting (5/minute par IP)
        RateLimiter::for('admin-2fa', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return $this->rateLimitResponse($headers, 'Trop de tentatives 2FA. Veuillez patienter.');
                });
        });

        // Company lookup API - 30/minute (external API calls)
        RateLimiter::for('company-lookup', function (Request $request) {
            return Limit::perMinute(30)
                ->by($request->user()?->id ?: $request->ip())
                ->response(function (Request $request, array $headers) {
                    return $this->rateLimitResponse($headers, 'Trop de recherches. Veuillez patienter.');
                });
        });

        // Public FAIA Validator - 10/minute par IP (prevent abuse)
        RateLimiter::for('faia-validator', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->ip())
                ->response(function (Request $request, array $headers) {
                    return $this->rateLimitResponse($headers, 'Trop de validations. Veuillez patienter une minute.');
                });
        });
    }

    /**
     * Generate a rate limit response.
     */
    protected function rateLimitResponse(array $headers, ?string $message = null): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
    {
        $retryAfter = $headers['Retry-After'] ?? 60;
        $message = $message ?? 'Trop de requêtes. Veuillez réessayer dans ' . $retryAfter . ' secondes.';

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'message' => $message,
                'retry_after' => (int) $retryAfter,
            ], 429, $headers);
        }

        return response($message, 429, $headers);
    }
}
