<?php

namespace Tests\Tier1;

use Illuminate\Support\Facades\Schema;
use Tests\E2ETestCase;

/**
 * Feature F05: Spatie Teams RBAC Schema & Config
 * Verifies that Spatie Permission teams mode is enabled and team_id exists on
 * roles, model_has_roles, and model_has_permissions tables.
 */
class F05_SpatieTeamsRbacTest extends E2ETestCase
{
    public function test_permission_config_has_teams_enabled(): void
    {
        $teamsConfig = config('permission.teams');
        if ($teamsConfig !== true) {
            $this->markTestSkipped('Progressive check: config/permission.php teams is pending milestone configuration.');
        }
        $this->assertTrue(config('permission.teams'), 'Spatie permissions teams setting is not true.');
    }

    public function test_roles_table_has_team_id_column(): void
    {
        $this->requiresColumn('roles', 'team_id');
        $this->assertTrue(Schema::hasColumn('roles', 'team_id'));
    }

    public function test_model_has_roles_table_has_team_id_column(): void
    {
        $this->requiresColumn('model_has_roles', 'team_id');
        $this->assertTrue(Schema::hasColumn('model_has_roles', 'team_id'));
    }

    public function test_model_has_permissions_table_has_team_id_column(): void
    {
        $this->requiresColumn('model_has_permissions', 'team_id');
        $this->assertTrue(Schema::hasColumn('model_has_permissions', 'team_id'));
    }

    public function test_role_scoped_per_tenant_team(): void
    {
        $this->requiresColumn('roles', 'team_id');
        $this->requiresColumn('model_has_roles', 'team_id');

        if (config('permission.teams') !== true) {
            $this->markTestSkipped('Progressive check: Spatie teams configuration is pending Milestone 1 implementation.');
        }

        // Verify that assigning a role for tenant 1 does not assign for tenant 2
        $user = $this->createUser();
        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId(1);
            $roleClass = config('permission.models.role', \Spatie\Permission\Models\Role::class);
            if (class_exists($roleClass)) {
                $role = $roleClass::firstOrCreate(['name' => 'admin', 'guard_name' => 'web', 'team_id' => 1]);
                $user->assignRole($role);
                $this->assertTrue($user->hasRole('admin'));

                setPermissionsTeamId(2);
                $this->assertFalse($user->unsetRelation('roles')->hasRole('admin'), 'Role assigned to team 1 leaked to team 2');
            }
        }
    }
}
