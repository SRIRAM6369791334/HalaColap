<?php

namespace Tests\Tier2;

use Tests\E2ETestCase;

/**
 * Feature F05 Boundary Tests: Spatie Teams RBAC Schema & Config
 * Verifies global roles (team_id = null) vs scoped roles (team_id = X) and team switching.
 */
class F05_SpatieTeamsRbacBoundaryTest extends E2ETestCase
{
    public function test_global_role_with_null_team_id_boundary(): void
    {
        $this->requiresColumn('roles', 'team_id');

        $roleClass = config('permission.models.role', \Spatie\Permission\Models\Role::class);
        if (class_exists($roleClass)) {
            $globalRole = $roleClass::firstOrCreate([
                'name'       => 'global_super_viewer',
                'guard_name' => 'web',
                'team_id'    => null,
            ]);

            $this->assertNull($globalRole->team_id);
        }
    }

    public function test_role_assignment_is_idempotent(): void
    {
        $this->requiresColumn('roles', 'team_id');
        $this->requiresColumn('model_has_roles', 'team_id');

        $user = $this->createUser();
        $roleClass = config('permission.models.role', \Spatie\Permission\Models\Role::class);

        if (function_exists('setPermissionsTeamId') && class_exists($roleClass)) {
            setPermissionsTeamId(1);
            $role = $roleClass::firstOrCreate(['name' => 'accountant', 'guard_name' => 'web', 'team_id' => 1]);

            $user->assignRole($role);
            $user->assignRole($role); // Duplicate assign

            $this->assertTrue($user->hasRole('accountant'));
        }
    }

    public function test_switching_team_id_in_same_request_lifecycle(): void
    {
        $this->requiresColumn('roles', 'team_id');

        if (function_exists('setPermissionsTeamId') && function_exists('getPermissionsTeamId')) {
            setPermissionsTeamId(10);
            $this->assertEquals(10, getPermissionsTeamId());

            setPermissionsTeamId(20);
            $this->assertEquals(20, getPermissionsTeamId());

            setPermissionsTeamId(null);
            $this->assertNull(getPermissionsTeamId());
        }
    }

    public function test_role_with_same_name_in_two_teams_has_distinct_ids(): void
    {
        $this->requiresColumn('roles', 'team_id');

        $roleClass = config('permission.models.role', \Spatie\Permission\Models\Role::class);
        if (class_exists($roleClass)) {
            $role1 = $roleClass::forceCreate(['name' => 'manager', 'guard_name' => 'web', 'team_id' => 101]);
            $role2 = $roleClass::forceCreate(['name' => 'manager', 'guard_name' => 'web', 'team_id' => 102]);

            $this->assertNotEquals($role1->id, $role2->id);
            $this->assertEquals(101, $role1->team_id);
            $this->assertEquals(102, $role2->team_id);
        }
    }

    public function test_revoking_role_in_team_one_leaves_other_teams_untouched(): void
    {
        $this->requiresColumn('roles', 'team_id');
        $this->requiresColumn('model_has_roles', 'team_id');

        $user = $this->createUser();
        $roleClass = config('permission.models.role', \Spatie\Permission\Models\Role::class);

        if (function_exists('setPermissionsTeamId') && class_exists($roleClass)) {
            setPermissionsTeamId(1);
            $role1 = $roleClass::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web', 'team_id' => 1]);
            $user->assignRole($role1);

            setPermissionsTeamId(2);
            $role2 = $roleClass::firstOrCreate(['name' => 'cashier', 'guard_name' => 'web', 'team_id' => 2]);
            $user->assignRole($role2);

            // Remove from team 1
            setPermissionsTeamId(1);
            $user->removeRole($role1);

            $this->assertFalse($user->unsetRelation('roles')->hasRole('cashier'));

            // Verify team 2 still has role
            setPermissionsTeamId(2);
            $this->assertTrue($user->unsetRelation('roles')->hasRole('cashier'));
        }
    }
}
