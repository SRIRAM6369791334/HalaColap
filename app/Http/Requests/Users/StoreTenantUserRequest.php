<?php

namespace App\Http\Requests\Users;

use App\Models\Role;
use App\Models\User;
use App\Rules\UniqueTenantUsername;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreTenantUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && ($user->can('manage users') || $user->can('create users'));
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'username' => mb_strtolower(trim((string) $this->input('username'))),
            'email' => mb_strtolower(trim((string) $this->input('email'))),
            'role' => mb_strtolower(trim((string) $this->input('role'))),
        ]);
    }

    public function rules(): array
    {
        $tenantId = app(TenantContext::class)->getTenantId();
        abort_unless($tenantId, 403, 'Select an active business before managing users.');

        $teamColumn = config('permission.column_names.team_foreign_key', 'team_id');

        $passwordRules = ['nullable', 'string', 'min:6'];
        if ($this->filled('password_confirmation')) {
            $passwordRules[] = 'confirmed';
        }

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'username' => [
                'required', 'string', 'min:3', 'max:100',
                'regex:/^[a-z0-9_.-]+$/',
                new UniqueTenantUsername($tenantId),
            ],
            'email' => ['required', 'email:rfc', 'max:255'],
            'password' => $passwordRules,
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
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $email = mb_strtolower(trim((string) $this->input('email')));
            $tenantId = app(TenantContext::class)->getTenantId();
            $tenant = $tenantId ? \App\Models\Tenant::find($tenantId) : null;
            $scopedEmail = $tenant ? app(\App\Services\TenantMembershipService::class)->scopeEmailToTenant($email, $tenant) : $email;

            $existingUser = User::whereRaw('LOWER(email) = ?', [$scopedEmail])->exists();

            if (! $existingUser && ! $this->filled('password')) {
                $validator->errors()->add('password', 'A password is required for a new account.');
            }
            if (! $existingUser && ! $this->filled('name')) {
                $validator->errors()->add('name', 'A full name is required for a new account.');
            }
        });
    }
}
