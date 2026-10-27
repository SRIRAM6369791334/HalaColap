<?php

namespace Tests\Feature\Tenancy;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\Reseller;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

class CoreTenancySchemaTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'poultry_db',
        ]);
        DB::purge();
    }

    /**
     * Helper to get database connection.
     */
    protected function getDbConnection()
    {
        return DB::connection('mysql');
    }

    /**
     * Test config/permission.php has teams enabled.
     */
    public function test_spatie_permission_config_has_teams_enabled(): void
    {
        $this->assertTrue(config('permission.teams'), 'config/permission.php must have teams => true');
        $this->assertEquals('team_id', config('permission.column_names.team_foreign_key'));
        $this->assertEquals(\App\Models\Role::class, config('permission.models.role'));
    }

    /**
     * Test core multi-tenancy tables exist in MySQL schema.
     */
    public function test_core_multitenancy_tables_exist_in_database(): void
    {
        $mysqlSchema = Schema::connection('mysql');

        $this->assertTrue($mysqlSchema->hasTable('tenants'), 'Table tenants must exist');
        $this->assertTrue($mysqlSchema->hasTable('resellers'), 'Table resellers must exist');
        $this->assertTrue($mysqlSchema->hasTable('tenant_user'), 'Table tenant_user must exist');

        // Check tenants columns
        $this->assertTrue($mysqlSchema->hasColumns('tenants', [
            'id', 'name', 'slug', 'domain', 'company_name', 'email', 'phone',
            'address', 'gst_number', 'logo_path', 'currency', 'timezone',
            'date_format', 'is_active', 'reseller_id', 'settings', 'created_by'
        ]));

        // Check resellers columns
        $this->assertTrue($mysqlSchema->hasColumns('resellers', [
            'id', 'name', 'company_name', 'email', 'phone', 'commission_rate',
            'is_active', 'address', 'notes', 'created_by'
        ]));

        // Check tenant_user columns
        $this->assertTrue($mysqlSchema->hasColumns('tenant_user', [
            'id', 'tenant_id', 'user_id', 'role', 'is_active'
        ]));

        // Check users columns
        $this->assertTrue($mysqlSchema->hasColumns('users', [
            'current_tenant_id', 'is_super_admin'
        ]));
    }

    /**
     * Test Spatie permission tables have team_id column in MySQL schema.
     */
    public function test_spatie_tables_have_team_id_column(): void
    {
        $mysqlSchema = Schema::connection('mysql');

        $this->assertTrue($mysqlSchema->hasColumn('roles', 'team_id'));
        $this->assertTrue($mysqlSchema->hasColumn('model_has_roles', 'team_id'));
        $this->assertTrue($mysqlSchema->hasColumn('model_has_permissions', 'team_id'));
    }

    /**
     * Test Tenant #1 (MSR Broilers) exists in MySQL and is active.
     */
    public function test_msr_broilers_tenant_one_seeded(): void
    {
        $tenant = $this->getDbConnection()->table('tenants')->where('id', 1)->first();

        $this->assertNotNull($tenant, 'Tenant #1 must exist');
        $this->assertEquals('MSR Broilers', $tenant->name);
        $this->assertEquals('msr-broilers', $tenant->slug);
        $this->assertEquals('admin@poultry.com', $tenant->email);
        $this->assertTrue((bool) $tenant->is_active);
    }

    /**
     * Test Super Admin and users are correctly assigned to Tenant #1.
     */
    public function test_super_admin_and_users_assigned_to_tenant_one(): void
    {
        $admin = $this->getDbConnection()->table('users')->where('email', 'admin@poultry.com')->first();
        $this->assertNotNull($admin, 'admin@poultry.com must exist');
        $this->assertTrue((bool) $admin->is_super_admin, 'admin@poultry.com must be super admin');
        $this->assertEquals(1, $admin->current_tenant_id, 'admin@poultry.com current_tenant_id must be 1');

        $sriram = $this->getDbConnection()->table('users')->where('email', 'sriram@gmail.com')->first();
        if ($sriram) {
            $this->assertFalse((bool) $sriram->is_super_admin, 'sriram@gmail.com must not be super admin');
            $this->assertEquals(1, $sriram->current_tenant_id, 'sriram@gmail.com current_tenant_id must be 1');
        }

        // Check memberships in tenant_user
        $adminMembership = $this->getDbConnection()->table('tenant_user')
            ->where('tenant_id', 1)
            ->where('user_id', $admin->id)
            ->first();

        $this->assertNotNull($adminMembership, 'admin must have tenant_user membership');
        $this->assertEquals('admin', $adminMembership->role);
        $this->assertTrue((bool) $adminMembership->is_active);
    }

    /**
     * Test Spatie roles have team_id backfilled to 1.
     */
    public function test_spatie_roles_have_team_id_backfilled(): void
    {
        $rolesWithoutTeam = $this->getDbConnection()->table('roles')->whereNull('team_id')->count();
        $this->assertEquals(0, $rolesWithoutTeam, 'All roles must have team_id populated');

        $rolesWithTeamOne = $this->getDbConnection()->table('roles')->where('team_id', 1)->count();
        $this->assertGreaterThan(0, $rolesWithTeamOne, 'Existing roles must have team_id = 1');

        $assignmentsWithoutTeam = $this->getDbConnection()->table('model_has_roles')
            ->whereNull('team_id')
            ->orWhere('team_id', 0)
            ->count();
        $this->assertEquals(0, $assignmentsWithoutTeam, 'All model_has_roles must have valid team_id');
    }

    /**
     * Test User Eloquent model helper methods.
     */
    public function test_user_model_tenancy_methods(): void
    {
        $superAdmin = new User([
            'id' => 1,
            'name' => 'Super Admin',
            'email' => 'admin@poultry.com',
            'is_super_admin' => true,
            'current_tenant_id' => 1,
        ]);

        $this->assertTrue($superAdmin->isSuperAdmin());

        $regularUser = new User([
            'id' => 2,
            'name' => 'Regular User',
            'email' => 'user@poultry.com',
            'is_super_admin' => false,
            'current_tenant_id' => 1,
        ]);

        $this->assertFalse($regularUser->isSuperAdmin());
    }

    /**
     * Test Gate::before behavior with is_super_admin.
     */
    public function test_gate_before_allows_super_admin_only(): void
    {
        $superAdmin = new User([
            'id' => 1,
            'name' => 'Super Admin',
            'email' => 'admin@poultry.com',
            'is_super_admin' => true,
        ]);

        $this->assertTrue(Gate::forUser($superAdmin)->allows('any-arbitrary-ability'));

        $regularUser = new User([
            'id' => 2,
            'name' => 'Regular User',
            'email' => 'user@poultry.com',
            'is_super_admin' => false,
        ]);

        // Arbitrary ability not explicitly defined on gate must return false for non-super-admin
        $this->assertFalse(Gate::forUser($regularUser)->allows('non-existent-ability'));
    }
}
