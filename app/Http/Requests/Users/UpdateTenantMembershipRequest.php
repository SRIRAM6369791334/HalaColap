<?php

namespace App\Http\Requests\Users;

use App\Models\Role;
use App\Rules\UniqueTenantUsername;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTenantMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');
        if ($target instanceof \App\Models\User) {
            if ($target->isSuperAdmin()) {
                return false;
            }
            $tenantId = app(TenantContext::class)->getTenantId();
            if ($tenantId && ! \Illuminate\Support\Facades\DB::table('tenant_user')->where('tenant_id', $tenantId)->where('user_id', $target->id)->exists()) {
                return false;
            }
        }

        $user = $this->user();
        return $user && ($user->can('manage users') || $user->can('edit users'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => mb_strtolower(trim((string) $this->input('username'))),
            'role' => mb_strtolower(trim((string) $this->input('role'))),
        ]);
    }

    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();
        abort_unless($tenantId, 403, 'Select an active business before managing users.');
        $target = $this->route('user');
        $teamColumn = config('permission.column_names.team_foreign_key', 'team_id');

        return [
            'username' => [
                'required', 'string', 'min:3', 'max:100',
                'regex:/^[a-z0-9_.-]+$/',
                new UniqueTenantUsername($tenantId, $target?->id),
            ],
            'role' => [
                'required', 'string',
                Rule::notIn(['super admin', 'super_admin']),
                function ($attribute, $value, $fail) use ($tenantId, $teamColumn) {
                    $normalized = strtolower(trim((string) $value));
                    $standardRoles = ['admin', 'staff', 'manager', 'accountant', 'delivery_staff', 'data_entry'];
                    if (in_array($normalized, $standardRoles, true)) {
                        return;
                    }
                    $exists = Role::query()
                        ->where('guard_name', 'web')
                        ->where('name', $value)
                        ->where(fn ($roles) => $roles->whereNull($teamColumn)->orWhere($teamColumn, $tenantId)->orWhere($teamColumn, 1))
                        ->exists();
                    if (! $exists) {
                        $fail('The selected role is invalid for this business.');
                    }
                },
            ],
            'password' => ['nullable', 'string', 'min:6'],
            'name' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
