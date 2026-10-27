<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecurityHeadersMiddleware
 *
 * Adds OWASP-recommended HTTP security headers to every API response
 * to prevent XSS, Clickjacking, MIME sniffing, and information leakage.
 */
class SecurityHeadersMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // ✅ Prevent XSS via Content Security Policy
        $response->headers->set('Content-Security-Policy', "default-src 'none'");

        // ✅ Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // ✅ Prevent Clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // ✅ Force HTTPS (HSTS)
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // ✅ No referrer info leakage
        $response->headers->set('Referrer-Policy', 'no-referrer');

        // ✅ Hide server/tech stack info
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        // ✅ Permissions policy — disable browser features not needed
        $response->headers->set('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }
}
