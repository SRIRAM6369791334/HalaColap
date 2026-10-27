<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreTenantUserRequest;
use App\Http\Requests\Users\UpdateTenantMembershipRequest;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\TenantUserInvitation;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\TenantMembershipService;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserManagementController extends Controller
{
    public function __construct(private TenantMembershipService $memberships) {}

    public function index(): View
    {
        $tenant = app(TenantContext::class)->getTenant();
        $authUser = Auth::user();

        if ($tenant) {
            TenantUserInvitation::expireStale($tenant->id);
            $users = $tenant->users()->with('roles')->orderBy('name')->get();
            $invitations = TenantUserInvitation::with('user')
                ->where('tenant_id', $tenant->id)
                ->where('status', 'pending')
                ->latest()
                ->get();
            $activityLogs = ActivityLog::with('user')->latest('timestamp')->take(10)->get();
        } elseif ($authUser?->isSuperAdmin()) {
            $users = User::query()
                ->join('tenant_user', 'users.id', '=', 'tenant_user.user_id')
                ->join('tenants', 'tenants.id', '=', 'tenant_user.tenant_id')
                ->select('users.*', 'tenant_user.username as membership_username', 'tenant_user.role as membership_role', 'tenant_user.is_active as membership_active', 'tenants.name as tenant_name')
                ->orderBy('tenants.name')
                ->orderBy('users.name')
                ->get();
            $invitations = collect();
            $activityLogs = ActivityLog::withoutTenancy()->with('user')->latest('timestamp')->take(10)->get();
        } else {
            abort(403, 'Tenant context missing.');
        }

        $roles = $tenant ? $this->availableRoles($tenant->id)->get() : collect();

        return view('admin.users.index', compact('users', 'roles', 'activityLogs', 'invitations', 'tenant'));
    }

    public function identityStatus(Request $request): JsonResponse
    {
        $tenant = $this->activeTenant();
        $validated = $request->validate(['email' => ['required', 'email:rfc', 'max:255']]);
        $email = mb_strtolower(trim($validated['email']));
        $scopedEmail = $this->memberships->scopeEmailToTenant($email, $tenant);
        $user = User::whereRaw('LOWER(email) = ?', [$scopedEmail])->first();

        return response()->json([
            'existing_account' => (bool) $user,
            'scoped_email' => $scopedEmail,
            'already_member' => $user
                ? DB::table('tenant_user')->where('tenant_id', $tenant->id)->where('user_id', $user->id)->exists()
                : false,
        ]);
    }

    public function store(StoreTenantUserRequest $request): RedirectResponse
    {
        $tenant = $this->activeTenant();
        $result = $this->memberships->createOrInvite($tenant, $request->validated(), $request->user());

        ActivityLogger::log(
            $result['status'] === 'created' ? 'Created Business User' : 'Invited Existing User',
            'UserManagement',
            $result['user']->id,
            $tenant->id,
        );

        $message = $result['status'] === 'created'
            ? 'User created successfully.'
            : 'Invitation sent. Access will start after the account owner accepts it.';

        return back()->with('success', $message);
    }

    public function update(UpdateTenantMembershipRequest $request, User $user): RedirectResponse
    {
        $tenant = $this->authorizeMembership($user);
        if ($user->id === Auth::id() && $request->has('is_active') && ! $request->boolean('is_active')) {
            return back()->with('error', 'You cannot deactivate your own membership.');
        }

        $this->memberships->updateMembership($tenant, $user, $request->validated());
        ActivityLogger::log('Updated Business Membership', 'UserManagement', $user->id, $tenant->id);

        return back()->with('success', 'Business membership updated successfully.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $tenant = $this->authorizeMembership($user);
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot deactivate your own membership.');
        }

        $active = $this->memberships->toggleMembership($tenant, $user);
        ActivityLogger::log($active ? 'Activated Business Membership' : 'Deactivated Business Membership', 'UserManagement', $user->id, $tenant->id);

        return back()->with('success', 'Membership status updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $tenant = $this->authorizeMembership($user);
        if ($user->id === Auth::id()) {
            return back()->with('error', 'You cannot remove your own membership.');
        }
        if ($user->isSuperAdmin()) {
            abort(403, 'Platform Super Admin membership is protected.');
        }

        $this->memberships->removeMembership($tenant, $user);
        ActivityLogger::log('Removed User From Business', 'UserManagement', $user->id, $tenant->id);

        return back()->with('success', 'User access was removed from this business.');
    }

    public function activityLogs(): View
    {
        return $this->index();
    }

    private function activeTenant()
    {
        $tenant = app(TenantContext::class)->getTenant();
        abort_unless($tenant?->is_active, 403, 'Select an active business before managing users.');

        return $tenant;
    }

    private function authorizeMembership(User $target)
    {
        $tenant = $this->activeTenant();
        $authUser = Auth::user();
        abort_unless($authUser, 401);

        if ($target->isSuperAdmin()) {
            abort(403, 'Platform Super Admin memberships cannot be modified here.');
        }

        $belongs = DB::table('tenant_user')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $target->id)
            ->exists();
        abort_unless($belongs, 403, 'Cannot access a membership from another business.');

        return $tenant;
    }

    private function availableRoles(int $tenantId)
    {
        $teamColumn = config('permission.column_names.team_foreign_key', 'team_id');

        return Role::query()
            ->where('guard_name', 'web')
            ->where(fn ($roles) => $roles->whereNull($teamColumn)->orWhere($teamColumn, $tenantId))
            ->whereRaw('LOWER(name) NOT IN (?, ?)', ['super admin', 'super_admin'])
            ->orderBy('name');
    }
}
