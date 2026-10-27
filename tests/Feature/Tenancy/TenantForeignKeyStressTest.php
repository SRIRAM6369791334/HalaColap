<?php

namespace Tests\Feature\Tenancy;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

/**
 * Empirical Stress Test Suite for Milestone 2:
 * Foreign Key Cascading and Invalid Tenant ID Rejection
 *
 * Authored by Challenger M2-2.
 */
class TenantForeignKeyStressTest extends TestCase
{
    /**
     * The 39 business tables partitioned by tenant_id.
     *
     * @var array<string>
     */
    protected array $businessTables = [
        'activity_logs',
        'batches',
        'bird_batches',
        'capital_transactions',
        'cash_bank_ledgers',
        'customers',
        'customer_payments',
        'daily_bills',
        'daily_bill_items',
        'day_load_batches',
        'day_load_entries',
        'day_load_invoices',
        'dealers',
        'dealer_payments',
        'dealer_purchases',
        'dealer_purchase_items',
        'drivers',
        'emis',
        'entry_adjustment_logs',
        'expenses',
        'expense_categories',
        'items',
        'poultry_consumptions',
        'poultry_mortalities',
        'payment_adjustment_logs',
        'purchases',
        'purchase_items',
        'routes',
        'stock_items',
        'stock_ledgers',
        'stock_transactions',
        'vehicles',
        'vendor_advances',
        'vendor_advance_adjustments',
        'vendor_payments',
        'vendors',
        'warehouses',
        'weekly_bills',
        'weekly_bill_items',
    ];

    protected array $createdTenantIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'poultry_db',
        ]);
        DB::purge();
        DB::setDefaultConnection('mysql');
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdTenantIds)) {
            foreach ($this->createdTenantIds as $tid) {
                // Ensure dependent records in stock_ledgers are cleaned up to prevent lockups
                try {
                    DB::table('stock_ledgers')->where('tenant_id', $tid)->delete();
                    DB::table('batches')->where('tenant_id', $tid)->delete();
                    DB::table('items')->where('tenant_id', $tid)->delete();
                    DB::table('warehouses')->where('tenant_id', $tid)->delete();
                    DB::table('daily_bill_items')->where('tenant_id', $tid)->delete();
                    DB::table('daily_bills')->where('tenant_id', $tid)->delete();
                    DB::table('customers')->where('tenant_id', $tid)->delete();
                    DB::table('weekly_bill_items')->where('tenant_id', $tid)->delete();
                    DB::table('weekly_bills')->where('tenant_id', $tid)->delete();
                    DB::table('payment_adjustment_logs')->where('tenant_id', $tid)->delete();
                    DB::table('dealer_payments')->where('tenant_id', $tid)->delete();
                    DB::table('dealers')->where('tenant_id', $tid)->delete();
                    DB::table('purchase_items')->where('tenant_id', $tid)->delete();
                    DB::table('purchases')->where('tenant_id', $tid)->delete();
                    DB::table('vendors')->where('tenant_id', $tid)->delete();
                    DB::table('tenants')->where('id', $tid)->delete();
                } catch (\Throwable $e) {}
            }
        }

        parent::tearDown();
    }

    protected function createTemporaryTenant(string $label = 'Test'): int
    {
        $id = DB::table('tenants')->insertGetId([
            'name' => "Tenant {$label} " . uniqid(),
            'slug' => "tenant-" . strtolower($label) . "-" . uniqid(),
            'company_name' => "Company {$label}",
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->createdTenantIds[] = $id;
        return $id;
    }

    /**
     * Requirement 2: Attempt inserting child records with non-existent tenant_id,
     * asserting foreign key constraint rejection across all 39 business tables.
     */
    public function test_invalid_tenant_id_rejected_across_all_39_business_tables(): void
    {
        $nonExistentTenantId = 99999999;
        $rejectedCount = 0;

        foreach ($this->businessTables as $table) {
            $columns = DB::select("SHOW COLUMNS FROM `{$table}`");
            $row = ['tenant_id' => $nonExistentTenantId];

            foreach ($columns as $col) {
                $field = $col->Field;
                if ($field === 'id' || $field === 'tenant_id') {
                    continue;
                }

                if ($col->Null === 'NO' && $col->Default === null) {
                    $type = strtolower($col->Type);
                    if (str_starts_with($type, 'enum(')) {
                        if (preg_match("/enum\('([^']+)'/", $col->Type, $matches)) {
                            $row[$field] = $matches[1];
                        } else {
                            $row[$field] = 'default';
                        }
                    } elseif (str_contains($type, 'int') || str_contains($type, 'bigint') || str_contains($type, 'tinyint')) {
                        $row[$field] = 1;
                    } elseif (str_contains($type, 'decimal') || str_contains($type, 'float') || str_contains($type, 'double')) {
                        $row[$field] = 10.0;
                    } elseif (str_contains($type, 'date')) {
                        $row[$field] = '2026-10-09';
                    } elseif (str_contains($type, 'timestamp') || str_contains($type, 'datetime')) {
                        $row[$field] = '2026-10-09 12:00:00';
                    } elseif (str_contains($type, 'json')) {
                        $row[$field] = '{}';
                    } else {
                        $row[$field] = 'test-' . uniqid();
                    }
                }
            }

            try {
                DB::table($table)->insert($row);
                $this->fail("Table {$table} allowed inserting child record with non-existent tenant_id {$nonExistentTenantId}");
            } catch (QueryException $e) {
                $message = $e->getMessage();
                $isFkFailure = str_contains($message, 'foreign key constraint fails') || str_contains($message, '1452');
                $this->assertTrue(
                    $isFkFailure,
                    "Table {$table} insert failed but not with foreign key rejection: {$message}"
                );
                $rejectedCount++;
            }
        }

        $this->assertEquals(
            39,
            $rejectedCount,
            'All 39 business tables must enforce foreign key rejection on invalid tenant_id'
        );
    }

    /**
     * Requirement 1 (Part A): Clean cascading deletion on independent business tables.
     */
    public function test_tenant_clean_cascade_on_independent_tables(): void
    {
        $tenantId = $this->createTemporaryTenant('Independent');

        $catId = DB::table('expense_categories')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Cat ' . uniqid(),
            'color' => '#10B981',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $dealerId = DB::table('dealers')->insertGetId([
            'tenant_id' => $tenantId,
            'firm_name' => 'Dealer ' . uniqid(),
            'phone' => '9876543210',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $custId = DB::table('customers')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Customer ' . uniqid(),
            'phone' => '9876543211',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $whId = DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'WH ' . uniqid(),
            'location' => 'Zone 1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $vehId = DB::table('vehicles')->insertGetId([
            'tenant_id' => $tenantId,
            'vehicle_number' => 'TN-' . rand(10, 99) . '-' . rand(1000, 9999),
            'vehicle_type' => 'Tata Ace',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Delete tenant
        DB::table('tenants')->where('id', $tenantId)->delete();

        // Assert all child records deleted via ON DELETE CASCADE
        $this->assertEquals(0, DB::table('expense_categories')->where('id', $catId)->count());
        $this->assertEquals(0, DB::table('dealers')->where('id', $dealerId)->count());
        $this->assertEquals(0, DB::table('customers')->where('id', $custId)->count());
        $this->assertEquals(0, DB::table('warehouses')->where('id', $whId)->count());
        $this->assertEquals(0, DB::table('vehicles')->where('id', $vehId)->count());
    }

    /**
     * Requirement 1 (Part B): Cascading deletion on hierarchical business chains.
     */
    public function test_tenant_clean_cascade_on_hierarchical_business_chains(): void
    {
        $tenantId = $this->createTemporaryTenant('Hierarchical');

        // Chain 1: Customers -> Daily Bills -> Daily Bill Items
        $cust = DB::table('customers')->insertGetId(['tenant_id' => $tenantId, 'name' => 'Cust ' . uniqid(), 'phone' => '9876543220', 'created_at' => now(), 'updated_at' => now()]);
        $bill = DB::table('daily_bills')->insertGetId(['tenant_id' => $tenantId, 'customer_id' => $cust, 'invoice_no' => 'DB-' . uniqid(), 'date' => now()->toDateString(), 'amount' => 100, 'net_amount' => 100, 'status' => 'pending', 'created_at' => now(), 'updated_at' => now()]);
        $billItem = DB::table('daily_bill_items')->insertGetId(['tenant_id' => $tenantId, 'daily_bill_id' => $bill, 'item_name' => 'Birds', 'quantity_kg' => 10, 'rate_per_kg' => 10, 'total_amount' => 100, 'created_at' => now(), 'updated_at' => now()]);

        // Chain 2: Dealers -> Weekly Bills -> Weekly Bill Items
        $dealer = DB::table('dealers')->insertGetId(['tenant_id' => $tenantId, 'firm_name' => 'Dealer ' . uniqid(), 'phone' => '9876543221', 'created_at' => now(), 'updated_at' => now()]);
        $wbill = DB::table('weekly_bills')->insertGetId(['tenant_id' => $tenantId, 'dealer_id' => $dealer, 'invoice_no' => 'WB-' . uniqid(), 'period_start' => now()->toDateString(), 'period_end' => now()->toDateString(), 'amount' => 200, 'net_amount' => 200, 'status' => 'Pending', 'created_at' => now(), 'updated_at' => now()]);
        $wbillItem = DB::table('weekly_bill_items')->insertGetId(['tenant_id' => $tenantId, 'weekly_bill_id' => $wbill, 'item_name' => 'Broiler', 'quantity_kg' => 20, 'rate_per_kg' => 10, 'total_amount' => 200, 'created_at' => now(), 'updated_at' => now()]);

        // Chain 3: Vendors -> Purchases -> Purchase Items
        $vendor = DB::table('vendors')->insertGetId(['tenant_id' => $tenantId, 'firm_name' => 'Vendor ' . uniqid(), 'phone' => '9876543222', 'created_at' => now(), 'updated_at' => now()]);
        $purch = DB::table('purchases')->insertGetId(['tenant_id' => $tenantId, 'vendor_id' => $vendor, 'vendor_name' => 'Vendor Test', 'date' => now()->toDateString(), 'total_amount' => 300, 'payment_mode' => 'Cash', 'created_at' => now(), 'updated_at' => now()]);
        $pItem = DB::table('purchase_items')->insertGetId(['tenant_id' => $tenantId, 'purchase_id' => $purch, 'item_name' => 'Feed Bag', 'quantity' => 10, 'unit' => 'bag', 'rate' => 30, 'total_amount' => 300, 'created_at' => now(), 'updated_at' => now()]);

        // Chain 4: Dealers -> Dealer Payments -> Payment Adjustment Logs
        $pay = DB::table('dealer_payments')->insertGetId(['tenant_id' => $tenantId, 'dealer_id' => $dealer, 'date' => now()->toDateString(), 'amount' => 400, 'payment_mode' => 'Cash', 'created_at' => now(), 'updated_at' => now()]);
        $adj = DB::table('payment_adjustment_logs')->insertGetId(['tenant_id' => $tenantId, 'payment_id' => $pay, 'action_type' => 'adjustment', 'reason' => 'test correction', 'created_at' => now()]);

        // Chain 5: Batches -> Poultry Mortalities
        $batch = DB::table('batches')->insertGetId(['tenant_id' => $tenantId, 'batch_code' => 'B-' . uniqid(), 'placement_date' => now()->toDateString(), 'initial_count' => 500, 'current_count' => 500, 'status' => 'Active', 'created_at' => now(), 'updated_at' => now()]);
        $mort = DB::table('poultry_mortalities')->insertGetId(['tenant_id' => $tenantId, 'batch_id' => $batch, 'date' => now()->toDateString(), 'count' => 5, 'created_at' => now(), 'updated_at' => now()]);

        // Delete parent tenant
        DB::table('tenants')->where('id', $tenantId)->delete();

        // Assert all cascading deletions completed cleanly
        $this->assertEquals(0, DB::table('daily_bill_items')->where('id', $billItem)->count());
        $this->assertEquals(0, DB::table('daily_bills')->where('id', $bill)->count());
        $this->assertEquals(0, DB::table('customers')->where('id', $cust)->count());

        $this->assertEquals(0, DB::table('weekly_bill_items')->where('id', $wbillItem)->count());
        $this->assertEquals(0, DB::table('weekly_bills')->where('id', $wbill)->count());
        $this->assertEquals(0, DB::table('dealers')->where('id', $dealer)->count());

        $this->assertEquals(0, DB::table('purchase_items')->where('id', $pItem)->count());
        $this->assertEquals(0, DB::table('purchases')->where('id', $purch)->count());
        $this->assertEquals(0, DB::table('vendors')->where('id', $vendor)->count());

        $this->assertEquals(0, DB::table('payment_adjustment_logs')->where('id', $adj)->count());
        $this->assertEquals(0, DB::table('dealer_payments')->where('id', $pay)->count());

        $this->assertEquals(0, DB::table('poultry_mortalities')->where('id', $mort)->count());
        $this->assertEquals(0, DB::table('batches')->where('id', $batch)->count());
    }

    /**
     * Requirement 1 (Part C - Cascade Verification on stock_ledgers):
     * Asserts that when a tenant is deleted, all records in stock_ledgers, batches,
     * items, and warehouses are cleanly cascade-deleted with zero Error 1451 exceptions.
     */
    public function test_tenant_cascade_succeeds_when_stock_ledgers_exist_with_cascading_foreign_keys(): void
    {
        $tenantId = $this->createTemporaryTenant('StockCascade');

        $itemId = DB::table('items')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Feed ' . uniqid(),
            'code' => 'FEED-' . uniqid(),
            'type' => 'feed',
            'base_unit' => 'kg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $batchId = DB::table('batches')->insertGetId([
            'tenant_id' => $tenantId,
            'batch_code' => 'BATCH-' . uniqid(),
            'placement_date' => now()->toDateString(),
            'initial_count' => 100,
            'current_count' => 100,
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $whId = DB::table('warehouses')->insertGetId([
            'tenant_id' => $tenantId,
            'name' => 'Warehouse ' . uniqid(),
            'location' => 'Loc',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ledgerId = DB::table('stock_ledgers')->insertGetId([
            'tenant_id' => $tenantId,
            'transaction_date' => now()->toDateString(),
            'item_id' => $itemId,
            'batch_id' => $batchId,
            'warehouse_id' => $whId,
            'type' => 'IN',
            'source_type' => 'manual',
            'source_id' => 1,
            'unit' => 'kg',
            'quantity' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Deleting the tenant must cleanly cascade delete all records without Error 1451
        DB::table('tenants')->where('id', $tenantId)->delete();

        // Assert clean cascade deletion across all related entities
        $this->assertEquals(0, DB::table('stock_ledgers')->where('id', $ledgerId)->count(), 'stock_ledgers record must be cascade deleted');
        $this->assertEquals(0, DB::table('batches')->where('id', $batchId)->count(), 'batches record must be cascade deleted');
        $this->assertEquals(0, DB::table('items')->where('id', $itemId)->count(), 'items record must be cascade deleted');
        $this->assertEquals(0, DB::table('warehouses')->where('id', $whId)->count(), 'warehouses record must be cascade deleted');
        $this->assertEquals(0, DB::table('tenants')->where('id', $tenantId)->count(), 'tenants record must be deleted');
    }
}
