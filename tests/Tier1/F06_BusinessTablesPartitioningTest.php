<?php

namespace Tests\Tier1;

use Illuminate\Support\Facades\Schema;
use Tests\E2ETestCase;

/**
 * Feature F06: Additive Business Tables Partitioning
 * Verifies that business tables contain tenant_id foreign keys.
 */
class F06_BusinessTablesPartitioningTest extends E2ETestCase
{
    public function test_dealers_table_has_tenant_id_column(): void
    {
        $this->requiresColumn('dealers', 'tenant_id');
        $this->assertTrue(Schema::hasColumn('dealers', 'tenant_id'));
    }

    public function test_vendors_table_has_tenant_id_column(): void
    {
        $this->requiresColumn('vendors', 'tenant_id');
        $this->assertTrue(Schema::hasColumn('vendors', 'tenant_id'));
    }

    public function test_day_load_entries_table_has_tenant_id_column(): void
    {
        $this->requiresColumn('day_load_entries', 'tenant_id');
        $this->assertTrue(Schema::hasColumn('day_load_entries', 'tenant_id'));
    }

    public function test_cash_bank_ledgers_table_has_tenant_id_column(): void
    {
        $this->requiresColumn('cash_bank_ledgers', 'tenant_id');
        $this->assertTrue(Schema::hasColumn('cash_bank_ledgers', 'tenant_id'));
    }

    public function test_day_load_invoices_table_has_tenant_id_column(): void
    {
        $this->requiresColumn('day_load_invoices', 'tenant_id');
        $this->assertTrue(Schema::hasColumn('day_load_invoices', 'tenant_id'));
    }
}
