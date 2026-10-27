<?php

namespace Tests\Tier2;

use Illuminate\Support\Facades\DB;
use Tests\E2ETestCase;

/**
 * Feature F03 Boundary Tests: Core Multi-Tenancy Tables
 * Verifies boundaries on slug lengths, UTF-8 strings, null states, and boolean flags.
 */
class F03_CoreMultiTenancyTablesBoundaryTest extends E2ETestCase
{
    public function test_tenant_creation_with_maximum_length_slug(): void
    {
        $this->requiresTable('tenants');
        $slug = str_repeat('a', 100);

        $id = DB::table('tenants')->insertGetId([
            'name'       => 'Max Slug Farm',
            'slug'       => $slug,
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNotNull($id);
        $saved = DB::table('tenants')->find($id);
        $this->assertEquals($slug, $saved->slug);
    }

    public function test_tenant_creation_with_special_characters_in_name(): void
    {
        $this->requiresTable('tenants');
        $specialName = "Al-Barakah & Son's Broilers (Branch #2) - சிறப்பு பண்ணை";

        $id = DB::table('tenants')->insertGetId([
            'name'       => $specialName,
            'slug'       => 'al-barakah-branch-2',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $saved = DB::table('tenants')->find($id);
        $this->assertEquals($specialName, $saved->name);
    }

    public function test_tenant_with_inactive_status(): void
    {
        $this->requiresTable('tenants');
        $id = DB::table('tenants')->insertGetId([
            'name'       => 'Suspended Poultry Co',
            'slug'       => 'suspended-co',
            'is_active'  => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $saved = DB::table('tenants')->find($id);
        $this->assertFalse((bool)$saved->is_active);
    }

    public function test_user_with_null_current_tenant_id(): void
    {
        $this->requiresColumn('users', 'current_tenant_id');

        $user = $this->createUser(['current_tenant_id' => null]);
        $this->assertNull($user->current_tenant_id);
    }

    public function test_reseller_with_null_optional_fields(): void
    {
        $this->requiresTable('resellers');

        $id = DB::table('resellers')->insertGetId([
            'name'       => 'Minimal Reseller',
            'email'      => 'reseller@test.com',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertNotNull($id);
    }
}
