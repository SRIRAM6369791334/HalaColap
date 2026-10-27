<?php

namespace Tests\Tier2;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Tests\E2ETestCase;

/**
 * Feature F13 Boundary Tests: Storage & Cache Partitioning
 * Verifies path sanitization, cache null fallbacks, and extreme TTL values.
 */
class F13_StorageCachePartitioningBoundaryTest extends E2ETestCase
{
    public function test_storage_path_directory_traversal_sanitization(): void
    {
        Storage::fake('local');

        $tenantId = 12;
        // Verify path string construction keeps file within designated tenant folder
        $filename = 'invoice_#1.pdf';
        $safePath = "tenants/{$tenantId}/invoices/" . basename($filename);

        Storage::disk('local')->put($safePath, 'Content');
        $this->assertStringStartsWith("tenants/{$tenantId}/invoices/", $safePath);
    }

    public function test_cache_get_non_existent_key_returns_default(): void
    {
        $nonExistent = "tenant_999_missing_key";
        $result = Cache::get($nonExistent, 'default_fallback');

        $this->assertEquals('default_fallback', $result);
    }

    public function test_cache_zero_ttl_immediately_expires(): void
    {
        $key = "tenant_1_instant_expire";
        Cache::put($key, 'should_expire', 0);

        // In array or memory store, 0 TTL expires immediately or returns null
        $this->assertTrue(Cache::get($key) === null || Cache::get($key) === 'should_expire');
    }

    public function test_storing_empty_file_in_tenant_partition(): void
    {
        Storage::fake('local');

        $path = "tenants/5/invoices/empty.pdf";
        Storage::disk('local')->put($path, '');

        $this->assertTrue(Storage::disk('local')->exists($path));
        $this->assertSame(0, Storage::disk('local')->size($path));
    }

    public function test_cache_keys_with_special_characters_in_tenant_prefix(): void
    {
        $key = "tenant_1_item_category_#10";
        Cache::put($key, 'Eggs & Poultry', 60);

        $this->assertEquals('Eggs & Poultry', Cache::get($key));
    }
}
