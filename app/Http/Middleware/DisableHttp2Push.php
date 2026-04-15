<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Force la fermeture de connexion HTTP pour éviter les erreurs 421
 * "Misdirected Request" sur les hébergements mutualisés avec HTTP/2.
 */
class DisableHttp2Push
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Force le navigateur à ne pas réutiliser cette connexion
        $response->headers->set('Connection', 'close');
        // Empêche le coalescing HTTP/2
        $response->headers->set('Alt-Svc', 'clear');

        return $response;
    }
}
