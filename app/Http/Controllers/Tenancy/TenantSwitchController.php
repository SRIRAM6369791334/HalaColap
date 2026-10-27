<?php

namespace App\Http\Controllers\Tenancy;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantSwitchController extends Controller
{
    /**
     * Switch the active tenant context for the current user session.
     */
    public function switch(Request $request): RedirectResponse
    {
        $request->validate([
            'tenant_id' => 'required|integer|exists:tenants,id',
        ]);

        $user = Auth::user();
        if (!$user) {
            return redirect()->route('login');
        }

        $tenantId = (int) $request->input('tenant_id');

        // Super Admin can switch to any active tenant
        if ($user->isSuperAdmin()) {
            $tenant = Tenant::where('id', $tenantId)->where('is_active', true)->first();
        } else {
            // Regular user can only switch to tenants they are explicitly attached to AND pivot membership is active
            $tenant = $user->tenants()
                ->where('tenants.id', $tenantId)
                ->where('tenants.is_active', true)
                ->wherePivot('is_active', true)
                ->first();
        }

        if (!$tenant) {
            return back()->with('error', 'You do not have access to this business account or it is deactivated.');
        }

        // Store active tenant in session and update user's default current_tenant_id
        session(['active_tenant_id' => $tenant->id, 'platform_context' => false]);
        $user->updateQuietly(['current_tenant_id' => $tenant->id]);

        // Bind in current request context
        app(TenantContext::class)->setTenant($tenant);

        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($tenant->id);
        }

        return back()->with('success', "Switched business to: {$tenant->name}");
    }
}
