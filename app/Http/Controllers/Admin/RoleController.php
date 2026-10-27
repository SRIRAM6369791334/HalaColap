<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\PermissionGroup;
use App\Services\ActivityLogger;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    private function flushPermissionCache(): void
    {
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function authorizeRoleAccess(Role $role): void
    {
        $user = auth()->user();
        if ($user && $user->isSuperAdmin()) {
            return;
        }

        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId();
        if ($tenantId && $role->team_id !== null && (int) $role->team_id !== (int) $tenantId) {
            abort(403, 'Unauthorized cross-tenant role access.');
        }
    }

    public function index()
    {
        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId();
        $query = Role::withCount(['permissions', 'users'])->oldest();

        if ($tenantId && ! (auth()->user()?->isSuperAdmin() && ! session()->has('active_tenant_id'))) {
            $query->where(function ($q) use ($tenantId) {
                $q->where('team_id', $tenantId)->orWhereNull('team_id');
            });
        }

        $roles = $query->get();
        return view('admin.roles.index', compact('roles'));
    }

    public function create()
    {
        return view('admin.roles.create', [
            'role' => new Role()
        ]);
    }

    public function store(Request $request)
    {
        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId();

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->where(function ($q) use ($tenantId) {
                    return $tenantId ? $q->where('team_id', $tenantId) : $q->whereNull('team_id');
                }),
            ],
        ]);

        if ($tenantId && function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($tenantId);
        }

        $role = Role::create([
            'name'       => $request->name,
            'guard_name' => 'web',
            'team_id'    => $tenantId,
        ]);

        ActivityLogger::log('Created Role', 'RoleManagement', $role->id);
        $this->flushPermissionCache();

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully!');
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);
        $this->authorizeRoleAccess($role);

        return view('admin.roles.edit', compact('role'));
    }

    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);
        $this->authorizeRoleAccess($role);

        $tenantId = $role->team_id ?? app(\App\Services\Tenancy\TenantContext::class)->getTenantId();

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'name')->ignore($id)->where(function ($q) use ($tenantId) {
                    return $tenantId ? $q->where('team_id', $tenantId) : $q->whereNull('team_id');
                }),
            ],
        ]);

        $role->update([
            'name' => $request->name,
        ]);

        ActivityLogger::log('Updated Role', 'RoleManagement', $role->id);
        $this->flushPermissionCache();

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully');
    }

    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $this->authorizeRoleAccess($role);
        
        // Anti-Lockout Strategy
        if ($role->name === 'admin' || $role->name === 'Super Admin') {
            abort(403, 'SYSTEM: Admin role cannot be deleted.');
        }
        
        $role->delete();

        ActivityLogger::log('Deleted Role', 'RoleManagement', $role->id);
        $this->flushPermissionCache();

        return redirect()->route('admin.roles.index')->with('success', 'Role deleted!');
    }

    public function assignPermissionPage($id)
    {
        $role = Role::findOrFail($id);
        $this->authorizeRoleAccess($role);

        $permissionGroups = PermissionGroup::with('permissions')->orderBy('name')->get();

        return view('admin.roles.assignpermission', compact('role', 'permissionGroups'));
    }

    public function assignPermission(Request $request)
    {
        $request->validate([
            'role_id'       => 'required|exists:roles,id',
            'permissions'   => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $role = Role::findOrFail($request->role_id);
        $this->authorizeRoleAccess($role);
        
        // Don't allow changing permissions for Super Admin
        if ($role->name === 'admin' || $role->name === 'Super Admin') {
            return redirect()->route('admin.roles.index')->with('error', 'Admin permissions cannot be modified.');
        }

        $permissions = Permission::whereIn('id', $request->permissions ?? [])->pluck('name')->toArray();
        $role->syncPermissions($permissions);

        ActivityLogger::log('Assigned Permissions to Role', 'RoleManagement', $role->id);
        $this->flushPermissionCache();

        return redirect()->route('admin.roles.index')->with('success', 'Permissions assigned successfully!');
    }
}
