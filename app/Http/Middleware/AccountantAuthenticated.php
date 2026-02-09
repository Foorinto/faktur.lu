<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountantAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth('accountant')->check()) {
            return redirect()->route('accountant.login');
        }

        return $next($request);
    }
}
