<?php

namespace App\Services;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantUserInvitation;
use App\Models\User;
use App\Notifications\TenantUserInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TenantMembershipService
{
    /**
     * Directly provision a Tenant Admin during Super Admin business onboarding.
     * Works for both brand new user accounts and existing accounts, ensuring
     * immediate active membership without requiring unreceivable email invitations.
     */
    public function provisionAdmin(Tenant $tenant, array $data): array
    {
        $email = mb_strtolower(trim($data['email']));
        $username = mb_strtolower(trim($data['username']));
        $existingUser = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($existingUser && $existingUser->isSuperAdmin()) {
            throw ValidationException::withMessages(['admin_email' => 'Platform administrators cannot be assigned as business tenant admins.']);
        }

        return DB::transaction(function () use ($tenant, $data, $email, $username, $existingUser) {
            if ($existingUser) {
                if (! empty($data['password'])) {
                    $existingUser->update(['password' => Hash::make($data['password'])]);
                }
                if (! empty($data['name'])) {
                    $existingUser->update(['name' => trim($data['name'])]);
                }

                DB::table('tenant_user')->updateOrInsert(
                    ['tenant_id' => $tenant->id, 'user_id' => $existingUser->id],
                    [
                        'username' => $username,
                        'role' => 'admin',
                        'is_active' => true,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                $existingUser->updateQuietly(['current_tenant_id' => $tenant->id]);
                $this->syncRole($existingUser, $tenant->id, 'admin');

                TenantUserInvitation::where('tenant_id', $tenant->id)
                    ->where('user_id', $existingUser->id)
                    ->delete();

                return ['status' => 'attached', 'user' => $existingUser];
            }

            $user = User::create([
                'name' => trim($data['name']),
                'email' => $email,
                'password' => Hash::make($data['password']),
                'is_active' => true,
                'is_super_admin' => false,
                'current_tenant_id' => $tenant->id,
            ]);

            DB::table('tenant_user')->insert([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'username' => $username,
                'role' => 'admin',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->syncRole($user, $tenant->id, 'admin');

            return ['status' => 'created', 'user' => $user];
        });
    }

    /**
     * Scope an email address to a specific tenant slug to ensure global database uniqueness
     * while allowing different tenants to register users with identical raw emails (e.g. arun.kongu@gmail.com).
     */
    public function scopeEmailToTenant(string $email, Tenant $tenant): string
    {
        $email = mb_strtolower(trim($email));
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }

        $slug = Str::slug($tenant->slug ?: $tenant->name);
        if ($slug === '') {
            return $email;
        }

        if (str_ends_with($parts[0], '.' . $slug) || str_ends_with($parts[0], '+' . $slug)) {
            return $email;
        }

        return "{$parts[0]}.{$slug}@{$parts[1]}";
    }

    public function createOrInvite(Tenant $tenant, array $data, User $inviter): array
    {
        $rawEmail = mb_strtolower(trim($data['email']));
        $scopedEmail = $this->scopeEmailToTenant($rawEmail, $tenant);
        $username = mb_strtolower(trim($data['username']));
        $existingUser = User::whereRaw('LOWER(email) = ?', [$scopedEmail])->first();

        if ($existingUser) {
            if ($existingUser->isSuperAdmin()) {
                throw ValidationException::withMessages(['email' => 'Platform administrators cannot be added as business members.']);
            }
            return $this->inviteExistingUser($tenant, $existingUser, $username, $data['role'], $inviter);
        }

        $user = DB::transaction(function () use ($tenant, $data, $scopedEmail, $username) {
            $user = User::create([
                'name' => trim($data['name']),
                'email' => $scopedEmail,
                'password' => Hash::make($data['password']),
                'is_active' => true,
                'is_super_admin' => false,
                'current_tenant_id' => $tenant->id,
            ]);

            DB::table('tenant_user')->insert([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'username' => $username,
                'role' => $data['role'],
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->syncRole($user, $tenant->id, $data['role']);

            return $user;
        });

        return ['status' => 'created', 'user' => $user];
    }

    public function inviteExistingUser(Tenant $tenant, User $user, string $username, string $role, User $inviter): array
    {
        TenantUserInvitation::expireStale($tenant->id, $user->id);

        if (DB::table('tenant_user')->where('tenant_id', $tenant->id)->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages(['email' => 'This account already belongs to this business.']);
        }

        $usernameConflict = TenantUserInvitation::query()
            ->where('tenant_id', $tenant->id)
            ->whereRaw('LOWER(username) = ?', [$username])
            ->where('user_id', '!=', $user->id)
            ->where('status', 'pending')
            ->where('expires_at', '>', now())
            ->exists();
        if ($usernameConflict) {
            throw ValidationException::withMessages(['username' => 'This username is reserved by another pending invitation.']);
        }

        [$invitation, $plainToken] = DB::transaction(function () use ($tenant, $user, $username, $role, $inviter) {
            $plainToken = Str::random(64);
            $invitation = TenantUserInvitation::query()
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->where('status', 'pending')
                ->latest('id')
                ->first();

            $attributes = [
                    'user_id' => $user->id,
                    'email' => mb_strtolower($user->email),
                    'username' => $username,
                    'role' => $role,
                    'token_hash' => hash('sha256', $plainToken),
                    'invited_by' => $inviter->id,
                    'status' => 'pending',
                    'expires_at' => now()->addHours(48),
                    'accepted_at' => null,
                    'cancelled_at' => null,
            ];

            if ($invitation) {
                $invitation->update($attributes);
            } else {
                $invitation = TenantUserInvitation::create(['tenant_id' => $tenant->id] + $attributes);
            }

            return [$invitation, $plainToken];
        });

        DB::afterCommit(function () use ($user, $invitation, $plainToken) {
            $user->notify(new TenantUserInvitationNotification($invitation->load('tenant'), $plainToken));
        });

        return ['status' => 'invited', 'user' => $user, 'invitation' => $invitation];
    }

    public function updateMembership(Tenant $tenant, User $user, array $data): void
    {
        DB::transaction(function () use ($tenant, $user, $data) {
            $updates = [
                'username' => mb_strtolower(trim($data['username'])),
                'role' => $data['role'],
                'updated_at' => now(),
            ];
            if (array_key_exists('is_active', $data)) {
                $updates['is_active'] = (bool) $data['is_active'];
            }

            DB::table('tenant_user')
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->update($updates);

            if (! empty($data['password'])) {
                $user->update(['password' => Hash::make($data['password'])]);
            }

            if (! empty($data['name'])) {
                $user->update(['name' => trim($data['name'])]);
            }

            $this->syncRole($user, $tenant->id, $data['role']);
        });
    }

    public function toggleMembership(Tenant $tenant, User $user): bool
    {
        $membership = DB::table('tenant_user')
            ->where('tenant_id', $tenant->id)
            ->where('user_id', $user->id)
            ->first();
        abort_unless($membership, 404);

        $active = ! (bool) $membership->is_active;
        DB::table('tenant_user')->where('id', $membership->id)->update([
            'is_active' => $active,
            'updated_at' => now(),
        ]);

        return $active;
    }

    public function removeMembership(Tenant $tenant, User $user): void
    {
        DB::transaction(function () use ($tenant, $user) {
            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($tenant->id);
            }
            $user->syncRoles([]);

            DB::table('tenant_user')
                ->where('tenant_id', $tenant->id)
                ->where('user_id', $user->id)
                ->delete();

            if ((int) $user->current_tenant_id === (int) $tenant->id) {
                $nextTenantId = DB::table('tenant_user')
                    ->where('user_id', $user->id)
                    ->where('is_active', true)
                    ->orderBy('tenant_id')
                    ->value('tenant_id');
                $user->updateQuietly(['current_tenant_id' => $nextTenantId]);
            }
        });
    }

    public function acceptInvitation(TenantUserInvitation $invitation, User $user): Tenant
    {
        if ($user->isSuperAdmin()) {
            throw ValidationException::withMessages(['invitation' => 'Platform administrators cannot accept business memberships.']);
        }
        if (! hash_equals(mb_strtolower($invitation->email), mb_strtolower($user->email))) {
            throw ValidationException::withMessages(['invitation' => 'This invitation belongs to a different account.']);
        }
        if (! $invitation->isPending() || ! $invitation->tenant?->is_active) {
            throw ValidationException::withMessages(['invitation' => 'This invitation is no longer valid.']);
        }

        DB::transaction(function () use ($invitation, $user) {
            $duplicate = DB::table('tenant_user')
                ->where('tenant_id', $invitation->tenant_id)
                ->where(function ($query) use ($invitation, $user) {
                    $query->where('user_id', $user->id)
                        ->orWhereRaw('LOWER(username) = ?', [mb_strtolower($invitation->username)]);
                })
                ->exists();
            if ($duplicate) {
                throw ValidationException::withMessages(['invitation' => 'The membership or username is no longer available.']);
            }

            DB::table('tenant_user')->insert([
                'tenant_id' => $invitation->tenant_id,
                'user_id' => $user->id,
                'username' => $invitation->username,
                'role' => $invitation->role,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->syncRole($user, $invitation->tenant_id, $invitation->role);
            $invitation->update(['status' => 'accepted', 'accepted_at' => now()]);
            $user->updateQuietly(['current_tenant_id' => $invitation->tenant_id]);
        });

        return $invitation->tenant;
    }

    public function syncRole(User $user, int $tenantId, string $roleName): void
    {
        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($tenantId);
        }

        $teamColumn = config('permission.column_names.team_foreign_key', 'team_id');
        $role = Role::query()
            ->where('guard_name', 'web')
            ->where('name', $roleName)
            ->where(fn ($roles) => $roles->where($teamColumn, $tenantId)->orWhereNull($teamColumn))
            ->orderByRaw("CASE WHEN {$teamColumn} = ? THEN 0 ELSE 1 END", [$tenantId])
            ->first();

        if (! $role) {
            $role = Role::firstOrCreate([
                'name'       => $roleName,
                'guard_name' => 'web',
                $teamColumn  => $tenantId,
            ]);

            if ($roleName === 'admin') {
                $allPermissions = \Spatie\Permission\Models\Permission::all();
                if ($allPermissions->isNotEmpty()) {
                    $role->syncPermissions($allPermissions);
                }
            }
        }

        $user->syncRoles([$role]);
    }
}
