<?php

namespace Tests\Feature\Tenancy;

use Tests\TestCase;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabasePartitioningTest extends TestCase
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

    /**
     * The 39 business models mapped to their classes.
     *
     * @var array<string, class-string>
     */
    protected array $businessModels = [
        'ActivityLog' => \App\Models\ActivityLog::class,
        'Batch' => \App\Models\Batch::class,
        'BirdBatch' => \App\Models\BirdBatch::class,
        'CapitalTransaction' => \App\Models\CapitalTransaction::class,
        'CashBankLedger' => \App\Models\CashBankLedger::class,
        'Consumption' => \App\Models\Consumption::class,
        'Customer' => \App\Models\Customer::class,
        'CustomerPayment' => \App\Models\CustomerPayment::class,
        'DailyBill' => \App\Models\DailyBill::class,
        'DailyBillItem' => \App\Models\DailyBillItem::class,
        'DayLoadBatch' => \App\Models\DayLoadBatch::class,
        'DayLoadEntry' => \App\Models\DayLoadEntry::class,
        'DayLoadInvoice' => \App\Models\DayLoadInvoice::class,
        'Dealer' => \App\Models\Dealer::class,
        'DealerPayment' => \App\Models\DealerPayment::class,
        'DealerPurchase' => \App\Models\DealerPurchase::class,
        'DealerPurchaseItem' => \App\Models\DealerPurchaseItem::class,
        'Driver' => \App\Models\Driver::class,
        'Emi' => \App\Models\Emi::class,
        'EntryAdjustmentLog' => \App\Models\EntryAdjustmentLog::class,
        'Expense' => \App\Models\Expense::class,
        'ExpenseCategory' => \App\Models\ExpenseCategory::class,
        'Item' => \App\Models\Item::class,
        'Mortality' => \App\Models\Mortality::class,
        'PaymentAdjustmentLog' => \App\Models\PaymentAdjustmentLog::class,
        'Purchase' => \App\Models\Purchase::class,
        'PurchaseItem' => \App\Models\PurchaseItem::class,
        'Route' => \App\Models\Route::class,
        'StockItem' => \App\Models\StockItem::class,
        'StockLedger' => \App\Models\StockLedger::class,
        'StockTransaction' => \App\Models\StockTransaction::class,
        'Vehicle' => \App\Models\Vehicle::class,
        'VendorAdvance' => \App\Models\VendorAdvance::class,
        'VendorAdvanceAdjustment' => \App\Models\VendorAdvanceAdjustment::class,
        'VendorPayment' => \App\Models\VendorPayment::class,
        'Vendor' => \App\Models\Vendor::class,
        'Warehouse' => \App\Models\Warehouse::class,
        'WeeklyBill' => \App\Models\WeeklyBill::class,
        'WeeklyBillItem' => \App\Models\WeeklyBillItem::class,
    ];

    /**
     * Map of 10 composite unique constraints.
     *
     * @var array<string, array{column: string, index: string}>
     */
    protected array $compositeUniqueConstraints = [
        'cash_bank_ledgers' => [
            'column' => 'ledger_date',
            'index' => 'cash_bank_ledgers_tenant_id_ledger_date_unique',
        ],
        'daily_bills' => [
            'column' => 'invoice_no',
            'index' => 'daily_bills_tenant_id_invoice_no_unique',
        ],
        'weekly_bills' => [
            'column' => 'invoice_no',
            'index' => 'weekly_bills_tenant_id_invoice_no_unique',
        ],
        'day_load_invoices' => [
            'column' => 'invoice_no',
            'index' => 'day_load_invoices_tenant_id_invoice_no_unique',
        ],
        'dealer_purchases' => [
            'column' => 'invoice_no',
            'index' => 'dealer_purchases_tenant_id_invoice_no_unique',
        ],
        'batches' => [
            'column' => 'batch_code',
            'index' => 'batches_tenant_id_batch_code_unique',
        ],
        'expense_categories' => [
            'column' => 'name',
            'index' => 'expense_categories_tenant_id_name_unique',
        ],
        'items' => [
            'column' => 'code',
            'index' => 'items_tenant_id_code_unique',
        ],
        'stock_items' => [
            'column' => 'item_name',
            'index' => 'stock_items_tenant_id_item_name_unique',
        ],
        'vehicles' => [
            'column' => 'vehicle_number',
            'index' => 'vehicles_tenant_id_vehicle_number_unique',
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Enforce MySQL poultry_db connection for schema and baseline validation
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'poultry_db',
        ]);
        DB::purge();
        DB::setDefaultConnection('mysql');
    }

    /**
     * Test 1: Role model has team_id in $fillable.
     */
    public function test_role_model_has_team_id_in_fillable(): void
    {
        $role = new Role();
        $this->assertContains('team_id', $role->getFillable(), 'Role model must have team_id in $fillable');
    }

    /**
     * Test 2: All 39 business tables have tenant_id column.
     */
    public function test_all_39_business_tables_have_tenant_id_column(): void
    {
        $this->assertCount(39, $this->businessTables, 'There must be exactly 39 business tables');

        foreach ($this->businessTables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Table {$table} must exist in the database"
            );
            $this->assertTrue(
                Schema::hasColumn($table, 'tenant_id'),
                "Table {$table} must contain the 'tenant_id' column"
            );
        }
    }

    /**
     * Test 3: tenant_id is NOT NULL across all 39 business tables.
     */
    public function test_tenant_id_column_is_not_null_on_all_39_tables(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('Column metadata check specifically requires MySQL connection');
        }

        foreach ($this->businessTables as $table) {
            $columns = DB::select("SHOW COLUMNS FROM `{$table}` WHERE Field = 'tenant_id'");
            $this->assertNotEmpty($columns, "Column tenant_id must exist in {$table}");
            $this->assertEquals(
                'NO',
                $columns[0]->Null,
                "Table {$table}.tenant_id must be NOT NULL"
            );
        }
    }

    /**
     * Test 4: tenant_id has an index on each of the 39 business tables.
     */
    public function test_tenant_id_is_indexed_on_all_39_tables(): void
    {
        foreach ($this->businessTables as $table) {
            $indexName = "{$table}_tenant_id_index";
            $this->assertTrue(
                Schema::hasIndex($table, $indexName) || Schema::hasIndex($table, [$table, 'tenant_id']),
                "Table {$table} must have an index on tenant_id"
            );
        }
    }

    /**
     * Test 5: All 39 Eloquent models have tenant_id in $fillable.
     */
    public function test_all_39_eloquent_models_have_tenant_id_in_fillable(): void
    {
        $this->assertCount(39, $this->businessModels, 'There must be exactly 39 business models');

        foreach ($this->businessModels as $name => $class) {
            $model = new $class();
            $this->assertContains(
                'tenant_id',
                $model->getFillable(),
                "Model {$name} ({$class}) must include 'tenant_id' in \$fillable"
            );
        }
    }

    /**
     * Test 6: Baseline data preservation for Tenant #1 (MSR Broilers).
     */
    public function test_baseline_data_preservation_for_tenant_one(): void
    {
        // Tenant #1 must exist
        $tenant = DB::table('tenants')->where('id', 1)->first();
        $this->assertNotNull($tenant, 'Tenant #1 (MSR Broilers) must exist in database');

        // Check dealers (30 active records backfilled with tenant_id = 1)
        $dealerCount = DB::table('dealers')->where('tenant_id', 1)->count();
        $this->assertGreaterThanOrEqual(30, $dealerCount, 'All 30 baseline dealers must have tenant_id = 1');

        // Check vendors (6 active records backfilled with tenant_id = 1)
        $vendorCount = DB::table('vendors')->where('tenant_id', 1)->count();
        $this->assertGreaterThanOrEqual(6, $vendorCount, 'All 6 baseline vendors must have tenant_id = 1');

        // Check customers (2 active records backfilled with tenant_id = 1)
        $customerCount = DB::table('customers')->where('tenant_id', 1)->count();
        $this->assertGreaterThanOrEqual(2, $customerCount, 'All 2 baseline customers must have tenant_id = 1');

        // Check expense categories (10 active records backfilled with tenant_id = 1)
        $categoryCount = DB::table('expense_categories')->where('tenant_id', 1)->count();
        $this->assertGreaterThanOrEqual(10, $categoryCount, 'All 10 baseline expense categories must have tenant_id = 1');

        // Ensure zero unallocated (NULL) tenant_id records exist across all 39 tables
        foreach ($this->businessTables as $table) {
            $nullRows = DB::table($table)->whereNull('tenant_id')->count();
            $this->assertEquals(0, $nullRows, "Table {$table} must have 0 unallocated null tenant_id records");
        }
    }

    /**
     * Test 7: All 10 composite unique constraints exist in schema.
     */
    public function test_all_10_composite_unique_constraints_exist(): void
    {
        foreach ($this->compositeUniqueConstraints as $table => $def) {
            $indexName = $def['index'];
            $this->assertTrue(
                Schema::hasIndex($table, $indexName),
                "Table {$table} must have composite unique index '{$indexName}'"
            );
        }
    }

    /**
     * Test 8: Composite uniqueness allows identical values across different tenants.
     */
    public function test_composite_uniqueness_permits_identical_values_in_different_tenants(): void
    {
        // Create a secondary tenant for testing
        $tenantTwoId = DB::table('tenants')->insertGetId([
            'name' => 'Apex Poultry Farms',
            'slug' => 'apex-poultry-' . uniqid(),
            'company_name' => 'Apex Farms Ltd',
            'email' => 'apex@poultry.test',
            'currency' => 'INR',
            'timezone' => 'Asia/Kolkata',
            'date_format' => 'd-m-Y',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $testInvoiceNo = 'INV-TEST-SPLIT-' . rand(1000, 9999);

        try {
            // Insert invoice for Tenant #1
            $id1 = DB::table('daily_bills')->insertGetId([
                'tenant_id' => 1,
                'invoice_no' => $testInvoiceNo,
                'customer_id' => null,
                'date' => now()->toDateString(),
                'amount' => 1500.00,
                'net_amount' => 1500.00,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Insert IDENTICAL invoice number for Tenant #2
            $id2 = DB::table('daily_bills')->insertGetId([
                'tenant_id' => $tenantTwoId,
                'invoice_no' => $testInvoiceNo,
                'customer_id' => null,
                'date' => now()->toDateString(),
                'amount' => 2500.00,
                'net_amount' => 2500.00,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assertNotEquals($id1, $id2);
            $this->assertEquals(2, DB::table('daily_bills')->where('invoice_no', $testInvoiceNo)->count());

            // Attempting duplicate in the SAME tenant must fail with QueryException
            $this->expectException(QueryException::class);
            DB::table('daily_bills')->insert([
                'tenant_id' => 1,
                'invoice_no' => $testInvoiceNo,
                'customer_id' => null,
                'date' => now()->toDateString(),
                'amount' => 3500.00,
                'net_amount' => 3500.00,
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } finally {
            // Clean up test records
            DB::table('daily_bills')->where('invoice_no', $testInvoiceNo)->delete();
            DB::table('tenants')->where('id', $tenantTwoId)->delete();
        }
    }

    /**
     * Test 9: Foreign key cascading delete removes tenant child records.
     */
    public function test_tenant_foreign_key_cascades_on_delete(): void
    {
        // Create temporary test tenant
        $tempTenantId = DB::table('tenants')->insertGetId([
            'name' => 'Ephemeral Test Tenant',
            'slug' => 'ephemeral-' . uniqid(),
            'company_name' => 'Ephemeral Ltd',
            'is_active' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Insert child record in expense_categories
        $tempCatId = DB::table('expense_categories')->insertGetId([
            'tenant_id' => $tempTenantId,
            'name' => 'Ephemeral Expense ' . uniqid(),
            'color' => '#10B981',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertEquals(1, DB::table('expense_categories')->where('id', $tempCatId)->count());

        // Delete parent tenant
        DB::table('tenants')->where('id', $tempTenantId)->delete();

        // Verify child record was cascade deleted
        $this->assertEquals(
            0,
            DB::table('expense_categories')->where('id', $tempCatId)->count(),
            'Child record in expense_categories must be deleted via foreign key ON DELETE CASCADE'
        );
    }
}
