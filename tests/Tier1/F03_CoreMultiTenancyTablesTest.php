<?php

namespace Tests\Tier1;

use Illuminate\Support\Facades\Schema;
use Tests\E2ETestCase;

/**
 * Feature F03: Core Multi-Tenancy Tables
 * Verifies the existence and structure of tenants, resellers, tenant_user,
 * and tenant-aware user columns.
 */
class F03_CoreMultiTenancyTablesTest extends E2ETestCase
{
    public function test_tenants_table_exists_with_required_columns(): void
    {
        $this->requiresTable('tenants');
        $this->assertTrue(Schema::hasColumn('tenants', 'id'));
        $this->assertTrue(Schema::hasColumn('tenants', 'name'));
        $this->assertTrue(Schema::hasColumn('tenants', 'slug'));
        $this->assertTrue(Schema::hasColumn('tenants', 'is_active'));
    }

    public function test_resellers_table_exists_with_required_columns(): void
    {
        $this->requiresTable('resellers');
        $this->assertTrue(Schema::hasColumn('resellers', 'id'));
        $this->assertTrue(Schema::hasColumn('resellers', 'name'));
    }

    public function test_tenant_user_pivot_table_exists_with_keys(): void
    {
        $this->requiresTable('tenant_user');
        $this->assertTrue(Schema::hasColumn('tenant_user', 'tenant_id'));
        $this->assertTrue(Schema::hasColumn('tenant_user', 'user_id'));
    }

    public function test_users_table_has_current_tenant_id_column(): void
    {
        $this->requiresColumn('users', 'current_tenant_id');
        $this->assertTrue(Schema::hasColumn('users', 'current_tenant_id'));
    }

    public function test_users_table_has_is_super_admin_column(): void
    {
        $this->requiresColumn('users', 'is_super_admin');
        $this->assertTrue(Schema::hasColumn('users', 'is_super_admin'));
    }
}
