<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectEmployeeOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isEmployee() && !$user->businessSettings) {
            return redirect()->route('employee-portal.dashboard');
        }

        return $next($request);
    }
}
