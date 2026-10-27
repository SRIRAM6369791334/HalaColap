<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Services\ActivityLogger;

class PermissionController extends Controller
{
    private function flushPermissionCache(): void
    {
        app()->make(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }


    public function index()
    {
        $permissions = Permission::with('permissionGroup')->orderBy('permission_group_id')->orderBy('name')->paginate(50);
        $groups = PermissionGroup::pluck('name', 'id');
        $allGroups = PermissionGroup::withCount('permissions')->orderBy('name')->get();
        return view('admin.permissions.index', compact('permissions', 'groups', 'allGroups'));
    }

    public function create()
    {
        $permissionGroups = PermissionGroup::all();
        return view('admin.permissions.create', compact('permissionGroups'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name',
            'permission_group_id' => 'required|exists:permission_groups,id',
        ]);

        $permission = Permission::create([
            'name' => $request->name,
            'guard_name' => 'web',
            'permission_group_id' => $request->permission_group_id,
        ]);

        ActivityLogger::log('Created Permission', 'PermissionManagement', $permission->id);
        $this->flushPermissionCache();

        return redirect()->route('admin.permissions.index')->with('success', 'Permission created!');
    }

    public function edit($id)
    {
        $permission = Permission::findOrFail($id);
        $permissionGroups = PermissionGroup::all();
        return view('admin.permissions.edit', compact('permission', 'permissionGroups'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $id,
            'permission_group_id' => 'required|exists:permission_groups,id',
        ]);

        $permission = Permission::findOrFail($id);
        $permission->update([
            'name' => $request->name,
            'permission_group_id' => $request->permission_group_id,
        ]);

        ActivityLogger::log('Updated Permission', 'PermissionManagement', $permission->id);
        $this->flushPermissionCache();

        return redirect()->route('admin.permissions.index')->with('success', 'Permission updated successfully');
    }

    public function destroy($id)
    {
        $permission = Permission::findOrFail($id);
        $permission->delete();

        ActivityLogger::log('Deleted Permission', 'PermissionManagement', $id);
        $this->flushPermissionCache();

        return redirect()->route('admin.permissions.index')->with('success', 'Permission deleted successfully');
    }
}
