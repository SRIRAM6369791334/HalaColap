<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Rules\UniqueTenantUsername;
use App\Services\TenantIdentityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TenantScopedIdentityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('current_tenant_id')->nullable();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_super_admin')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('tenant_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('user_id');
            $table->string('username');
            $table->string('role')->default('staff');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'username']);
        });
        Schema::create('tenant_user_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id');
            $table->foreignId('user_id');
            $table->string('email');
            $table->string('username');
            $table->string('status')->default('pending');
            $table->timestamp('expires_at');
        });
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->nullable();
            $table->string('name');
            $table->string('guard_name')->default('web');
            $table->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->foreignId('role_id');
            $table->string('model_type');
            $table->foreignId('model_id');
            $table->foreignId('team_id')->nullable();
        });

        DB::table('roles')->insert([
            ['name' => 'admin', 'guard_name' => 'web', 'team_id' => null, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'staff', 'guard_name' => 'web', 'team_id' => null, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Tenant::insert([
            ['id' => 1, 'name' => 'Tenant One', 'slug' => 'tenant-one', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Tenant Two', 'slug' => 'tenant-two', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        User::insert([
            ['id' => 1, 'current_tenant_id' => 1, 'name' => 'Arun One', 'email' => 'one@example.com', 'password' => Hash::make('Password123'), 'is_active' => true, 'is_super_admin' => false, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'current_tenant_id' => 2, 'name' => 'Arun Two', 'email' => 'two@example.com', 'password' => Hash::make('Password456'), 'is_active' => true, 'is_super_admin' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('tenant_user')->insert([
            ['tenant_id' => 1, 'user_id' => 1, 'username' => 'arun3', 'role' => 'staff', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => 2, 'user_id' => 2, 'username' => 'arun3', 'role' => 'staff', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_same_username_is_allowed_in_different_tenants_but_not_same_tenant(): void
    {
        $sameTenant = Validator::make(['username' => ' ARUN3 '], [
            'username' => [new UniqueTenantUsername(1)],
        ]);
        $thirdTenant = Validator::make(['username' => 'arun3'], [
            'username' => [new UniqueTenantUsername(3)],
        ]);

        $this->assertTrue($sameTenant->fails());
        $this->assertTrue($thirdTenant->passes());
    }

    public function test_business_code_resolves_the_correct_duplicate_username(): void
    {
        $service = app(TenantIdentityService::class);

        $tenantOne = $service->resolve('tenant-one', 'ARUN3');
        $tenantTwo = $service->resolve('tenant-two', ' arun3 ');

        $this->assertSame(1, $tenantOne['user']->id);
        $this->assertSame(2, $tenantTwo['user']->id);
        $this->assertSame('arun3', $tenantOne['membership']->username);
    }

    public function test_normal_username_login_uses_password_to_resolve_the_correct_tenant(): void
    {
        $service = app(TenantIdentityService::class);

        $this->assertSame(1, $service->resolveForCredentials(' ARUN3 ', 'Password123')['tenant']->id);
        $this->assertSame(2, $service->resolveForCredentials('arun3', 'Password456')['tenant']->id);
        $this->assertNull($service->resolveForCredentials('arun3', 'wrong-password'));
    }

    public function test_normal_username_login_rejects_ambiguous_accounts_with_the_same_password(): void
    {
        User::whereKey(2)->update(['password' => Hash::make('Password123')]);

        $this->assertNull(app(TenantIdentityService::class)->resolveForCredentials('arun3', 'Password123'));
    }

    public function test_malformed_legacy_password_hash_returns_a_generic_login_failure(): void
    {
        User::whereKey(1)->update(['password' => 'not-a-valid-password-hash']);

        $this->assertNull(app(TenantIdentityService::class)->resolveForCredentials('one@example.com', 'Password123'));
    }

    public function test_inactive_membership_cannot_be_resolved_for_login(): void
    {
        DB::table('tenant_user')->where('tenant_id', 2)->where('user_id', 2)->update(['is_active' => false]);

        $this->assertNull(app(TenantIdentityService::class)->resolve('tenant-two', 'arun3'));
    }

    public function test_pending_invitation_reserves_username_only_inside_its_tenant(): void
    {
        DB::table('tenant_user_invitations')->insert([
            'tenant_id' => 1,
            'user_id' => 2,
            'email' => 'two@example.com',
            'username' => 'pending_user',
            'status' => 'pending',
            'expires_at' => now()->addHour(),
        ]);

        $this->assertTrue(Validator::make(['username' => 'pending_user'], ['username' => [new UniqueTenantUsername(1)]])->fails());
        $this->assertTrue(Validator::make(['username' => 'pending_user'], ['username' => [new UniqueTenantUsername(2)]])->passes());
    }

    public function test_global_email_resolves_only_through_the_selected_active_membership(): void
    {
        DB::table('tenant_user')->insert([
            'tenant_id' => 2,
            'user_id' => 1,
            'username' => 'arun_one',
            'role' => 'staff',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(TenantIdentityService::class);
        $this->assertSame(1, $service->resolve(' TENANT-TWO ', 'ONE@EXAMPLE.COM')['user']->id);

        DB::table('tenant_user')->where('tenant_id', 2)->where('user_id', 1)->update(['is_active' => false]);
        $this->assertNull($service->resolve('tenant-two', 'one@example.com'));
        $this->assertSame(1, $service->resolve('tenant-one', 'one@example.com')['user']->id);
    }

    public function test_normal_email_login_opens_the_users_preferred_active_membership(): void
    {
        DB::table('tenant_user')->insert([
            'tenant_id' => 2,
            'user_id' => 1,
            'username' => 'one_tenant_two',
            'role' => 'staff',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resolved = app(TenantIdentityService::class)->resolveForCredentials('ONE@EXAMPLE.COM', 'Password123');

        $this->assertSame(1, $resolved['user']->id);
        $this->assertSame(1, $resolved['tenant']->id);
        $this->assertSame('arun3', $resolved['membership']->username);
    }

    public function test_inactive_tenant_and_globally_suspended_user_are_rejected(): void
    {
        $service = app(TenantIdentityService::class);
        Tenant::whereKey(1)->update(['is_active' => false]);
        $this->assertNull($service->resolve('tenant-one', 'arun3'));

        Tenant::whereKey(1)->update(['is_active' => true]);
        User::whereKey(1)->update(['is_active' => false]);
        $this->assertNull($service->resolve('tenant-one', 'arun3'));
    }

    public function test_platform_code_accepts_only_active_super_admin_email(): void
    {
        $platform = User::create([
            'name' => 'Platform Admin',
            'email' => 'platform@example.com',
            'password' => 'Password789',
            'is_active' => true,
            'is_super_admin' => true,
        ]);
        DB::table('tenant_user')->insert([
            'tenant_id' => 1,
            'user_id' => $platform->id,
            'username' => 'platform_admin',
            'role' => 'admin',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(TenantIdentityService::class);
        $this->assertNotNull($service->resolve(' PLATFORM ', 'PLATFORM@EXAMPLE.COM'));
        $this->assertNull($service->resolveForCredentials('platform@example.com', 'Password789')['tenant']);
        $this->assertNull($service->resolve('platform', 'platform_admin'));
        $this->assertNull($service->resolve('platform', 'one@example.com'));
    }

    public function test_scoped_email_allows_multiple_tenants_to_have_same_base_email(): void
    {
        $membershipService = app(\App\Services\TenantMembershipService::class);
        $tenantOne = Tenant::find(1);
        $tenantTwo = Tenant::find(2);

        $inviter = User::find(1);

        $res1 = $membershipService->createOrInvite($tenantOne, [
            'name' => 'Kavitha Staff One',
            'email' => 'kavitha@example.com',
            'username' => 'kavitha',
            'password' => 'SecretPass1',
            'role' => 'staff',
        ], $inviter);

        $res2 = $membershipService->createOrInvite($tenantTwo, [
            'name' => 'Kavitha Staff Two',
            'email' => 'kavitha@example.com',
            'username' => 'kavitha',
            'password' => 'SecretPass2',
            'role' => 'staff',
        ], $inviter);

        $this->assertEquals('created', $res1['status']);
        $this->assertEquals('created', $res2['status']);
        $this->assertEquals('kavitha.tenant-one@example.com', $res1['user']->email);
        $this->assertEquals('kavitha.tenant-two@example.com', $res2['user']->email);

        $identityService = app(TenantIdentityService::class);

        // Login using raw email and Tenant One's password resolves Tenant One!
        $resolvedOne = $identityService->resolveForCredentials('kavitha@example.com', 'SecretPass1');
        $this->assertNotNull($resolvedOne);
        $this->assertEquals($tenantOne->id, $resolvedOne['tenant']->id);
        $this->assertEquals($res1['user']->id, $resolvedOne['user']->id);

        // Login using raw email and Tenant Two's password resolves Tenant Two!
        $resolvedTwo = $identityService->resolveForCredentials('kavitha@example.com', 'SecretPass2');
        $this->assertNotNull($resolvedTwo);
        $this->assertEquals($tenantTwo->id, $resolvedTwo['tenant']->id);
        $this->assertEquals($res2['user']->id, $resolvedTwo['user']->id);

        // Login using scoped email
        $resolvedScoped = $identityService->resolveForCredentials('kavitha.tenant-one@example.com', 'SecretPass1');
        $this->assertNotNull($resolvedScoped);
        $this->assertEquals($tenantOne->id, $resolvedScoped['tenant']->id);

        // Update password for user 1 via membership service
        $membershipService->updateMembership($tenantOne, $res1['user'], [
            'username' => 'kavitha',
            'role' => 'staff',
            'password' => 'BrandNewPassword123',
        ]);

        $resolvedAfterReset = $identityService->resolveForCredentials('kavitha@example.com', 'BrandNewPassword123');
        $this->assertNotNull($resolvedAfterReset);
        $this->assertEquals($tenantOne->id, $resolvedAfterReset['tenant']->id);
    }
}
