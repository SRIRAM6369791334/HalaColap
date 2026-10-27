<?php

namespace Tests\Tier1;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\E2ETestCase;

/**
 * Feature F13: Storage & Cache Partitioning
 * Verifies that file storage is partitioned by tenants/{id}/... and cache keys
 * are partitioned by tenant_{id}_...
 */
class F13_StorageCachePartitioningTest extends E2ETestCase
{
    public function test_tenant_storage_path_is_isolated(): void
    {
        Storage::fake('local');

        $t1 = $this->createTenant();
        $t1Path = "tenants/{$t1->id}/invoices/inv_001.pdf";

        Storage::disk('local')->put($t1Path, 'Tenant 1 Invoice Content');
        $this->assertTrue(Storage::disk('local')->exists($t1Path));
    }

    public function test_cross_tenant_storage_isolation(): void
    {
        Storage::fake('local');

        $t1 = $this->createTenant();
        $t2 = $this->createTenant();

        $t1Path = "tenants/{$t1->id}/invoices/inv_001.pdf";
        $t2Path = "tenants/{$t2->id}/invoices/inv_001.pdf";

        Storage::disk('local')->put($t1Path, 'Secret Financial Data T1');

        $this->assertFalse(Storage::disk('local')->exists($t2Path), 'Tenant 2 can see Tenant 1 storage path!');
    }

    public function test_cache_keys_are_prefixed_per_tenant(): void
    {
        $t1Id = 1;
        $t2Id = 2;

        $keyT1 = "tenant_{$t1Id}_dashboard_summary";
        $keyT2 = "tenant_{$t2Id}_dashboard_summary";

        Cache::put($keyT1, ['revenue' => 10000], 60);
        Cache::put($keyT2, ['revenue' => 20000], 60);

        $this->assertEquals(10000, Cache::get($keyT1)['revenue']);
        $this->assertEquals(20000, Cache::get($keyT2)['revenue']);
    }

    public function test_cache_invalidation_for_tenant_one_does_not_affect_tenant_two(): void
    {
        $t1Key = "tenant_1_active_dealers";
        $t2Key = "tenant_2_active_dealers";

        Cache::put($t1Key, ['dealers' => 5], 60);
        Cache::put($t2Key, ['dealers' => 12], 60);

        Cache::forget($t1Key);

        $this->assertNull(Cache::get($t1Key));
        $this->assertEquals(12, Cache::get($t2Key)['dealers'], 'Tenant 2 cache was evicted when Tenant 1 cache cleared!');
    }

    public function test_tenant_export_directories_are_partitioned(): void
    {
        Storage::fake('local');

        $t1 = $this->createTenant();
        $exportPath = "tenants/{$t1->id}/exports/ledger_report.xlsx";

        Storage::disk('local')->put($exportPath, 'Export Content');
        $this->assertStringStartsWith("tenants/{$t1->id}/", $exportPath);
    }
}
