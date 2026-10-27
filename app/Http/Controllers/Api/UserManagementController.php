<?php

namespace App\Http\Controllers\Api;

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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserManagementController extends BaseApiController
{
    public function __construct(private TenantMembershipService $memberships) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = $this->activeTenant();
        TenantUserInvitation::expireStale($tenant->id);
        $users = $tenant->users()->orderBy('name')->paginate(15);
        $teamColumn = config('permission.column_names.team_foreign_key', 'team_id');
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->where(fn ($query) => $query->whereNull($teamColumn)->orWhere($teamColumn, $tenant->id))
            ->whereRaw('LOWER(name) NOT IN (?, ?)', ['super admin', 'super_admin'])
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Business memberships retrieved successfully',
            'data' => [
                'users' => collect($users->items())->map(fn (User $user) => $this->membershipPayload($user)),
                'roles' => $roles,
                'invitations' => TenantUserInvitation::where('tenant_id', $tenant->id)->where('status', 'pending')->latest()->get(),
                'activity_logs' => ActivityLog::with('user')->latest('timestamp')->take(15)->get(),
            ],
            'pagination' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function identityStatus(Request $request): JsonResponse
    {
        $tenant = $this->activeTenant();
        $validated = $request->validate(['email' => ['required', 'email:rfc', 'max:255']]);
        $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower(trim($validated['email']))])->first();

        return $this->sendResponse([
            'existing_account' => (bool) $user,
            'already_member' => $user
                ? DB::table('tenant_user')->where('tenant_id', $tenant->id)->where('user_id', $user->id)->exists()
                : false,
        ], 'Identity status retrieved');
    }

    public function store(StoreTenantUserRequest $request): JsonResponse
    {
        $tenant = $this->activeTenant();
        $result = $this->memberships->createOrInvite($tenant, $request->validated(), $request->user());
        ActivityLogger::log($result['status'] === 'created' ? 'Created Business User' : 'Invited Existing User', 'UserManagement', $result['user']->id, $tenant->id);

        if ($result['status'] === 'invited') {
            return $this->sendResponse([
                'status' => 'invited',
                'invitation_id' => $result['invitation']->id,
            ], 'Invitation sent; access starts after acceptance', 202);
        }

        $user = $tenant->users()->where('users.id', $result['user']->id)->firstOrFail();
        return $this->sendResponse($this->membershipPayload($user), 'User created successfully', 201);
    }

    public function update(UpdateTenantMembershipRequest $request, User $user): JsonResponse
    {
        $tenant = $this->authorizeMembership($user);
        if ($user->id === Auth::id() && $request->has('is_active') && ! $request->boolean('is_active')) {
            return $this->sendError('You cannot deactivate your own membership.', [], 422);
        }

        $this->memberships->updateMembership($tenant, $user, $request->validated());
        ActivityLogger::log('Updated Business Membership', 'UserManagement', $user->id, $tenant->id);
        $fresh = $tenant->users()->where('users.id', $user->id)->firstOrFail();

        return $this->sendResponse($this->membershipPayload($fresh), 'Business membership updated successfully');
    }

    public function toggleStatus(User $user): JsonResponse
    {
        $tenant = $this->authorizeMembership($user);
        if ($user->id === Auth::id()) {
            return $this->sendError('You cannot deactivate your own membership.', [], 422);
        }

        $active = $this->memberships->toggleMembership($tenant, $user);
        ActivityLogger::log($active ? 'Activated Business Membership' : 'Deactivated Business Membership', 'UserManagement', $user->id, $tenant->id);

        return $this->sendResponse(['is_active' => $active], 'Membership status updated');
    }

    public function destroy(User $user): JsonResponse
    {
        $tenant = $this->authorizeMembership($user);
        if ($user->id === Auth::id()) {
            return $this->sendError('You cannot remove your own membership.', [], 422);
        }
        if ($user->isSuperAdmin()) {
            return $this->sendError('Platform Super Admin membership is protected.', [], 403);
        }

        $this->memberships->removeMembership($tenant, $user);
        ActivityLogger::log('Removed User From Business', 'UserManagement', $user->id, $tenant->id);

        return $this->sendResponse([], 'User access removed from this business');
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

        abort_unless(
            DB::table('tenant_user')->where('tenant_id', $tenant->id)->where('user_id', $target->id)->exists(),
            403,
            'Cannot access a membership from another business.',
        );

        return $tenant;
    }

    private function membershipPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->pivot?->username,
            'role' => $user->pivot?->role,
            'is_active' => (bool) $user->pivot?->is_active,
        ];
    }
}
