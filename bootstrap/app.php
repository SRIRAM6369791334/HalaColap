<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role'               => \App\Http\Middleware\RoleMiddleware::class,
            'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'api.limit'          => \App\Http\Middleware\ApiRateLimitMiddleware::class,
            'tenant'             => \App\Http\Middleware\ResolveTenantContext::class,
        ]);

        $middleware->appendToGroup('web', [
            \App\Http\Middleware\PreventStaleFormCache::class,
            \App\Http\Middleware\ResolveTenantContext::class,
        ]);

        // ✅ Security Headers applied globally to all API responses
        $middleware->appendToGroup('api', [
            \App\Http\Middleware\SecurityHeadersMiddleware::class,
            \App\Http\Middleware\ResolveTenantContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $e->errors()
                ], 422);
            }
        });

        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated'
                ], 401);
            }
        });

        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Resource not found'
                ], 404);
            }
        });

        // ✅ Fix 5: Return JSON 403 for API routes instead of redirect
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if (($request->is('api/*') || $request->expectsJson()) && $e->getStatusCode() === 403) {
                return response()->json([
                    'success' => false,
                    'message' => 'Forbidden. You do not have permission to perform this action.',
                ], 403);
            }
        });
    })->create();
