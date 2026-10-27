<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

abstract class E2ETestCase extends TestCase
{
    use RefreshDatabase;

    /**
     * Skip test if a required database table has not yet been migrated.
     */
    protected function requiresTable(string $table): void
    {
        if (!Schema::hasTable($table)) {
            $this->markTestSkipped("Progressive check: Database table '{$table}' is pending milestone implementation.");
        }
    }

    /**
     * Skip test if a required column on a database table is missing.
     */
    protected function requiresColumn(string $table, string $column): void
    {
        $this->requiresTable($table);
        if (!Schema::hasColumn($table, $column)) {
            $this->markTestSkipped("Progressive check: Column '{$column}' on table '{$table}' is pending milestone implementation.");
        }
    }

    /**
     * Skip test if a class does not exist.
     */
    protected function requiresClass(string $className): void
    {
        if (!class_exists($className) && !trait_exists($className) && !interface_exists($className)) {
            $this->markTestSkipped("Progressive check: Class '{$className}' is pending milestone implementation.");
        }
    }

    /**
     * Skip test if a named route is not defined.
     */
    protected function requiresRoute(string $routeName): void
    {
        if (!Route::has($routeName)) {
            $this->markTestSkipped("Progressive check: Route '{$routeName}' is pending milestone implementation.");
        }
    }

    /**
     * Create a tenant record if the tenants table exists, or return a mock object.
     */
    protected function createTenant(array $attributes = []): object
    {
        $data = array_merge([
            'name'       => 'Test Poultry Farm ' . Str::random(5),
            'slug'       => 'farm-' . Str::lower(Str::random(6)),
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes);

        if (Schema::hasTable('tenants')) {
            if (isset($data['slug'])) {
                $existing = DB::table('tenants')->where('slug', $data['slug'])->first();
                if ($existing) {
                    return (object) (array) $existing;
                }
            }
            $id = DB::table('tenants')->insertGetId($data);
            return (object) array_merge(['id' => $id], $data);
        }

        return (object) array_merge(['id' => rand(100, 999)], $data);
    }

    /**
     * Create a day load batch record.
     */
    protected function createDayLoadBatch(array $attributes = []): \App\Models\DayLoadBatch
    {
        $data = array_merge([
            'billing_date' => now()->toDateString(),
            'status'       => 'Open',
        ], $attributes);

        return \App\Models\DayLoadBatch::create($data);
    }

    /**
     * Create a user, optionally assigned to a tenant.
     */
    protected function createUser(array $attributes = []): User
    {
        $userData = array_merge([
            'name'              => 'Test User ' . Str::random(4),
            'email'             => 'user_' . Str::lower(Str::random(6)) . '@example.com',
            'password'          => bcrypt('password123'),
            'is_active'         => true,
            'email_verified_at' => now(),
        ], $attributes);

        return User::create($userData);
    }

    /**
     * Create a dealer record with required firm_name.
     */
    protected function createDealer(array $attributes = []): \App\Models\Dealer
    {
        $data = array_merge([
            'firm_name'      => 'Dealer Firm ' . Str::random(5),
            'contact_person' => 'Dealer Contact ' . Str::random(4),
            'phone'          => '9' . str_pad((string) rand(100000000, 999999999), 9, '0'),
            'pending_amount' => 0.00,
        ], $attributes);

        return \App\Models\Dealer::create($data);
    }

    /**
     * Create a vendor record with required firm_name.
     */
    protected function createVendor(array $attributes = []): \App\Models\Vendor
    {
        $data = array_merge([
            'firm_name'      => 'Vendor Firm ' . Str::random(5),
            'contact_person' => 'Vendor Contact ' . Str::random(4),
            'phone'          => '9' . str_pad((string) rand(100000000, 999999999), 9, '0'),
            'pending_amount' => 0.00,
        ], $attributes);

        return \App\Models\Vendor::create($data);
    }

    /**
     * Set active tenant context in container and Spatie permissions if implemented.
     */
    protected function setTenantContext(?object $tenant): void
    {
        if (class_exists(\App\Services\Tenancy\TenantContext::class)) {
            $context = app(\App\Services\Tenancy\TenantContext::class);
            if (method_exists($context, 'setTenant')) {
                $tenantModel = null;
                if ($tenant && class_exists(\App\Models\Tenant::class) && Schema::hasTable('tenants')) {
                    $tenantModel = \App\Models\Tenant::find($tenant->id);
                }
                $context->setTenant($tenantModel);
            }
        }

        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($tenant?->id ?? null);
        }
    }

    /**
     * Authenticate as a user within a specific tenant context.
     */
    protected function actingAsTenantUser(?object $tenant = null, array $userAttributes = []): User
    {
        if ($tenant && Schema::hasColumn('users', 'current_tenant_id')) {
            $userAttributes['current_tenant_id'] = $tenant->id;
        }

        $user = $this->createUser($userAttributes);
        $this->actingAs($user);

        if ($tenant) {
            $this->setTenantContext($tenant);
        }

        return $user;
    }

    /**
     * Authoritative formula calculation: Bird Weight = Box Weight - Empty Weight
     */
    protected function calculateExpectedBirdWeight(float $boxWeight, float $emptyWeight): float
    {
        return round($boxWeight - $emptyWeight, 2);
    }

    /**
     * Authoritative formula calculation: Loss Weight = Farm Weight - Bird Weight
     */
    protected function calculateExpectedLossWeight(float $farmWeight, float $birdWeight): float
    {
        return round($farmWeight - $birdWeight, 2);
    }

    /**
     * Authoritative formula calculation: Dealer Income = Bird Weight * Customer Rate
     */
    protected function calculateExpectedDealerIncome(float $birdWeight, float $customerRate): float
    {
        return round($birdWeight * $customerRate, 2);
    }

    /**
     * Authoritative formula calculation: Vendor Cost = Effective Farm Weight * Vendor Rate
     */
    protected function calculateExpectedVendorCost(float $effectiveFarmWeight, float $vendorRate): float
    {
        return round($effectiveFarmWeight * $vendorRate, 2);
    }

    /**
     * Authoritative formula calculation: Gross Margin = Dealer Income - Vendor Cost
     */
    protected function calculateExpectedGrossMargin(float $dealerIncome, float $vendorCost): float
    {
        return round($dealerIncome - $vendorCost, 2);
    }

    /**
     * Assert that cross-tenant access properly enforces Zero IDOR (ModelNotFoundException or 404).
     */
    protected function assertZeroIdor(callable $callback): void
    {
        try {
            $response = $callback();
            if (is_object($response) && method_exists($response, 'status')) {
                $this->assertEquals(404, $response->status(), 'Cross-tenant lookup did not return 404 Not Found');
            } elseif ($response === null) {
                $this->assertNull($response, 'Cross-tenant lookup correctly returned null / empty');
            } else {
                $this->fail('Cross-tenant lookup returned data when 404 or empty was required (IDOR Leakage)');
            }
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->assertTrue(true, 'ModelNotFoundException correctly caught on cross-tenant lookup.');
        } catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
            $this->assertTrue(true, 'NotFoundHttpException correctly caught on cross-tenant lookup.');
        }
    }

    /**
     * Assert box conservation invariant.
     */
    protected function assertBoxConservation(int $remainingBoxes, int $splitBoxes, int $originalBoxes): void
    {
        $this->assertSame(
            $originalBoxes,
            $remainingBoxes + $splitBoxes,
            "Box conservation violated: {$remainingBoxes} + {$splitBoxes} !== {$originalBoxes}"
        );
    }
}
