<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // XSS Protection (legacy browsers)
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Referrer Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy - disable unnecessary features
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=()');

        // HSTS - only in production with HTTPS
        if (config('app.env') === 'production') {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Content Security Policy
        $csp = $this->buildContentSecurityPolicy();
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }

    /**
     * Build the Content-Security-Policy header value.
     */
    protected function buildContentSecurityPolicy(): string
    {
        $directives = [
            // Default fallback
            "default-src 'self'",

            // Scripts - allow inline for Vite/Vue
            "script-src 'self' 'unsafe-inline' 'unsafe-eval'",

            // Styles - allow inline for Tailwind
            "style-src 'self' 'unsafe-inline'",

            // Images - allow data URIs for QR codes
            "img-src 'self' data: https:",

            // Fonts
            "font-src 'self'",

            // Connect - API calls
            "connect-src 'self'",

            // Prevent framing
            "frame-ancestors 'none'",

            // Base URI restriction
            "base-uri 'self'",

            // Form action restriction
            "form-action 'self'",

            // Object/embed restriction
            "object-src 'none'",
        ];

        return implode('; ', $directives);
    }
}
