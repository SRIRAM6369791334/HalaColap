<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Spatie\Permission\PermissionRegistrar;

/**
 * Challenger M1-1 Replacement: Adversarial Second Tenant & Role Partitioning Probe
 *
 * Directly tests requirement 3:
 * "Test creating a second tenant and asserting that Spatie roles with identical names
 *  are properly partitioned by team_id."
 */
class AdversarialM11TenantRoleTest extends TestCase
{
    protected array $createdTenantIds = [];
    protected array $createdRoleIds = [];
    protected array $createdUserIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'poultry_db',
        ]);
        DB::purge();
        DB::setDefaultConnection('mysql');

        app(PermissionRegistrar::class)->initializeCache();
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdRoleIds)) {
            DB::connection('mysql')->table('model_has_roles')->whereIn('role_id', $this->createdRoleIds)->delete();
            DB::connection('mysql')->table('roles')->whereIn('id', $this->createdRoleIds)->delete();
        }

        if (!empty($this->createdUserIds)) {
            DB::connection('mysql')->table('tenant_user')->whereIn('user_id', $this->createdUserIds)->delete();
            DB::connection('mysql')->table('users')->whereIn('id', $this->createdUserIds)->delete();
        }

        if (!empty($this->createdTenantIds)) {
            DB::connection('mysql')->table('tenant_user')->whereIn('tenant_id', $this->createdTenantIds)->delete();
            DB::connection('mysql')->table('roles')->whereIn('team_id', $this->createdTenantIds)->delete();
            DB::connection('mysql')->table('tenants')->whereIn('id', $this->createdTenantIds)->delete();
        }

        // Clean up any untracked probe roles
        DB::connection('mysql')->table('roles')
            ->where('name', 'like', 'test_role_%')
            ->orWhere('name', 'like', 'supervisor_%')
            ->orWhere('name', 'like', 'shared_title_%')
            ->delete();

        // Clean up any test tenants
        DB::connection('mysql')->table('tenants')->where('id', '>', 1)->delete();

        app('cache')->forget(config('permission.cache.key'));

        parent::tearDown();
    }

    /**
     * Test creating a second tenant succeeds and creates a valid tenant entity.
     */
    public function test_second_tenant_creation(): void
    {
        $tenant2 = Tenant::create([
            'name' => 'Royal Broilers Farm',
            'slug' => 'royal-broilers-' . uniqid(),
            'domain' => 'royal-' . uniqid() . '.poultry.local',
            'company_name' => 'Royal Broilers Ltd',
            'email' => 'admin@royalbroilers.local',
            'currency' => 'INR',
            'timezone' => 'Asia/Kolkata',
            'date_format' => 'd-m-Y',
            'is_active' => true,
        ]);
        $this->createdTenantIds[] = $tenant2->id;

        $this->assertNotNull($tenant2->id);
        $this->assertGreaterThan(1, $tenant2->id);

        $dbTenant = DB::connection('mysql')->table('tenants')->where('id', $tenant2->id)->first();
        $this->assertNotNull($dbTenant);
        $this->assertEquals('Royal Broilers Farm', $dbTenant->name);
    }

    /**
     * Adversarial Test: Assert whether Spatie Role::create properly saves team_id.
     * When Role::create(['name' => ..., 'team_id' => $tenantId]) is called,
     * team_id MUST be persisted in MySQL.
     */
    public function test_role_create_persists_team_id(): void
    {
        $tenant2 = Tenant::create([
            'name' => 'Probe Tenant 2',
            'slug' => 'probe-t2-' . uniqid(),
            'is_active' => true,
        ]);
        $this->createdTenantIds[] = $tenant2->id;

        setPermissionsTeamId($tenant2->id);

        $roleName = 'test_role_' . uniqid();
        $role = Role::create([
            'name' => $roleName,
            'guard_name' => 'web',
            'team_id' => $tenant2->id,
        ]);
        $this->createdRoleIds[] = $role->id;

        $dbRole = DB::connection('mysql')->table('roles')->where('id', $role->id)->first();

        $this->assertNotNull($dbRole);
        $this->assertEquals(
            $tenant2->id,
            $dbRole->team_id,
            "DEFECT FOUND: App\\Models\\Role::create() failed to persist team_id. In database: team_id is " . var_export($dbRole->team_id, true) . ", expected {$tenant2->id}. Root cause: App\\Models\\Role::\$fillable omits 'team_id'."
        );
    }

    /**
     * Adversarial Test: Attempt to create identical role names in Tenant 1 and Tenant 2.
     * With multi-tenancy enabled and teams => true, Tenant 1 and Tenant 2 MUST be able
     * to define identical role names partitioned by team_id without colliding.
     */
    public function test_identical_role_names_partitioned_by_team_id(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Tenant A Agro',
            'slug' => 'tenant-a-' . uniqid(),
            'is_active' => true,
        ]);
        $this->createdTenantIds[] = $tenantA->id;

        $tenantB = Tenant::create([
            'name' => 'Tenant B Poultry',
            'slug' => 'tenant-b-' . uniqid(),
            'is_active' => true,
        ]);
        $this->createdTenantIds[] = $tenantB->id;

        $roleName = 'supervisor_' . uniqid();

        // 1. Create role in Tenant A
        setPermissionsTeamId($tenantA->id);
        $roleA = Role::create([
            'name' => $roleName,
            'guard_name' => 'web',
            'team_id' => $tenantA->id,
        ]);
        $this->createdRoleIds[] = $roleA->id;

        // 2. Create identical role in Tenant B
        setPermissionsTeamId($tenantB->id);
        $roleB = Role::create([
            'name' => $roleName,
            'guard_name' => 'web',
            'team_id' => $tenantB->id,
        ]);
        $this->createdRoleIds[] = $roleB->id;

        $this->assertNotEquals($roleA->id, $roleB->id);
        $this->assertEquals($tenantA->id, $roleA->team_id);
        $this->assertEquals($tenantB->id, $roleB->team_id);
    }

    /**
     * Test direct database insertion vs Eloquent model layer.
     * Demonstrates that MySQL schema allows identical roles with distinct team_ids,
     * proving the bug is isolated to App\Models\Role::$fillable.
     */
    public function test_mysql_composite_unique_key_allows_identical_role_names_with_distinct_team_ids(): void
    {
        $tenantA = Tenant::create([
            'name' => 'FK Tenant A',
            'slug' => 'fk-t-a-' . uniqid(),
            'is_active' => true,
        ]);
        $this->createdTenantIds[] = $tenantA->id;

        $tenantB = Tenant::create([
            'name' => 'FK Tenant B',
            'slug' => 'fk-t-b-' . uniqid(),
            'is_active' => true,
        ]);
        $this->createdTenantIds[] = $tenantB->id;

        $roleName = 'shared_title_' . uniqid();

        $idA = DB::connection('mysql')->table('roles')->insertGetId([
            'team_id' => $tenantA->id,
            'name' => $roleName,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdRoleIds[] = $idA;

        $idB = DB::connection('mysql')->table('roles')->insertGetId([
            'team_id' => $tenantB->id,
            'name' => $roleName,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdRoleIds[] = $idB;

        $this->assertNotEquals($idA, $idB);

        $rowA = DB::connection('mysql')->table('roles')->where('id', $idA)->first();
        $rowB = DB::connection('mysql')->table('roles')->where('id', $idB)->first();

        $this->assertEquals($tenantA->id, $rowA->team_id);
        $this->assertEquals($tenantB->id, $rowB->team_id);
        $this->assertEquals($rowA->name, $rowB->name);
    }
}
