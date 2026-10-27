<?php

namespace Tests\Feature\Tenancy;

use Tests\TestCase;
use App\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Empirical Stress Test Suite for Milestone 2:
 * Cross-Tenant Coexistence & Composite Unique Constraints.
 *
 * Verifies that identical invoice numbers, ledger dates, batch codes,
 * and other business keys can coexist concurrently across multiple tenants,
 * while intra-tenant duplicates are strictly rejected by the database.
 */
class CrossTenantCoexistenceStressTest extends TestCase
{
    /**
     * Tracked test tenant IDs for guaranteed cleanup.
     *
     * @var array<int>
     */
    protected array $createdTenantIds = [];

    /**
     * Tracked records created in Tenant #1 (MSR Broilers) for cleanup.
     *
     * @var array<string, array<int>>
     */
    protected array $tenantOneCreatedRecords = [
        'cash_bank_ledgers'  => [],
        'daily_bills'        => [],
        'weekly_bills'       => [],
        'day_load_invoices'  => [],
        'day_load_batches'   => [],
        'dealer_purchases'   => [],
        'batches'            => [],
        'expense_categories' => [],
        'items'              => [],
        'stock_items'        => [],
        'vehicles'           => [],
        'dealers'            => [],
    ];

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
    }

    protected function tearDown(): void
    {
        // 1. Clean up any test records created directly under Tenant #1
        foreach ($this->tenantOneCreatedRecords as $table => $ids) {
            if (!empty($ids)) {
                DB::connection('mysql')->table($table)->whereIn('id', $ids)->delete();
            }
        }

        // 2. Clean up test tenants (cascades to all their child records)
        if (!empty($this->createdTenantIds)) {
            DB::connection('mysql')->table('tenants')->whereIn('id', $this->createdTenantIds)->delete();
        }

        parent::tearDown();
    }

    /**
     * Helper to create an isolated test tenant.
     */
    protected function createTestTenant(string $prefix = 'Test Tenant'): int
    {
        $id = DB::connection('mysql')->table('tenants')->insertGetId([
            'name'         => $prefix . ' ' . uniqid(),
            'slug'         => 'tenant-' . uniqid(),
            'company_name' => 'Test Company ' . uniqid(),
            'email'        => 'tenant_' . uniqid() . '@example.test',
            'currency'     => 'INR',
            'timezone'     => 'Asia/Kolkata',
            'date_format'  => 'd-m-Y',
            'is_active'    => 1,
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $this->createdTenantIds[] = $id;
        return $id;
    }

    /**
     * Helper to create a dealer for a tenant.
     */
    protected function createDealerForTenant(int $tenantId): int
    {
        $id = DB::connection('mysql')->table('dealers')->insertGetId([
            'tenant_id'      => $tenantId,
            'firm_name'      => 'Dealer Firm ' . uniqid(),
            'contact_person' => 'Contact ' . uniqid(),
            'phone'          => '9' . str_pad((string) rand(100000000, 999999999), 9, '0'),
            'pending_amount' => 0.00,
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);

        if ($tenantId === 1) {
            $this->tenantOneCreatedRecords['dealers'][] = $id;
        }

        return $id;
    }

    /**
     * Helper to create a day_load_batch for a tenant.
     */
    protected function createDayLoadBatchForTenant(int $tenantId): int
    {
        $id = DB::connection('mysql')->table('day_load_batches')->insertGetId([
            'tenant_id'    => $tenantId,
            'billing_date' => now()->toDateString(),
            'status'       => 'Open',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        if ($tenantId === 1) {
            $this->tenantOneCreatedRecords['day_load_batches'][] = $id;
        }

        return $id;
    }

    /**
     * Insert a record for one of the 10 composite unique constraint tables.
     */
    protected function insertCompositeEntity(string $table, int $tenantId, string $uniqueValue, array $extra = []): int
    {
        $data = ['tenant_id' => $tenantId];

        switch ($table) {
            case 'cash_bank_ledgers':
                $data = array_merge($data, [
                    'ledger_date'          => $uniqueValue,
                    'opening_cash_balance' => 100.00,
                    'opening_bank_balance' => 200.00,
                    'closing_cash_balance' => 300.00,
                    'closing_bank_balance' => 400.00,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ], $extra);
                break;

            case 'daily_bills':
                $data = array_merge($data, [
                    'invoice_no' => $uniqueValue,
                    'date'       => now()->toDateString(),
                    'amount'     => 1500.00,
                    'net_amount' => 1500.00,
                    'status'     => 'Pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $extra);
                break;

            case 'weekly_bills':
                $dealerId = $extra['dealer_id'] ?? $this->createDealerForTenant($tenantId);
                $data = array_merge($data, [
                    'dealer_id'    => $dealerId,
                    'invoice_no'   => $uniqueValue,
                    'period_start' => now()->subDays(7)->toDateString(),
                    'period_end'   => now()->toDateString(),
                    'amount'       => 5000.00,
                    'net_amount'   => 5000.00,
                    'status'       => 'Pending',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ], $extra);
                break;

            case 'day_load_invoices':
                $batchId = $extra['batch_id'] ?? $this->createDayLoadBatchForTenant($tenantId);
                $data = array_merge($data, [
                    'batch_id'     => $batchId,
                    'invoice_no'   => $uniqueValue,
                    'invoice_date' => now()->toDateString(),
                    'total_boxes'  => 10,
                    'status'       => 'Draft',
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ], $extra);
                break;

            case 'dealer_purchases':
                $dealerId = $extra['dealer_id'] ?? $this->createDealerForTenant($tenantId);
                $data = array_merge($data, [
                    'dealer_id'      => $dealerId,
                    'invoice_no'     => $uniqueValue,
                    'date'           => now()->toDateString(),
                    'amount'         => 2200.00,
                    'gst_percentage' => 0.00,
                    'gst_amount'     => 0.00,
                    'net_amount'     => 2200.00,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ], $extra);
                break;

            case 'batches':
                $data = array_merge($data, [
                    'batch_code'           => $uniqueValue,
                    'placement_date'       => now()->toDateString(),
                    'initial_count'        => 500,
                    'current_count'        => 500,
                    'breed'                => 'Cobb 500',
                    'avg_placement_weight' => 42.50,
                    'status'               => 'Active',
                    'created_at'           => now(),
                    'updated_at'           => now(),
                ], $extra);
                break;

            case 'expense_categories':
                $data = array_merge($data, [
                    'name'       => $uniqueValue,
                    'color'      => '#3B82F6',
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $extra);
                break;

            case 'items':
                $data = array_merge($data, [
                    'code'       => $uniqueValue,
                    'name'       => 'Item ' . $uniqueValue,
                    'type'       => 'Feed',
                    'category'   => 'Feed',
                    'base_unit'  => 'KG',
                    'is_active'  => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $extra);
                break;

            case 'stock_items':
                $data = array_merge($data, [
                    'item_name'     => $uniqueValue,
                    'category'      => 'General',
                    'unit'          => 'Unit',
                    'current_stock' => 50,
                    'reorder_level' => 5,
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ], $extra);
                break;

            case 'vehicles':
                $data = array_merge($data, [
                    'vehicle_number' => $uniqueValue,
                    'vehicle_type'   => 'Pickup',
                    'capacity'       => 2000.00,
                    'created_at'     => now(),
                    'updated_at'     => now(),
                ], $extra);
                break;

            default:
                throw new \InvalidArgumentException("Unsupported table: {$table}");
        }

        $id = DB::connection('mysql')->table($table)->insertGetId($data);

        if ($tenantId === 1 && isset($this->tenantOneCreatedRecords[$table])) {
            $this->tenantOneCreatedRecords[$table][] = $id;
        }

        return $id;
    }

    /**
     * Test 1: Cross-tenant coexistence across ALL 10 composite unique entities.
     * Assert that Tenant #1 and Tenant #2 can concurrently hold identical values
     * without any SQL constraint errors or collisions.
     */
    public function test_cross_tenant_coexistence_all_ten_composite_unique_entities(): void
    {
        $tenantTwoId = $this->createTestTenant('Coexistence Tenant 2');

        $testMatrix = [
            'cash_bank_ledgers'  => '2026-11-20',
            'daily_bills'        => 'INV-DAILY-COEXIST-' . uniqid(),
            'weekly_bills'       => 'INV-WEEKLY-COEXIST-' . uniqid(),
            'day_load_invoices'  => 'INV-DAYLOAD-COEXIST-' . uniqid(),
            'dealer_purchases'   => 'INV-PURCHASE-COEXIST-' . uniqid(),
            'batches'            => 'BATCH-COEXIST-' . uniqid(),
            'expense_categories' => 'Category Coexist ' . uniqid(),
            'items'              => 'ITEM-COEXIST-' . uniqid(),
            'stock_items'        => 'Stock Coexist ' . uniqid(),
            'vehicles'           => 'KA-04-TEST-' . rand(1000, 9999),
        ];

        foreach ($testMatrix as $table => $uniqueValue) {
            // 1. Insert for Tenant #1 (MSR Broilers)
            $id1 = $this->insertCompositeEntity($table, 1, $uniqueValue);
            $this->assertGreaterThan(0, $id1, "Tenant #1 insert failed on {$table}");

            // 2. Insert IDENTICAL unique value for Tenant #2
            $id2 = $this->insertCompositeEntity($table, $tenantTwoId, $uniqueValue);
            $this->assertGreaterThan(0, $id2, "Tenant #2 insert failed on {$table} with identical value: {$uniqueValue}");

            // 3. Both records must exist with distinct IDs and different tenant_id
            $this->assertNotEquals($id1, $id2, "IDs must be distinct for {$table}");

            $records = DB::connection('mysql')->table($table)->whereIn('id', [$id1, $id2])->get();
            $this->assertCount(2, $records, "Both records must exist in {$table}");

            $tenantIds = $records->pluck('tenant_id')->sort()->values()->toArray();
            $expectedTenantIds = [1, $tenantTwoId];
            sort($expectedTenantIds);
            $this->assertEquals($expectedTenantIds, $tenantIds, "Records in {$table} must belong to Tenant 1 and Tenant 2");
        }
    }

    /**
     * Test 2: Intra-tenant strict rejection of duplicates for ALL 10 composite unique entities.
     * Assert that attempting to insert an identical value within the same tenant throws QueryException.
     */
    public function test_intra_tenant_duplicate_rejection_all_ten_composite_unique_entities(): void
    {
        $tenantTwoId = $this->createTestTenant('Intra-Tenant Duplicate Tenant');

        $testMatrix = [
            'cash_bank_ledgers'  => '2026-12-15',
            'daily_bills'        => 'INV-DAILY-DUP-' . uniqid(),
            'weekly_bills'       => 'INV-WEEKLY-DUP-' . uniqid(),
            'day_load_invoices'  => 'INV-DAYLOAD-DUP-' . uniqid(),
            'dealer_purchases'   => 'INV-PURCHASE-DUP-' . uniqid(),
            'batches'            => 'BATCH-DUP-' . uniqid(),
            'expense_categories' => 'Category Dup ' . uniqid(),
            'items'              => 'ITEM-DUP-' . uniqid(),
            'stock_items'        => 'Stock Dup ' . uniqid(),
            'vehicles'           => 'KA-05-DUP-' . rand(1000, 9999),
        ];

        foreach ($testMatrix as $table => $uniqueValue) {
            // First insert under Tenant #1
            $this->insertCompositeEntity($table, 1, $uniqueValue);

            // Duplicate insert under Tenant #1 MUST fail with SQL constraint violation
            $tenantOneFailed = false;
            try {
                $this->insertCompositeEntity($table, 1, $uniqueValue);
            } catch (QueryException $e) {
                $tenantOneFailed = true;
                $this->assertMatchesRegularExpression('/Duplicate entry|1062|UNIQUE constraint/i', $e->getMessage());
            }
            $this->assertTrue($tenantOneFailed, "Intra-tenant duplicate under Tenant #1 on table {$table} must be rejected by database");

            // Also verify for Tenant #2: First insert succeeds, second duplicate fails
            $this->insertCompositeEntity($table, $tenantTwoId, $uniqueValue);

            $tenantTwoFailed = false;
            try {
                $this->insertCompositeEntity($table, $tenantTwoId, $uniqueValue);
            } catch (QueryException $e) {
                $tenantTwoFailed = true;
                $this->assertMatchesRegularExpression('/Duplicate entry|1062|UNIQUE constraint/i', $e->getMessage());
            }
            $this->assertTrue($tenantTwoFailed, "Intra-tenant duplicate under Tenant #2 on table {$table} must be rejected by database");
        }
    }

    /**
     * Test 3: High-concurrency interleaved insertion stress across 5 tenants.
     * Simulates concurrent multi-tenant ingestion with interleaved duplicate injection.
     */
    public function test_concurrent_interleaved_insertion_stress_across_multiple_tenants(): void
    {
        // Setup 5 tenants: Tenant 1 (baseline) + 4 new tenants
        $tenants = [1];
        for ($i = 2; $i <= 5; $i++) {
            $tenants[] = $this->createTestTenant("Concurrency Tenant {$i}");
        }

        $cycles = 15;

        for ($cycle = 1; $cycle <= $cycles; $cycle++) {
            $invoiceNo = "INV-STRESS-CYCLE-{$cycle}-" . uniqid();
            $batchCode = "BATCH-STRESS-CYCLE-{$cycle}-" . uniqid();
            $ledgerDate = '2027-03-' . str_pad((string) $cycle, 2, '0', STR_PAD_LEFT);

            // Interleaved inserts across all 5 tenants
            foreach ($tenants as $tenantId) {
                // Insert daily bill
                $this->insertCompositeEntity('daily_bills', $tenantId, $invoiceNo);

                // Insert batch
                $this->insertCompositeEntity('batches', $tenantId, $batchCode);

                // Insert cash bank ledger
                $this->insertCompositeEntity('cash_bank_ledgers', $tenantId, $ledgerDate);
            }

            // Mid-cycle adversarial attack: Tenant 3 tries to duplicate the exact same invoice within its own scope
            $adversarialTenantId = $tenants[2]; // Tenant 3
            $caughtException = false;
            try {
                $this->insertCompositeEntity('daily_bills', $adversarialTenantId, $invoiceNo);
            } catch (QueryException $e) {
                $caughtException = true;
            }
            $this->assertTrue($caughtException, "Adversarial duplicate insert within Tenant 3 must fail");

            // Verify count across all tenants for this cycle's shared keys
            $billCount = DB::connection('mysql')->table('daily_bills')->where('invoice_no', $invoiceNo)->count();
            $this->assertEquals(5, $billCount, "All 5 tenants must have invoice {$invoiceNo} concurrently");

            $batchCount = DB::connection('mysql')->table('batches')->where('batch_code', $batchCode)->count();
            $this->assertEquals(5, $batchCount, "All 5 tenants must have batch {$batchCode} concurrently");

            $ledgerCount = DB::connection('mysql')->table('cash_bank_ledgers')->where('ledger_date', $ledgerDate)->count();
            $this->assertEquals(5, $ledgerCount, "All 5 tenants must have ledger date {$ledgerDate} concurrently");
        }
    }

    /**
     * Test 4: Tenant #1 baseline data coexistence with new tenants.
     * Verify that Tenant #1's existing baseline records (dealers, categories)
     * allow identically named counterparts in Tenant #2, while Tenant #1 cannot duplicate itself.
     */
    public function test_tenant_one_baseline_coexistence_with_new_tenants(): void
    {
        $tenantTwoId = $this->createTestTenant('Baseline Coexistence Tenant');

        // Pick an existing baseline category in Tenant 1 (MSR Broilers)
        $baselineCategory = DB::connection('mysql')->table('expense_categories')
            ->where('tenant_id', 1)
            ->first();

        $this->assertNotNull($baselineCategory, "Tenant #1 must have baseline expense categories");
        $categoryName = $baselineCategory->name;

        // Tenant #2 creates a category with the EXACT same name
        $tenantTwoCatId = $this->insertCompositeEntity('expense_categories', $tenantTwoId, $categoryName);
        $this->assertGreaterThan(0, $tenantTwoCatId);

        // Verify both exist independently
        $categories = DB::connection('mysql')->table('expense_categories')
            ->where('name', $categoryName)
            ->get();
        $this->assertGreaterThanOrEqual(2, $categories->count());

        // Verify Tenant #1's original category was NOT modified
        $refreshedBaseline = DB::connection('mysql')->table('expense_categories')
            ->where('id', $baselineCategory->id)
            ->first();
        $this->assertEquals(1, $refreshedBaseline->tenant_id);
        $this->assertEquals($categoryName, $refreshedBaseline->name);

        // Verify Tenant #1 CANNOT create a second category with the same name
        $this->expectException(QueryException::class);
        $this->insertCompositeEntity('expense_categories', 1, $categoryName);
    }

    /**
     * Test 5: Transaction rollback isolation between tenants.
     * Verify that when Tenant 2 fails and rolls back, Tenant 1's coexisting data remains intact.
     */
    public function test_transaction_rollback_isolation_between_tenants(): void
    {
        $tenantTwoId = $this->createTestTenant('Rollback Tenant 2');
        $invoiceNo = 'INV-TX-ROLLBACK-' . uniqid();

        // 1. Tenant 1 successfully commits invoice
        $t1InvoiceId = $this->insertCompositeEntity('daily_bills', 1, $invoiceNo);
        $this->assertGreaterThan(0, $t1InvoiceId);

        // 2. Tenant 2 starts transaction
        DB::connection('mysql')->beginTransaction();

        try {
            // Tenant 2 inserts identical invoice number (valid)
            $this->insertCompositeEntity('daily_bills', $tenantTwoId, $invoiceNo);

            // Tenant 2 attempts an illegal duplicate within its transaction
            $this->insertCompositeEntity('daily_bills', $tenantTwoId, $invoiceNo);

            $this->fail('Expected QueryException on duplicate insert');
        } catch (QueryException $e) {
            DB::connection('mysql')->rollBack();
        }

        // 3. Tenant 1's record must still be intact
        $t1Record = DB::connection('mysql')->table('daily_bills')->where('id', $t1InvoiceId)->first();
        $this->assertNotNull($t1Record, "Tenant #1's invoice must survive Tenant #2 rollback");
        $this->assertEquals(1, $t1Record->tenant_id);
        $this->assertEquals($invoiceNo, $t1Record->invoice_no);

        // 4. Tenant 2 must have zero records for this invoice
        $t2Count = DB::connection('mysql')->table('daily_bills')
            ->where('tenant_id', $tenantTwoId)
            ->where('invoice_no', $invoiceNo)
            ->count();
        $this->assertEquals(0, $t2Count, "Tenant #2 must have 0 records after rollback");
    }

    /**
     * Test 6: Foreign key cascading deletion leaves coexisting tenant intact.
     * Verify that deleting Tenant #2 cascades and deletes Tenant #2's records,
     * while leaving Tenant #1's identically keyed records 100% intact.
     */
    public function test_foreign_key_cascading_leaves_coexisting_tenant_records_intact(): void
    {
        $tenantTwoId = $this->createTestTenant('Cascade Test Tenant 2');

        $invoiceNo = 'INV-CASCADE-TEST-' . uniqid();
        $batchCode = 'BATCH-CASCADE-TEST-' . uniqid();
        $ledgerDate = '2029-08-15';

        // Insert identical data for both tenants
        $t1BillId = $this->insertCompositeEntity('daily_bills', 1, $invoiceNo);
        $t2BillId = $this->insertCompositeEntity('daily_bills', $tenantTwoId, $invoiceNo);

        $t1BatchId = $this->insertCompositeEntity('batches', 1, $batchCode);
        $t2BatchId = $this->insertCompositeEntity('batches', $tenantTwoId, $batchCode);

        $t1LedgerId = $this->insertCompositeEntity('cash_bank_ledgers', 1, $ledgerDate);
        $t2LedgerId = $this->insertCompositeEntity('cash_bank_ledgers', $tenantTwoId, $ledgerDate);

        // Delete Tenant 2 directly
        DB::connection('mysql')->table('tenants')->where('id', $tenantTwoId)->delete();

        // Verify Tenant 2 child records were cascade deleted
        $this->assertEquals(0, DB::connection('mysql')->table('daily_bills')->where('id', $t2BillId)->count());
        $this->assertEquals(0, DB::connection('mysql')->table('batches')->where('id', $t2BatchId)->count());
        $this->assertEquals(0, DB::connection('mysql')->table('cash_bank_ledgers')->where('id', $t2LedgerId)->count());

        // Verify Tenant 1 records REMAIN 100% INTACT
        $this->assertEquals(1, DB::connection('mysql')->table('daily_bills')->where('id', $t1BillId)->count());
        $this->assertEquals(1, DB::connection('mysql')->table('batches')->where('id', $t1BatchId)->count());
        $this->assertEquals(1, DB::connection('mysql')->table('cash_bank_ledgers')->where('id', $t1LedgerId)->count());

        // Verify keys in Tenant 1 are unchanged
        $bill1 = DB::connection('mysql')->table('daily_bills')->where('id', $t1BillId)->first();
        $this->assertEquals($invoiceNo, $bill1->invoice_no);
        $this->assertEquals(1, $bill1->tenant_id);

        $batch1 = DB::connection('mysql')->table('batches')->where('id', $t1BatchId)->first();
        $this->assertEquals($batchCode, $batch1->batch_code);
        $this->assertEquals(1, $batch1->tenant_id);
    }

    /**
     * Test 7: Special characters, slashes, and symbols in unique keys.
     * Real-world poultry invoice patterns like 'INV/2026-10/001#A' must coexist across tenants.
     */
    public function test_special_characters_and_symbol_handling(): void
    {
        $tenantTwoId = $this->createTestTenant('Special Chars Tenant 2');

        $complexKeys = [
            'INV/2026-10/DEL-001#A',
            'BATCH.SPL-2026/FARM#99',
            'KA-01-AB-1234/TRUCK-SPL',
            'Special & Raw Materials (Grade A+)',
        ];

        // Daily bill invoice with slashes & symbols
        $invKey = $complexKeys[0];
        $id1 = $this->insertCompositeEntity('daily_bills', 1, $invKey);
        $id2 = $this->insertCompositeEntity('daily_bills', $tenantTwoId, $invKey);

        $this->assertNotEquals($id1, $id2);
        $this->assertEquals(2, DB::connection('mysql')->table('daily_bills')->where('invoice_no', $invKey)->count());

        // Batch code with dots, slashes, hashes
        $batchKey = $complexKeys[1];
        $b1 = $this->insertCompositeEntity('batches', 1, $batchKey);
        $b2 = $this->insertCompositeEntity('batches', $tenantTwoId, $batchKey);

        $this->assertNotEquals($b1, $b2);
        $this->assertEquals(2, DB::connection('mysql')->table('batches')->where('batch_code', $batchKey)->count());

        // Vehicle number with dashes and slashes
        $vehKey = $complexKeys[2];
        $v1 = $this->insertCompositeEntity('vehicles', 1, $vehKey);
        $v2 = $this->insertCompositeEntity('vehicles', $tenantTwoId, $vehKey);

        $this->assertNotEquals($v1, $v2);
        $this->assertEquals(2, DB::connection('mysql')->table('vehicles')->where('vehicle_number', $vehKey)->count());

        // Expense category with ampersands, parens, plus
        $catKey = $complexKeys[3];
        $c1 = $this->insertCompositeEntity('expense_categories', 1, $catKey);
        $c2 = $this->insertCompositeEntity('expense_categories', $tenantTwoId, $catKey);

        $this->assertNotEquals($c1, $c2);
        $this->assertEquals(2, DB::connection('mysql')->table('expense_categories')->where('name', $catKey)->count());
    }

    /**
     * Test 8: Bulk multi-row insert with identical keys across tenants in a single SQL statement.
     */
    public function test_bulk_multi_row_insert_cross_tenant_coexistence(): void
    {
        $tenantTwoId = $this->createTestTenant('Bulk Insert Tenant 2');
        $bulkInvoiceNo = 'INV-BULK-MULTI-' . uniqid();

        // Single insert statement containing identical invoice number across Tenant 1 and Tenant 2
        DB::connection('mysql')->table('daily_bills')->insert([
            [
                'tenant_id'  => 1,
                'invoice_no' => $bulkInvoiceNo,
                'date'       => now()->toDateString(),
                'amount'     => 100.00,
                'net_amount' => 100.00,
                'status'     => 'Pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'tenant_id'  => $tenantTwoId,
                'invoice_no' => $bulkInvoiceNo,
                'date'       => now()->toDateString(),
                'amount'     => 200.00,
                'net_amount' => 200.00,
                'status'     => 'Pending',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $bills = DB::connection('mysql')->table('daily_bills')->where('invoice_no', $bulkInvoiceNo)->get();
        $this->assertCount(2, $bills, "Bulk insert must create both records without collision");

        // Clean up Tenant 1 record
        $t1Bill = $bills->firstWhere('tenant_id', 1);
        if ($t1Bill) {
            $this->tenantOneCreatedRecords['daily_bills'][] = $t1Bill->id;
        }

        // Bulk insert containing intra-tenant duplicates in single SQL statement MUST fail
        $intraBulkDuplicateFailed = false;
        try {
            DB::connection('mysql')->table('daily_bills')->insert([
                [
                    'tenant_id'  => 1,
                    'invoice_no' => $bulkInvoiceNo . '-DUP',
                    'date'       => now()->toDateString(),
                    'amount'     => 100.00,
                    'net_amount' => 100.00,
                    'status'     => 'Pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'tenant_id'  => 1,
                    'invoice_no' => $bulkInvoiceNo . '-DUP', // Duplicate within Tenant 1
                    'date'       => now()->toDateString(),
                    'amount'     => 200.00,
                    'net_amount' => 200.00,
                    'status'     => 'Pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        } catch (QueryException $e) {
            $intraBulkDuplicateFailed = true;
        }

        $this->assertTrue($intraBulkDuplicateFailed, "Bulk insert with intra-tenant duplicates must be rejected");
    }
}
