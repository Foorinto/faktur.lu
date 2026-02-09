<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAccountantAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $accountant = auth('accountant')->user();
        $user = $request->route('user');

        // If user is passed as ID, resolve it
        if (is_numeric($user)) {
            $user = User::find($user);
        }

        if (!$user || !$accountant->hasAccessTo($user)) {
            abort(403, 'Vous n\'avez pas accès à ce client.');
        }

        return $next($request);
    }
}
