<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\ActivityLogger;
use App\Services\TenantIdentityService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(LoginRequest $request, TenantIdentityService $identity): RedirectResponse
    {
        $resolved = $identity->resolveForCredentials(
            $request->login,
            $request->password,
            $request->input('business_code'),
        );
        $user = $resolved['user'] ?? null;

        if (! $user) {
            return back()
                ->withInput($request->only('login'))
                ->withErrors(['login' => 'These credentials do not match our records.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $tenant = $resolved['tenant'];
        if ($tenant) {
            $request->session()->put(['active_tenant_id' => $tenant->id, 'platform_context' => false]);
            $user->updateQuietly(['current_tenant_id' => $tenant->id]);
            app(TenantContext::class)->setTenant($tenant);
            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($tenant->id);
            }
            ActivityLogger::log('Login', 'Auth', $user->id, $tenant->id);
        } else {
            $request->session()->forget('active_tenant_id');
            $request->session()->put('platform_context', true);
            app(TenantContext::class)->clear();
            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId(null);
            }
            ActivityLogger::logPlatform('Platform Login', 'Auth', $user->id);
        }

        return $tenant
            ? redirect()->intended(route('dashboard'))
            : redirect()->route('dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        $tenantId = app(TenantContext::class)->getTenantId();
        $tenantId
            ? ActivityLogger::log('Logout', 'Auth', Auth::id(), $tenantId)
            : ActivityLogger::logPlatform('Platform Logout', 'Auth', Auth::id());

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
