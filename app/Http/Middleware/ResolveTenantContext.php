<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenantContext
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. If not authenticated, proceed (login, registration, public assets)
        if (! $user) {
            return $next($request);
        }

        // 2. Check if user account is deactivated
        if (! (bool) $user->is_active) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account has been deactivated.',
                ], 403);
            }

            \Illuminate\Support\Facades\Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['email' => 'Your account has been deactivated.']);
        }

        $isSuperAdmin = $user->isSuperAdmin();
        $tenant = null;

        if ($isSuperAdmin && session('platform_context') === true && ! $request->hasHeader('X-Tenant-ID')) {
            app(TenantContext::class)->clear();
            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId(null);
            }

            return $next($request);
        }

        // 3. If explicit X-Tenant-ID header was supplied, resolve strictly without silent fallback
        if ($request->hasHeader('X-Tenant-ID')) {
            $headerTenantId = (int) $request->header('X-Tenant-ID');
            if ($isSuperAdmin) {
                $tenant = Tenant::where('id', $headerTenantId)->where('is_active', true)->first();
            } else {
                $tenant = $user->tenants()
                    ->where('tenants.id', $headerTenantId)
                    ->where('tenants.is_active', true)
                    ->wherePivot('is_active', true)
                    ->first();
            }

            if (! $tenant) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized or invalid tenant specified in X-Tenant-ID header.',
                    ], 403);
                }

                abort(403, 'Unauthorized or invalid tenant specified in X-Tenant-ID header.');
            }
        } else {
            // 4. Session -> User current_tenant_id
            $tenantId = session('active_tenant_id') ?? $user->current_tenant_id;

            if ($tenantId) {
                if ($isSuperAdmin) {
                    $tenant = Tenant::where('id', $tenantId)->where('is_active', true)->first();
                } else {
                    $tenant = $user->tenants()
                        ->where('tenants.id', $tenantId)
                        ->where('tenants.is_active', true)
                        ->wherePivot('is_active', true)
                        ->first();
                }
            }

            // 5. Fallback only if no tenant matched: User's first assigned active tenant with active membership
            if (! $tenant) {
                if ($isSuperAdmin) {
                    $tenant = Tenant::where('is_active', true)->orderBy('id')->first();
                } else {
                    $tenant = $user->tenants()
                        ->where('tenants.is_active', true)
                        ->wherePivot('is_active', true)
                        ->first();
                }

                if ($tenant) {
                    session(['active_tenant_id' => $tenant->id]);
                }
            }
        }

        // 6. Set TenantContext and Spatie team ID
        if ($tenant) {
            app(TenantContext::class)->setTenant($tenant);

            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($tenant->id);
            }
        } elseif (! $isSuperAdmin) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is not associated with any active business.',
                ], 403);
            }

            abort(403, 'Your account is not associated with any active business.');
        }

        return $next($request);
    }
}
