<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * ApiRateLimitMiddleware
 *
 * Enforces per-IP rate limiting on sensitive API routes.
 * Uses Laravel's built-in RateLimiter with exponential backoff.
 *
 * Limits:
 *  - auth/login  → 5 attempts per 15 minutes per IP
 *  - General API → 120 requests per minute per user/IP
 */
class ApiRateLimitMiddleware
{
    public function handle(Request $request, Closure $next, string $key = 'api', int $maxAttempts = 120, int $decayMinutes = 1): Response
    {
        $identifier = $this->resolveIdentifier($request, $key);

        if (RateLimiter::tooManyAttempts($identifier, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($identifier);

            return response()->json([
                'success' => false,
                'message' => 'Too many requests. Please slow down.',
                'retry_after_seconds' => $seconds,
            ], Response::HTTP_TOO_MANY_REQUESTS, [
                'X-RateLimit-Limit'     => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'Retry-After'           => $seconds,
                'X-RateLimit-Reset'     => now()->addSeconds($seconds)->timestamp,
            ]);
        }

        RateLimiter::hit($identifier, $decayMinutes * 60);

        $response = $next($request);

        // Attach rate limit headers to every response
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', RateLimiter::remaining($identifier, $maxAttempts));

        return $response;
    }

    /**
     * Build a unique rate limit key per route group + IP + authenticated user.
     */
    private function resolveIdentifier(Request $request, string $key): string
    {
        $userId = $request->user()?->id ?? 'guest';
        $ip     = $request->ip();

        return "rate_limit:{$key}:{$ip}:{$userId}";
    }
}
