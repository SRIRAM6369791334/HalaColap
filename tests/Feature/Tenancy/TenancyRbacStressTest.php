<?php

namespace Tests\Feature\Tenancy;

use Tests\TestCase;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\UniqueConstraintViolationException;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Spatie\Permission\PermissionRegistrar;

/**
 * Empirical Stress Test Suite for Milestone 1:
 * Multi-Tenancy Schema & Spatie Teams RBAC
 */
class TenancyRbacStressTest extends TestCase
{
    protected array $createdTenantIds = [];
    protected array $createdUserIds = [];
    protected array $createdRoleIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Enforce MySQL poultry_db connection
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'poultry_db',
        ]);
        DB::purge();
        DB::setDefaultConnection('mysql');

        // Re-initialize Spatie PermissionRegistrar with teams
        app(PermissionRegistrar::class)->initializeCache();
    }

    protected function tearDown(): void
    {
        // Clean up all created test data from MySQL poultry_db
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

        app('cache')->forget(config('permission.cache.key'));

        parent::tearDown();
    }

    /**
     * Test 1: Multiple test tenants creation and attribute isolation.
     */
    public function test_multiple_tenants_creation_and_attributes(): void
    {
        $tenant2 = Tenant::create([
            'name' => 'Farm Alpha Broilers',
            'slug' => 'farm-alpha-' . uniqid(),
            'domain' => 'alpha-' . uniqid() . '.poultry.local',
            'company_name' => 'Alpha Agro Farms Pvt Ltd',
            'email' => 'admin@alphafarm.local',
            'currency' => 'INR',
            'timezone' => 'Asia/Kolkata',
            'date_format' => 'd-m-Y',
            'is_active' => true,
        ]);
        $this->createdTenantIds[] = $tenant2->id;

        $tenant3 = Tenant::create([
            'name' => 'Farm Beta Poultry',
            'slug' => 'farm-beta-' . uniqid(),
            'domain' => 'beta-' . uniqid() . '.poultry.local',
            'company_name' => 'Beta Broiler Farms LLP',
            'email' => 'admin@betafarm.local',
            'currency' => 'INR',
            'timezone' => 'Asia/Kolkata',
            'date_format' => 'd-m-Y',
            'is_active' => true,
        ]);
        $this->createdTenantIds[] = $tenant3->id;

        $this->assertNotNull($tenant2->id);
        $this->assertNotNull($tenant3->id);
        $this->assertNotEquals($tenant2->id, $tenant3->id);

        // Verify existence in MySQL tenants table
        $row2 = DB::connection('mysql')->table('tenants')->where('id', $tenant2->id)->first();
        $row3 = DB::connection('mysql')->table('tenants')->where('id', $tenant3->id)->first();

        $this->assertNotNull($row2);
        $this->assertNotNull($row3);
        $this->assertEquals($tenant2->slug, $row2->slug);
        $this->assertEquals($tenant3->slug, $row3->slug);
    }

    /**
     * Test 2: User tenant switching and membership associations in tenant_user.
     */
    public function test_user_tenant_switching_and_memberships(): void
    {
        $tenant2 = Tenant::create([
            'name' => 'Membership Tenant A',
            'slug' => 'mem-t-a-' . uniqid(),
            'is_active' => true,
        ]);
        $this->createdTenantIds[] = $tenant2->id;

        $tenant3 = Tenant::create([
            'name' => 'Membership Tenant B',
            'slug' => 'mem-t-b-' . uniqid(),
            'is_active' => true,
        ]);
        $this->createdTenantIds[] = $tenant3->id;

        $user = User::create([
            'name' => 'Multi-Tenant Operator',
            'email' => 'operator_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'current_tenant_id' => $tenant2->id,
            'is_active' => true,
            'is_super_admin' => false,
        ]);
        $this->createdUserIds[] = $user->id;

        // Associate user with Tenant 2 as 'admin' and Tenant 3 as 'operator'
        $user->tenants()->attach($tenant2->id, ['username' => 'rbac_user', 'role' => 'admin', 'is_active' => true]);
        $user->tenants()->attach($tenant3->id, ['username' => 'rbac_user', 'role' => 'operator', 'is_active' => true]);

        // Assert memberships
        $this->assertTrue($user->belongsToTenant($tenant2));
        $this->assertTrue($user->belongsToTenant($tenant3));
        $this->assertFalse($user->belongsToTenant(999999));

        $this->assertEquals($tenant2->id, $user->current_tenant_id);

        // Switch to Tenant 3
        $user->update(['current_tenant_id' => $tenant3->id]);
        $user->refresh();
        $this->assertEquals($tenant3->id, $user->current_tenant_id);
        $this->assertEquals($tenant3->name, $user->currentTenant->name);

        // Verify pivot table records
        $pivotT2 = DB::connection('mysql')->table('tenant_user')->where('tenant_id', $tenant2->id)->where('user_id', $user->id)->first();
        $pivotT3 = DB::connection('mysql')->table('tenant_user')->where('tenant_id', $tenant3->id)->where('user_id', $user->id)->first();

        $this->assertNotNull($pivotT2);
        $this->assertNotNull($pivotT3);
        $this->assertEquals('admin', $pivotT2->role);
        $this->assertEquals('operator', $pivotT3->role);
    }

    /**
     * Test 3: Assert unique constraint on tenant_user prevents duplicate membership.
     */
    public function test_tenant_user_prevents_duplicate_membership(): void
    {
        $tenant = Tenant::create([
            'name' => 'Duplicate Membership Tenant',
            'slug' => 'dup-mem-' . uniqid(),
            'is_active' => true,
        ]);
        $this->createdTenantIds[] = $tenant->id;

        $user = User::create([
            'name' => 'Dup User',
            'email' => 'dup_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'current_tenant_id' => $tenant->id,
            'is_active' => true,
        ]);
        $this->createdUserIds[] = $user->id;

        $user->tenants()->attach($tenant->id, ['username' => 'duplicate_user', 'role' => 'staff', 'is_active' => true]);

        $this->expectException(UniqueConstraintViolationException::class);
        // Attempting to attach same tenant again must violate UNIQUE(tenant_id, user_id)
        $user->tenants()->attach($tenant->id, ['username' => 'duplicate_user', 'role' => 'admin', 'is_active' => true]);
    }

    /**
     * Test 4: Role model team_id mass-assignment vulnerability audit.
     * Verifies whether App\Models\Role allows 'team_id' in $fillable,
     * or if Eloquent strips it when Role::create() is called.
     */
    public function test_role_model_persists_team_id_on_create(): void
    {
        $tenant = Tenant::create([
            'name' => 'Role Team Persistence Tenant',
            'slug' => 'role-persist-' . uniqid(),
            'is_active' => true,
        ]);
        $this->createdTenantIds[] = $tenant->id;

        setPermissionsTeamId($tenant->id);
        $role = Role::create([
            'name' => 'custom_operator_' . uniqid(),
            'guard_name' => 'web',
            'team_id' => $tenant->id,
        ]);
        $this->createdRoleIds[] = $role->id;

        $dbRole = DB::connection('mysql')->table('roles')->where('id', $role->id)->first();

        $this->assertNotNull($dbRole);
        $this->assertEquals(
            $tenant->id,
            $dbRole->team_id,
            "CRITICAL DEFECT: Role::create() dropped 'team_id' because App\\Models\\Role::\$fillable is missing 'team_id'. In database, team_id was saved as: " . var_export($dbRole->team_id, true)
        );
    }

    /**
     * Test 5: Identical role names under different team IDs.
     * When two tenants each define a role named 'operator', Spatie teams must
     * partition them by team_id without RoleAlreadyExists collision.
     */
    public function test_identical_role_names_under_different_team_ids(): void
    {
        $tenant2 = Tenant::create([
            'name' => 'Stress Tenant Alpha',
            'slug' => 'stress-alpha-' . uniqid(),
            'is_active' => true,
        ]);
        $this->createdTenantIds[] = $tenant2->id;

        $tenant3 = Tenant::create([
            'name' => 'Stress Tenant Beta',
            'slug' => 'stress-beta-' . uniqid(),
            'is_active' => true,
        ]);
        $this->createdTenantIds[] = $tenant3->id;

        $roleName = 'site_operator_' . uniqid();

        // 1. Create role in Tenant Alpha
        setPermissionsTeamId($tenant2->id);
        $roleT2 = Role::create([
            'name' => $roleName,
            'guard_name' => 'web',
            'team_id' => $tenant2->id,
        ]);
        $this->createdRoleIds[] = $roleT2->id;

        // 2. Create identical role name in Tenant Beta
        setPermissionsTeamId($tenant3->id);
        $roleT3 = Role::create([
            'name' => $roleName,
            'guard_name' => 'web',
            'team_id' => $tenant3->id,
        ]);
        $this->createdRoleIds[] = $roleT3->id;

        // Both roles must exist with distinct IDs and correct team_id values
        $this->assertNotEquals($roleT2->id, $roleT3->id);
        $this->assertEquals($tenant2->id, $roleT2->team_id);
        $this->assertEquals($tenant3->id, $roleT3->team_id);
    }

    /**
     * Test 6: Spatie model_has_roles isolation across teams.
     */
    public function test_spatie_user_role_assignment_scoped_to_team(): void
    {
        $tenant2 = Tenant::create([
            'name' => 'Assignment Tenant 2',
            'slug' => 'assign-t2-' . uniqid(),
            'is_active' => true,
        ]);
        $this->createdTenantIds[] = $tenant2->id;

        $tenant3 = Tenant::create([
            'name' => 'Assignment Tenant 3',
            'slug' => 'assign-t3-' . uniqid(),
            'is_active' => true,
        ]);
        $this->createdTenantIds[] = $tenant3->id;

        $user = User::create([
            'name' => 'Scoped Assignment User',
            'email' => 'scoped_assign_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'current_tenant_id' => $tenant2->id,
            'is_active' => true,
            'is_super_admin' => false,
        ]);
        $this->createdUserIds[] = $user->id;

        // Create roles manually or via DB if Role::create fillable issue exists
        $roleIdA = DB::connection('mysql')->table('roles')->insertGetId([
            'team_id' => $tenant2->id,
            'name' => 'field_manager',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdRoleIds[] = $roleIdA;
        $roleA = Role::find($roleIdA);

        $roleIdB = DB::connection('mysql')->table('roles')->insertGetId([
            'team_id' => $tenant3->id,
            'name' => 'field_manager',
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdRoleIds[] = $roleIdB;
        $roleB = Role::find($roleIdB);

        // Assign role in Tenant 2
        setPermissionsTeamId($tenant2->id);
        $user->assignRole($roleA);

        $this->assertTrue($user->hasRole('field_manager'), 'User should have field_manager in Tenant 2');

        // Switch to Tenant 3
        setPermissionsTeamId($tenant3->id);
        $user->unsetRelation('roles');
        $this->assertFalse($user->hasRole('field_manager'), 'User must NOT have field_manager in Tenant 3');

        // Assign in Tenant 3
        $user->assignRole($roleB);
        $user->unsetRelation('roles');
        $this->assertTrue($user->hasRole('field_manager'), 'User should have field_manager in Tenant 3 now');
    }
}
