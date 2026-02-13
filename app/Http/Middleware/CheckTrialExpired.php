<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTrialExpired
{
    /**
     * Routes that are allowed even when trial is expired (read-only mode).
     */
    protected array $allowedRoutes = [
        'dashboard',
        'logout',
        'subscription.*',
        'settings.subscription.*',
        'profile.*',
        'legal.*',
        'support.*',
        // Allow viewing resources (GET requests to index/show routes)
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // No user or user can access app → continue
        if (!$user || !$user->isReadOnly()) {
            return $next($request);
        }

        // User is in read-only mode (trial expired, no subscription)

        // Allow GET requests (viewing/reading data)
        if ($request->isMethod('GET')) {
            return $next($request);
        }

        // Allow whitelisted routes
        foreach ($this->allowedRoutes as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        // Block create/update/delete operations
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('app.trial_expired_message'),
                'redirect' => route('settings.subscription.index'),
            ], 403);
        }

        return redirect()->route('settings.subscription.index')
            ->with('warning', __('app.trial_expired_message'));
    }
}
