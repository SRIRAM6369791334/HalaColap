<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\TenantIdentityService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AuthController extends BaseApiController
{
    /**
     * Handle API login.
     */
    public function login(Request $request, TenantIdentityService $identity): JsonResponse
    {
        $businessCode = Str::lower(trim((string) $request->input('business_code', '')));
        $login = Str::lower(trim((string) $request->input('login', '')));
        $request->merge(['business_code' => $businessCode, 'login' => $login]);
        $throttleKey = 'login:'.($businessCode ?: 'auto').':'.$login.':'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return $this->sendError('Too many login attempts.', [
                'retry_after_seconds' => $seconds,
            ], 429);
        }

        $validator = Validator::make($request->all(), [
            'business_code' => 'nullable|string|max:100|regex:/^[a-z0-9-]+$/',
            'login'    => 'required|string|max:255',
            'password' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors()->toArray(), 422);
        }

        $resolved = $identity->resolveForCredentials($login, (string) $request->password, $businessCode);
        $user = $resolved['user'] ?? null;

        // ✅ Use generic error message — don't reveal if email/username exists
        if (! $user) {
            RateLimiter::hit($throttleKey, 900); // 15 minutes decay
            return $this->sendError('Unauthorized', ['login' => ['Invalid credentials.']], 401);
        }

        $tenant = $resolved['tenant'];
        if ($tenant) {
            $user->updateQuietly(['current_tenant_id' => $tenant->id]);
            app(TenantContext::class)->setTenant($tenant);
            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($tenant->id);
            }
        } else {
            app(TenantContext::class)->clear();
            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId(null);
            }
        }

        // ✅ Fix 4: Named token with device info for better token management
        $deviceName = $request->input('device_name', $request->userAgent() ?? 'unknown-device');
        $tokenName  = Str::limit($deviceName, 50);
        $token = $user->createToken($tokenName)->plainTextToken;

        // Clear rate limiter on successful login
        RateLimiter::clear($throttleKey);

        // Log Activity
        if ($tenant) {
            ActivityLogger::log('API Login', 'Auth', $user->id, $tenant->id);
        } else {
            ActivityLogger::logPlatform('Platform API Login', 'Auth', $user->id);
        }

        return $this->sendResponse([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user'         => [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'username' => $resolved['membership']?->username,
                'role'     => $resolved['membership']?->role,
                'status'   => $resolved['membership'] ? 'active' : null,
                'roles'    => $user->getRoleNames(),
            ],
            'active_tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ] : null,
        ], 'Login successful');
    }

    /**
     * Handle API logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if ($user) {
            // Revoke current token
            $user->currentAccessToken()->delete();
            $tenantId = app(TenantContext::class)->getTenantId();
            $tenantId
                ? ActivityLogger::log('API Logout', 'Auth', $user->id, $tenantId)
                : ActivityLogger::logPlatform('Platform API Logout', 'Auth', $user->id);
        }

        return $this->sendResponse([], 'Logged out successfully');
    }

    /**
     * Retrieve the authenticated user's profile.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $tenant = app(TenantContext::class)->getTenant();
        $membership = $tenant
            ? $user->tenants()->where('tenants.id', $tenant->id)->first()?->pivot
            : null;
        return $this->sendResponse([
            'id'       => $user->id,
            'name'     => $user->name,
            'email'    => $user->email,
            'username' => $membership?->username,
            'role'     => $membership?->role,
            'roles'    => $user->getRoleNames(),
            'active_tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ] : null,
            'membership_status' => $membership ? ((bool) $membership->is_active ? 'active' : 'inactive') : null,
        ], 'Profile retrieved successfully');
    }
}
