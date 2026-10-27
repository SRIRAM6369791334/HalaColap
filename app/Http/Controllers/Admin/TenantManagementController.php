<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Reseller;
use App\Services\ActivityLogger;
use App\Services\TenantMembershipService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Models\Role;
use Spatie\Permission\Models\Permission;

class TenantManagementController extends Controller
{
    /**
     * Ensure only Platform Super Admin can access SaaS tenant management.
     */
    public function __construct(private TenantMembershipService $memberships) {}

    private function authorizeSuperAdmin(): void
    {
        if (! Auth::user()?->isSuperAdmin()) {
            abort(403, 'Unauthorized. Only Platform Super Admin can manage SaaS business tenants.');
        }
    }

    /**
     * List all business tenants.
     */
    public function index(Request $request): View
    {
        $this->authorizeSuperAdmin();

        $query = Tenant::with(['reseller', 'users'])->withCount(['dealers', 'users']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $tenants = $query->orderBy('id', 'desc')->paginate(15);
        $resellers = Reseller::where('is_active', true)->orderBy('company_name')->get();

        return view('admin.tenants.index', compact('tenants', 'resellers'));
    }

    /**
     * Show form to create a new business tenant and its initial Tenant Admin.
     */
    public function create(): View
    {
        $this->authorizeSuperAdmin();

        $resellers = Reseller::where('is_active', true)->orderBy('company_name')->get();

        return view('admin.tenants.create', compact('resellers'));
    }

    /**
     * Onboard a new business tenant and create its Tenant Admin account.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $request->merge([
            'admin_username' => mb_strtolower(trim((string) $request->input('admin_username'))),
            'admin_email'    => mb_strtolower(trim((string) $request->input('admin_email'))),
            'email'          => $request->filled('email') ? mb_strtolower(trim((string) $request->input('email'))) : null,
            'phone'          => $request->filled('phone') ? trim((string) $request->input('phone')) : null,
        ]);
        $request->validate([
            // Business Details
            'name'         => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'slug'         => 'nullable|string|max:100|unique:tenants,slug',
            'email'        => 'required|email:rfc|max:255|unique:tenants,email',
            'phone'        => 'required|string|min:7|max:30|unique:tenants,phone',
            'address'      => 'nullable|string',
            'gst_number'   => 'nullable|string|max:50',
            'currency'     => 'nullable|string|max:10',
            'reseller_id'  => 'nullable|exists:resellers,id',

            // Initial Tenant Admin Credentials
            'admin_name'     => 'required|string|max:255',
            'admin_username' => ['required', 'string', 'min:3', 'max:100', 'regex:/^[a-z0-9_.-]+$/'],
            'admin_email'    => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'admin_password' => 'required|string|min:8|confirmed',
        ], [
            'email.required'          => 'Official Business Email is required.',
            'email.unique'            => 'This business email is already registered to another tenant.',
            'phone.required'          => 'Official Business Phone number is required.',
            'phone.unique'            => 'This business phone number is already registered to another tenant.',
            'admin_email.required'    => 'Administrator email is required.',
            'admin_email.unique'      => 'This administrator email is already registered to another account. Each business tenant must have a unique administrator email.',
            'admin_password.required' => 'A password (minimum 8 characters) is required for the administrator account.',
        ]);

        return DB::transaction(function () use ($request) {
            $slug = $request->input('slug') ? Str::slug($request->input('slug')) : Str::slug($request->input('name'));
            
            // Ensure unique slug
            $originalSlug = $slug;
            $counter = 1;
            while (Tenant::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }

            // 1. Create Tenant
            $tenant = Tenant::create([
                'name'         => $request->input('name'),
                'slug'         => $slug,
                'company_name' => $request->input('company_name') ?: $request->input('name'),
                'email'        => $request->input('email') ?: $request->input('admin_email'),
                'phone'        => $request->input('phone'),
                'address'      => $request->input('address'),
                'gst_number'   => $request->input('gst_number'),
                'currency'     => $request->input('currency', 'INR'),
                'timezone'     => 'Asia/Kolkata',
                'date_format'  => 'd-m-Y',
                'is_active'    => true,
                'reseller_id'  => $request->input('reseller_id'),
            ]);

            // 2. Ensure the tenant admin role is available before creating membership/invitation.
            if (config('permission.teams', false)) {
                setPermissionsTeamId($tenant->id);
            }
            $teamColumn = config('permission.column_names.team_foreign_key', 'team_id');
            $adminRole = Role::firstOrCreate([
                'name'       => 'admin',
                'guard_name' => 'web',
                $teamColumn  => $tenant->id,
            ]);
            $allPermissions = Permission::all();
            if ($allPermissions->isNotEmpty()) {
                $adminRole->syncPermissions($allPermissions);
            }

            $result = $this->memberships->provisionAdmin($tenant, [
                'name' => $request->input('admin_name'),
                'username' => $request->input('admin_username'),
                'email' => $request->input('admin_email'),
                'password' => $request->input('admin_password'),
                'role' => 'admin',
            ]);

            $adminUser = $result['user'];
            ActivityLogger::logPlatform('Created Business Tenant: '.$tenant->name, 'TenantManagement', $tenant->id);

            return redirect()->route('admin.tenants.index')->with('success', "Business Tenant '{$tenant->name}' created successfully with Admin: {$adminUser->email} (Username: {$request->input('admin_username')}).");
        });
    }

    /**
     * Show form to edit an existing business tenant.
     */
    public function edit(Tenant $tenant): View
    {
        $this->authorizeSuperAdmin();

        $tenant->load(['reseller', 'users']);
        $adminUser = $tenant->users()->wherePivot('role', 'admin')->first()
            ?? $tenant->users()->first();
        $resellers = Reseller::where('is_active', true)->orderBy('company_name')->get();

        return view('admin.tenants.edit', compact('tenant', 'resellers', 'adminUser'));
    }

    /**
     * Update business tenant details.
     */
    public function update(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        $request->merge([
            'email' => $request->filled('email') ? mb_strtolower(trim((string) $request->input('email'))) : null,
            'phone' => $request->filled('phone') ? trim((string) $request->input('phone')) : null,
        ]);

        $request->validate([
            'name'           => 'required|string|max:255',
            'company_name'   => 'nullable|string|max:255',
            'email'          => ['required', 'email:rfc', 'max:255', Rule::unique('tenants', 'email')->ignore($tenant->id)],
            'phone'          => ['required', 'string', 'min:7', 'max:30', Rule::unique('tenants', 'phone')->ignore($tenant->id)],
            'address'        => 'nullable|string',
            'gst_number'     => 'nullable|string|max:50',
            'currency'       => 'nullable|string|max:10',
            'reseller_id'    => 'nullable|exists:resellers,id',
            'is_active'      => 'boolean',
            'admin_user_id'  => 'nullable|exists:users,id',
            'admin_username' => ['nullable', 'string', 'min:3', 'max:100', 'regex:/^[a-z0-9_.-]+$/'],
            'admin_password' => 'nullable|string|min:8|confirmed',
        ], [
            'email.required' => 'Official Business Email is required.',
            'email.unique'   => 'This business email is already registered to another tenant.',
            'phone.required' => 'Official Business Phone number is required.',
            'phone.unique'   => 'This business phone number is already registered to another tenant.',
        ]);

        $tenant->update([
            'name'         => $request->input('name'),
            'company_name' => $request->input('company_name') ?: $request->input('name'),
            'email'        => $request->input('email'),
            'phone'        => $request->input('phone'),
            'address'      => $request->input('address'),
            'gst_number'   => $request->input('gst_number'),
            'currency'     => $request->input('currency', 'INR'),
            'reseller_id'  => $request->input('reseller_id'),
            'is_active'    => $request->boolean('is_active', true),
        ]);

        $passwordUpdated = false;
        $usernameUpdated = false;

        $adminUserId = $request->input('admin_user_id');
        $adminUser = null;
        if ($adminUserId) {
            $adminUser = $tenant->users()->where('users.id', $adminUserId)->first();
        }
        if (! $adminUser) {
            $adminUser = $tenant->users()->wherePivot('role', 'admin')->first() ?? $tenant->users()->first();
        }

        if ($adminUser) {
            if ($request->filled('admin_password')) {
                $adminUser->update(['password' => Hash::make($request->input('admin_password'))]);
                $passwordUpdated = true;
                ActivityLogger::logPlatform("Changed Password for Tenant Admin: {$adminUser->email} ({$tenant->name})", 'TenantManagement', $tenant->id);
            }

            if ($request->filled('admin_username')) {
                $newUsername = mb_strtolower(trim($request->input('admin_username')));
                $conflict = DB::table('tenant_user')
                    ->where('tenant_id', $tenant->id)
                    ->where('username', $newUsername)
                    ->where('user_id', '!=', $adminUser->id)
                    ->exists();

                if ($conflict) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        'admin_username' => 'This username is already taken by another user in this business.',
                    ]);
                }

                DB::table('tenant_user')
                    ->where('tenant_id', $tenant->id)
                    ->where('user_id', $adminUser->id)
                    ->update(['username' => $newUsername, 'updated_at' => now()]);
                $usernameUpdated = true;
            }
        }

        ActivityLogger::logPlatform('Updated Business Tenant: '.$tenant->name, 'TenantManagement', $tenant->id);

        $msg = "Business Tenant '{$tenant->name}' updated successfully.";
        if ($passwordUpdated) {
            $msg .= " Admin password changed successfully.";
        }
        if ($usernameUpdated && ! $passwordUpdated) {
            $msg .= " Admin username updated successfully.";
        }

        return redirect()->route('admin.tenants.edit', $tenant)->with('success', $msg);
    }

    /**
     * Display the specified business tenant.
     */
    public function show(Tenant $tenant): View
    {
        $this->authorizeSuperAdmin();

        $tenant->load(['reseller', 'users'])->loadCount(['dealers', 'users']);
        $adminUser = $tenant->users()->wherePivot('role', 'admin')->first()
            ?? $tenant->users()->first();

        return view('admin.tenants.edit', [
            'tenant' => $tenant,
            'adminUser' => $adminUser,
            'resellers' => Reseller::where('is_active', true)->orderBy('company_name')->get(),
        ]);
    }

    /**
     * Delete a business tenant.
     */
    public function destroy(Tenant $tenant): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        // Cannot delete Tenant #1 (MSR Broilers - Primary)
        if ($tenant->id === 1) {
            return back()->with('error', 'Cannot delete Primary Tenant #1 (MSR Broilers).');
        }

        $tenantName = $tenant->name;
        $tenant->delete();

        ActivityLogger::logPlatform('Deleted Business Tenant: '.$tenantName, 'TenantManagement', $tenant->id);

        return redirect()->route('admin.tenants.index')->with('success', "Business Tenant '{$tenantName}' deleted successfully.");
    }

    /**
     * Toggle active/suspended status of a business tenant.
     */
    public function toggleStatus(Tenant $tenant): RedirectResponse
    {
        $this->authorizeSuperAdmin();

        // Cannot deactivate Tenant #1 (MSR Broilers - Primary)
        if ($tenant->id === 1) {
            return back()->with('error', 'Cannot deactivate Primary Tenant #1 (MSR Broilers).');
        }

        $tenant->is_active = ! $tenant->is_active;
        $tenant->save();

        ActivityLogger::logPlatform(($tenant->is_active ? 'Activated' : 'Suspended').' Tenant: '.$tenant->name, 'TenantManagement', $tenant->id);

        return back()->with('success', "Tenant status updated to: " . ($tenant->is_active ? 'Active' : 'Suspended'));
    }
}
