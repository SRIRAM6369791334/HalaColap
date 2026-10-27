<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TenantIdentityService
{
    /**
     * Resolve a normal login without requiring the user to know a business code.
     *
     * Email identifies the global account and opens its preferred active
     * membership. A username is resolved from active tenant memberships and
     * the password is used to safely disambiguate usernames shared by tenants.
     */
    public function resolveForCredentials(string $login, string $password, ?string $businessCode = null): ?array
    {
        $login = mb_strtolower(trim($login));
        $businessCode = mb_strtolower(trim((string) $businessCode));

        if ($businessCode !== '') {
            $resolved = $this->resolve($businessCode, $login);

            return $resolved && $this->passwordMatches($password, $resolved['user']->password)
                ? $resolved
                : null;
        }

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $user = User::query()
                ->whereRaw('LOWER(email) = ?', [$login])
                ->where('is_active', true)
                ->first();

            if (! $user || ! $this->passwordMatches($password, $user->password)) {
                $user = null;
                $parts = explode('@', $login);
                if (count($parts) === 2) {
                    $local = str_replace(['%', '_'], ['\\%', '\\_'], $parts[0]);
                    $domain = str_replace(['%', '_'], ['\\%', '\\_'], $parts[1]);
                    $candidates = User::query()
                        ->whereRaw('LOWER(email) LIKE ?', ["{$local}.%@{$domain}"])
                        ->where('is_active', true)
                        ->get()
                        ->filter(fn (User $u) => $this->passwordMatches($password, $u->password));

                    if ($candidates->count() === 1) {
                        $user = $candidates->first();
                    }
                }
            }

            if (! $user) {
                return null;
            }

            if ($user->isSuperAdmin()) {
                return ['user' => $user, 'tenant' => null, 'membership' => null];
            }

            $tenants = $user->tenants()
                ->where('tenants.is_active', true)
                ->wherePivot('is_active', true)
                ->orderBy('tenants.id')
                ->get();
            $tenant = $tenants->first(fn (Tenant $candidate) => (int) $candidate->id === (int) $user->current_tenant_id)
                ?? $tenants->first();

            return $tenant
                ? ['user' => $user, 'tenant' => $tenant, 'membership' => $tenant->pivot]
                : null;
        }

        $matches = DB::table('tenant_user')
            ->join('users', 'users.id', '=', 'tenant_user.user_id')
            ->join('tenants', 'tenants.id', '=', 'tenant_user.tenant_id')
            ->whereRaw('LOWER(tenant_user.username) = ?', [$login])
            ->where('tenant_user.is_active', true)
            ->where('users.is_active', true)
            ->where('tenants.is_active', true)
            ->whereNull('tenants.deleted_at')
            ->select(
                'tenant_user.*',
                'users.password as user_password',
                'users.current_tenant_id as preferred_tenant_id',
            )
            ->orderBy('tenant_user.tenant_id')
            ->get()
            ->filter(fn ($membership) => $this->passwordMatches($password, $membership->user_password))
            ->values();

        if ($matches->isEmpty() || $matches->pluck('user_id')->unique()->count() !== 1) {
            return null;
        }

        $preferredTenantId = (int) $matches->first()->preferred_tenant_id;
        $membership = $matches->first(fn ($candidate) => (int) $candidate->tenant_id === $preferredTenantId)
            ?? $matches->first();
        $user = User::query()->whereKey($membership->user_id)->where('is_active', true)->first();
        $tenant = Tenant::query()->whereKey($membership->tenant_id)->where('is_active', true)->first();

        return $user && $tenant
            ? ['user' => $user, 'tenant' => $tenant, 'membership' => $membership]
            : null;
    }

    public function resolve(string $businessCode, string $login): ?array
    {
        $businessCode = mb_strtolower(trim($businessCode));
        $login = mb_strtolower(trim($login));

        if ($businessCode === 'platform') {
            if (! filter_var($login, FILTER_VALIDATE_EMAIL)) {
                return null;
            }

            $user = User::whereRaw('LOWER(email) = ?', [$login])->first();
            if (! $user?->isSuperAdmin() || ! $user->is_active) {
                return null;
            }

            return ['user' => $user, 'tenant' => null, 'membership' => null];
        }

        $slugAttempt = \Illuminate\Support\Str::slug($businessCode);
        $tenant = Tenant::query()
            ->where(function ($q) use ($businessCode, $slugAttempt) {
                $q->whereRaw('LOWER(slug) = ?', [$businessCode])
                  ->orWhereRaw('LOWER(name) = ?', [$businessCode]);
                if ($slugAttempt !== '') {
                    $q->orWhereRaw('LOWER(slug) = ?', [$slugAttempt]);
                }
            })
            ->where('is_active', true)
            ->first();

        if (! $tenant) {
            return null;
        }

        $query = $tenant->users()
            ->where('users.is_active', true)
            ->wherePivot('is_active', true);

        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $scopedEmail = app(TenantMembershipService::class)->scopeEmailToTenant($login, $tenant);
            $query->where(function ($q) use ($login, $scopedEmail) {
                $q->whereRaw('LOWER(users.email) = ?', [$login])
                  ->orWhereRaw('LOWER(users.email) = ?', [$scopedEmail]);
            });
        } else {
            $query->whereRaw('LOWER(tenant_user.username) = ?', [$login]);
        }

        $user = $query->first();
        if (! $user) {
            return null;
        }

        return ['user' => $user, 'tenant' => $tenant, 'membership' => $user->pivot];
    }

    private function passwordMatches(string $plainPassword, string $passwordHash): bool
    {
        try {
            return Hash::check($plainPassword, $passwordHash);
        } catch (\Throwable) {
            return false;
        }
    }
}
