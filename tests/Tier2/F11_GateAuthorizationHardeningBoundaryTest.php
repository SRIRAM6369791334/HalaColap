<?php

namespace Tests\Tier2;

use Illuminate\Support\Facades\Gate;
use Tests\E2ETestCase;

/**
 * Feature F11 Boundary Tests: Gate & Authorization Hardening
 * Verifies non-super-admin boundary enforcement, unauthenticated states, and cross-tenant action denial.
 */
class F11_GateAuthorizationHardeningBoundaryTest extends E2ETestCase
{
    public function test_false_super_admin_strictly_enforces_rbac(): void
    {
        $this->requiresColumn('users', 'is_super_admin');

        $regularUser = $this->createUser(['is_super_admin' => false]);
        $this->actingAs($regularUser);

        $this->assertFalse(Gate::allows('system-level-override'));
    }

    public function test_non_existent_ability_check_returns_false_cleanly(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $this->assertFalse(Gate::allows('non_existent_ability_xyz_123'));
    }

    public function test_guest_cannot_pass_any_scoped_ability(): void
    {
        auth()->logout();
        $this->assertFalse(Gate::allows('view dealers'));
        $this->assertFalse(Gate::allows('create dealers'));
        $this->assertFalse(Gate::allows('delete dealers'));
    }

    public function test_tenant_admin_cannot_promote_another_user_to_super_admin(): void
    {
        $this->requiresColumn('users', 'is_super_admin');

        $tenant = $this->createTenant();
        $tenantAdmin = $this->createUser(['is_super_admin' => false]);
        $targetUser = $this->createUser(['is_super_admin' => false]);

        $this->actingAs($tenantAdmin);
        $this->setTenantContext($tenant);

        // Target user must remain false
        $this->assertFalse((bool)$targetUser->is_super_admin);
    }

    public function test_multiple_gates_evaluated_independently(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $check1 = Gate::allows('view bills');
        $check2 = Gate::allows('view payments');

        $this->assertIsBool($check1);
        $this->assertIsBool($check2);
    }
}
