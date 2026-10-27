<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\DB;

class UniqueTenantUsername implements ValidationRule
{
    public function __construct(
        private int $tenantId,
        private ?int $ignoreUserId = null,
        private bool $includeInvitations = true,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $username = mb_strtolower(trim((string) $value));

        $membershipExists = DB::table('tenant_user')
            ->where('tenant_id', $this->tenantId)
            ->whereRaw('LOWER(username) = ?', [$username])
            ->when($this->ignoreUserId, fn ($query) => $query->where('user_id', '!=', $this->ignoreUserId))
            ->exists();

        $invitationExists = $this->includeInvitations
            && DB::table('tenant_user_invitations')
                ->where('tenant_id', $this->tenantId)
                ->whereRaw('LOWER(username) = ?', [$username])
                ->where('status', 'pending')
                ->where('expires_at', '>', now())
                ->exists();

        if ($membershipExists || $invitationExists) {
            $fail('This username is already in use for this business.');
        }
    }
}
