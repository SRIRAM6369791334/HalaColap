<?php

namespace Tests\Feature\Tenancy;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TenantManagementAndSwitchingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\TenantSeeder::class);
    }

    public function test_super_admin_can_access_tenant_management_index(): void
    {
        $superAdmin = User::where('email', 'admin@poultry.com')->first();
        $this->actingAs($superAdmin);

        $response = $this->get(route('admin.tenants.index'));
        $response->assertStatus(200);
        $response->assertSee('MSR Broilers');
    }

    public function test_regular_tenant_admin_cannot_access_tenant_management_index(): void
    {
        $tenant = Tenant::create([
            'name' => 'Farm B',
            'slug' => 'farm-b',
            'is_active' => true,
        ]);

        $tenantAdmin = User::create([
            'name' => 'Tenant B Admin',
            'username' => 'admin_b',
            'email' => 'admin@farmb.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_super_admin' => false,
            'current_tenant_id' => $tenant->id,
        ]);

        $this->actingAs($tenantAdmin);

        $response = $this->get(route('admin.tenants.index'));
        $response->assertStatus(403);
    }

    public function test_super_admin_can_create_new_business_tenant_with_admin(): void
    {
        $superAdmin = User::where('email', 'admin@poultry.com')->first();
        $this->actingAs($superAdmin);

        $response = $this->post(route('admin.tenants.store'), [
            'name'           => 'Kongu Broilers',
            'company_name'   => 'Kongu Broilers Ltd',
            'slug'           => 'kongu-broilers',
            'email'          => 'info@kongu.com',
            'phone'          => '9842100000',
            'admin_name'     => 'Kongu Manager',
            'admin_username' => 'kongu_admin',
            'admin_email'    => 'manager@kongu.com',
            'admin_password' => 'Secret@123',
            'admin_password_confirmation' => 'Secret@123',
        ]);

        $response->assertRedirect(route('admin.tenants.index'));

        // Verify Tenant exists
        $newTenant = Tenant::where('slug', 'kongu-broilers')->first();
        $this->assertNotNull($newTenant);
        $this->assertEquals('Kongu Broilers', $newTenant->name);

        // Verify Tenant Admin exists and is NOT super admin
        $adminUser = User::where('email', 'manager@kongu.com')->first();
        $this->assertNotNull($adminUser);
        $this->assertFalse((bool)$adminUser->is_super_admin);
        $this->assertEquals($newTenant->id, $adminUser->current_tenant_id);

        // Verify pivot table attachment
        $pivot = DB::table('tenant_user')
            ->where('tenant_id', $newTenant->id)
            ->where('user_id', $adminUser->id)
            ->first();
        $this->assertNotNull($pivot);
        $this->assertEquals('admin', $pivot->role);
    }

    public function test_onboarding_new_tenant_when_admin_role_exists_with_tenant_1(): void
    {
        // 1. Manually create an admin role assigned to team_id = 1 (just like production MySQL)
        \App\Models\Role::create([
            'name' => 'admin',
            'team_id' => 1,
            'guard_name' => 'web',
        ]);

        $superAdmin = User::where('email', 'admin@poultry.com')->first();
        $this->actingAs($superAdmin);

        $response = $this->post(route('admin.tenants.store'), [
            'name'           => 'New Test Farm',
            'company_name'   => 'New Test Farm Ltd',
            'slug'           => 'new-test-farm',
            'email'          => 'info@newtestfarm.com',
            'phone'          => '9842100001',
            'admin_name'     => 'Farm Admin',
            'admin_username' => 'farm_admin',
            'admin_email'    => 'admin@newtestfarm.com',
            'admin_password' => 'Secret@123',
            'admin_password_confirmation' => 'Secret@123',
        ]);

        $response->assertRedirect(route('admin.tenants.index'));
    }

    public function test_super_admin_cannot_onboard_tenant_with_duplicate_email_phone_or_admin_email(): void
    {
        $superAdmin = User::where('email', 'admin@poultry.com')->first();
        $this->actingAs($superAdmin);

        // Pre-create an existing tenant and user
        $existingTenant = Tenant::create([
            'name' => 'Existing Broilers',
            'slug' => 'existing-broilers',
            'email' => 'contact@existing.com',
            'phone' => '9842112345',
            'is_active' => true,
        ]);

        $existingUser = User::create([
            'name' => 'Existing Person',
            'email' => 'admin@existing.com',
            'password' => bcrypt('OldPassword@123'),
            'is_active' => true,
            'is_super_admin' => false,
            'current_tenant_id' => $existingTenant->id,
        ]);

        // 1. Attempt with duplicate admin_email
        $res1 = $this->post(route('admin.tenants.store'), [
            'name'           => 'Delta Broilers 1',
            'slug'           => 'delta-1',
            'email'          => 'unique1@delta.com',
            'phone'          => '9842199001',
            'admin_name'     => 'Delta Lead',
            'admin_username' => 'delta_lead1',
            'admin_email'    => 'admin@existing.com', // Duplicate user email
            'admin_password' => 'NewSecret@123',
            'admin_password_confirmation' => 'NewSecret@123',
        ]);
        $res1->assertSessionHasErrors(['admin_email']);

        // 2. Attempt with duplicate business email
        $res2 = $this->post(route('admin.tenants.store'), [
            'name'           => 'Delta Broilers 2',
            'slug'           => 'delta-2',
            'email'          => 'contact@existing.com', // Duplicate business email
            'phone'          => '9842199002',
            'admin_name'     => 'Delta Lead',
            'admin_username' => 'delta_lead2',
            'admin_email'    => 'unique2@delta.com',
            'admin_password' => 'NewSecret@123',
            'admin_password_confirmation' => 'NewSecret@123',
        ]);
        $res2->assertSessionHasErrors(['email']);

        // 3. Attempt with duplicate business phone
        $res3 = $this->post(route('admin.tenants.store'), [
            'name'           => 'Delta Broilers 3',
            'slug'           => 'delta-3',
            'email'          => 'unique3@delta.com',
            'phone'          => '9842112345', // Duplicate business phone
            'admin_name'     => 'Delta Lead',
            'admin_username' => 'delta_lead3',
            'admin_email'    => 'unique3_admin@delta.com',
            'admin_password' => 'NewSecret@123',
            'admin_password_confirmation' => 'NewSecret@123',
        ]);
        $res3->assertSessionHasErrors(['phone']);

        // 4. Attempt with all unique values -> SUCCESS
        $res4 = $this->post(route('admin.tenants.store'), [
            'name'           => 'Delta Broilers 4',
            'slug'           => 'delta-4',
            'email'          => 'unique4@delta.com',
            'phone'          => '9842199004',
            'admin_name'     => 'Delta Lead 4',
            'admin_username' => 'delta_lead4',
            'admin_email'    => 'unique4_admin@delta.com',
            'admin_password' => 'NewSecret@123',
            'admin_password_confirmation' => 'NewSecret@123',
        ]);
        $res4->assertRedirect(route('admin.tenants.index'));

        $newTenant = Tenant::where('slug', 'delta-4')->first();
        $this->assertNotNull($newTenant);
    }

    public function test_super_admin_can_switch_tenant_context(): void
    {
        $superAdmin = User::where('email', 'admin@poultry.com')->first();
        $this->actingAs($superAdmin);

        $tenant2 = Tenant::create([
            'name' => 'Salem Farms',
            'slug' => 'salem-farms',
            'is_active' => true,
        ]);

        $response = $this->post(route('tenant.switch'), [
            'tenant_id' => $tenant2->id,
        ]);

        $response->assertSessionHas('active_tenant_id', $tenant2->id);
        $superAdmin->refresh();
        $this->assertEquals($tenant2->id, $superAdmin->current_tenant_id);
    }

    public function test_regular_user_cannot_switch_to_unassigned_tenant(): void
    {
        $tenant1 = Tenant::first();
        $tenant2 = Tenant::create([
            'name' => 'Isolated Farm',
            'slug' => 'isolated-farm',
            'is_active' => true,
        ]);

        $user = User::create([
            'name' => 'Farm 1 Staff',
            'username' => 'staff1',
            'email' => 'staff1@farm.com',
            'password' => bcrypt('password'),
            'is_active' => true,
            'is_super_admin' => false,
            'current_tenant_id' => $tenant1->id,
        ]);

        // Attach user only to tenant 1
        DB::table('tenant_user')->insert([
            'tenant_id' => $tenant1->id,
            'user_id' => $user->id,
            'username' => 'staff1',
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this->actingAs($user);

        // Attempting to switch to unassigned tenant 2 must be blocked
        $response = $this->post(route('tenant.switch'), [
            'tenant_id' => $tenant2->id,
        ]);

        $response->assertSessionHas('error');
        $this->assertNotEquals($tenant2->id, session('active_tenant_id'));
    }

    public function test_super_admin_can_update_tenant_and_change_admin_password_from_edit_page(): void
    {
        $superAdmin = User::where('email', 'admin@poultry.com')->first();
        $this->actingAs($superAdmin);

        $tenant = Tenant::create([
            'name' => 'Kovai Poultry',
            'slug' => 'kovai-poultry',
            'email' => 'admin@kovai.com',
            'is_active' => true,
        ]);

        $adminUser = User::create([
            'name' => 'Kovai Admin',
            'email' => 'admin@kovai.com',
            'password' => bcrypt('InitialPass@123'),
            'is_active' => true,
            'is_super_admin' => false,
            'current_tenant_id' => $tenant->id,
        ]);

        DB::table('tenant_user')->insert([
            'tenant_id' => $tenant->id,
            'user_id' => $adminUser->id,
            'username' => 'kovai_admin',
            'role' => 'admin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 1. Visit Edit Page: Must see Administrator section & password inputs
        $response = $this->get(route('admin.tenants.edit', $tenant->id));
        $response->assertStatus(200);
        $response->assertSee('Tenant Administrator and Change Password');
        $response->assertSee('admin_password');
        $response->assertSee('kovai_admin');

        // 2. Submit new password
        $updateResponse = $this->put(route('admin.tenants.update', $tenant->id), [
            'name'                        => 'Kovai Poultry Updated',
            'company_name'                => 'Kovai Poultry Private Limited',
            'email'                       => 'admin@kovai.com',
            'phone'                       => '9842100088',
            'admin_user_id'               => $adminUser->id,
            'admin_username'              => 'kovai_boss',
            'admin_password'              => 'BrandNewPassword@2026',
            'admin_password_confirmation' => 'BrandNewPassword@2026',
            'is_active'                   => true,
        ]);

        $updateResponse->assertRedirect(route('admin.tenants.edit', $tenant->id));
        $updateResponse->assertSessionHas('success');

        // Verify password was changed in database
        $adminUser->refresh();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('BrandNewPassword@2026', $adminUser->password));
        $this->assertFalse(\Illuminate\Support\Facades\Hash::check('InitialPass@123', $adminUser->password));

        // Verify username was changed in pivot table
        $pivot = DB::table('tenant_user')->where('tenant_id', $tenant->id)->where('user_id', $adminUser->id)->first();
        $this->assertEquals('kovai_boss', $pivot->username);

        // Verify login succeeds with the new password
        $resolved = app(\App\Services\TenantIdentityService::class)->resolveForCredentials('kovai_boss', 'BrandNewPassword@2026');
        $this->assertNotNull($resolved);
        $this->assertEquals($tenant->id, $resolved['tenant']->id);
    }
}
