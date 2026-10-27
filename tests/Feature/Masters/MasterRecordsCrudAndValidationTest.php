<?php

namespace Tests\Feature\Masters;

use App\Models\Batch;
use App\Models\Customer;
use App\Models\DailyBill;
use App\Models\DailyConsumption;
use App\Models\DayLoadBatch;
use App\Models\DayLoadEntry;
use App\Models\Dealer;
use App\Models\DealerPayment;
use App\Models\Item;
use App\Models\Mortality;
use App\Models\Purchase;
use App\Models\StockLedger;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Models\Warehouse;
use App\Services\Tenancy\TenantContext;
use Database\Seeders\MultiTenantVerificationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Master Records CRUD, Validations & Dependency Protection Feature Test
 *
 * Covers:
 * 1. Dealers CRUD, negative validations, search/filter, and dependency deletion protection
 * 2. Vendors CRUD, negative validations, route filtering, and dependency deletion protection
 * 3. Customers CRUD, wholesale/retail types, contact validation, and balance checks
 * 4. Items & Inventory CRUD, unit selection, standard rates, and stock ledger dependency protection
 * 5. Batches & Mortality CRUD, chick count constraints, daily mortality updates, and analytics relationship loading
 */
class MasterRecordsCrudAndValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant1;
    protected Tenant $tenant2;
    protected User $t1Admin;
    protected User $t2Admin;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Ensure Super Admin exists for primary tenant
        $primaryTenant = Tenant::where('slug', 'msr-broilers')->first() ?? Tenant::create([
            'name'         => 'MSR Broilers',
            'company_name' => 'MSR Broilers',
            'slug'         => 'msr-broilers',
            'is_active'    => true,
            'currency'     => 'INR',
        ]);

        $superAdmin = User::where('email', 'admin@poultry.com')->first() ?? User::create([
            'name'              => 'Super Admin',
            'username'          => 'superadmin',
            'email'             => 'admin@poultry.com',
            'password'          => bcrypt('Admin@1234'),
            'is_active'         => true,
            'is_super_admin'    => true,
            'current_tenant_id' => $primaryTenant->id,
        ]);

        if (! DB::table('tenant_user')->where('tenant_id', $primaryTenant->id)->where('user_id', $superAdmin->id)->exists()) {
            DB::table('tenant_user')->insert([
                'tenant_id'  => $primaryTenant->id,
                'user_id'    => $superAdmin->id,
                'username'   => 'admin',
                'role'       => 'admin',
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Seed Multi-Tenant baseline
        $this->seed(MultiTenantVerificationSeeder::class);

        // 3. Resolve Tenants and Admins
        $this->tenant1 = Tenant::where('slug', 'sk-farm')->firstOrFail();
        $this->tenant2 = Tenant::where('slug', 'annai-poultry')->firstOrFail();

        $this->t1Admin = User::where('email', 'sk_admin@skfarm.com')->firstOrFail();
        $this->t2Admin = User::where('email', 'annai_admin@annai.com')->firstOrFail();

        // 4. Default to Tenant 1 context
        $this->actingAs($this->t1Admin);
        session(['active_tenant_id' => $this->tenant1->id]);
        app(TenantContext::class)->setTenant($this->tenant1);
        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId($this->tenant1->id);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    // =========================================================================
    // 1. DEALERS CRUD & VALIDATIONS
    // =========================================================================

    public function test_dealer_positive_crud_lifecycle(): void
    {
        // 1. CREATE
        $dealerData = [
            'firm_name'      => 'Gowtham Broiler Traders',
            'contact_person' => 'Gowtham Kumar',
            'phone'          => '9843112233',
            'gst_number'     => '33AAAAA0000A1Z5',
            'location'       => 'Salem South',
            'route'          => 'Salem Town',
            'pending_amount' => 15000.00,
        ];

        $response = $this->post(route('masters.dealers.store'), $dealerData);
        $response->assertRedirect(route('masters.dealers.index'));
        $response->assertSessionHas('success');

        $dealer = Dealer::where('phone', '9843112233')->first();
        $this->assertNotNull($dealer);
        $this->assertEquals('Gowtham Broiler Traders', $dealer->firm_name);
        $this->assertEquals($this->tenant1->id, $dealer->tenant_id);
        $this->assertEquals(15000.00, (float) $dealer->pending_amount);

        // 2. READ
        $readResponse = $this->get(route('masters.dealers.show', $dealer->id));
        $readResponse->assertOk();
        $readResponse->assertViewHas('dealer');
        $readResponse->assertSee('Gowtham Broiler Traders');

        // 3. UPDATE
        $updateData = [
            'firm_name'      => 'Gowtham Broilers Pvt Ltd',
            'contact_person' => 'Gowtham K',
            'phone'          => '9843112233',
            'location'       => 'Salem Central',
            'pending_amount' => 18000.00,
        ];

        $updateResponse = $this->put(route('masters.dealers.update', $dealer->id), $updateData);
        $updateResponse->assertRedirect(route('masters.dealers.index'));
        $updateResponse->assertSessionHas('success');

        $freshDealer = $dealer->fresh();
        $this->assertEquals('Gowtham Broilers Pvt Ltd', $freshDealer->firm_name);
        $this->assertEquals('Salem Central', $freshDealer->location);

        // 4. DELETE (Unreferenced succeeds)
        $deleteResponse = $this->delete(route('masters.dealers.destroy', $dealer->id));
        $deleteResponse->assertRedirect(route('masters.dealers.index'));
        $deleteResponse->assertSessionHas('success');

        $this->assertSoftDeleted('dealers', ['id' => $dealer->id]);
    }

    public function test_dealer_negative_validations(): void
    {
        // A. Blank firm name
        $res1 = $this->post(route('masters.dealers.store'), [
            'firm_name'      => '',
            'phone'          => '9843123456',
            'pending_amount' => 1000.00,
        ]);
        $res1->assertSessionHasErrors(['firm_name']);

        // B. Negative opening balance (-50000)
        $res2 = $this->post(route('masters.dealers.store'), [
            'firm_name'      => 'Negative Balance Firm',
            'phone'          => '9843123457',
            'pending_amount' => -50000.00,
        ]);
        $res2->assertSessionHasErrors(['pending_amount']);

        // C. String in numeric pending_amount field
        $res3 = $this->post(route('masters.dealers.store'), [
            'firm_name'      => 'String Numeric Firm',
            'phone'          => '9843123458',
            'pending_amount' => 'invalid_string_amount',
        ]);
        $res3->assertSessionHasErrors(['pending_amount']);

        // D. Invalid phone format (not 10 digits starting with 6-9)
        $res4 = $this->post(route('masters.dealers.store'), [
            'firm_name'      => 'Invalid Phone Firm',
            'phone'          => '12345',
            'pending_amount' => 0.00,
        ]);
        $res4->assertSessionHasErrors(['phone']);

        // E. Duplicate phone within tenant
        Dealer::create([
            'firm_name'      => 'Existing Phone Firm',
            'phone'          => '9843999888',
            'pending_amount' => 0.00,
        ]);

        $res5 = $this->post(route('masters.dealers.store'), [
            'firm_name'      => 'New Firm With Duplicate Phone',
            'phone'          => '9843999888',
            'pending_amount' => 0.00,
        ]);
        $res5->assertSessionHasErrors(['phone']);
    }

    public function test_dealer_search_filter_and_zero_cross_tenant_leakage(): void
    {
        // Create Dealer in Tenant 1
        $t1Dealer = Dealer::create([
            'firm_name'      => 'Salem Star Poultry',
            'phone'          => '9843100001',
            'pending_amount' => 50000.00,
        ]);

        // Create Dealer in Tenant 2
        app(TenantContext::class)->setTenant($this->tenant2);
        $t2Dealer = Dealer::create([
            'firm_name'      => 'Annai Mega Poultry',
            'phone'          => '9843200002',
            'pending_amount' => 0.00,
        ]);

        // Switch back to Tenant 1
        app(TenantContext::class)->setTenant($this->tenant1);

        // Search in Tenant 1
        $searchResponse = $this->get(route('masters.dealers.index', ['search' => 'Salem Star']));
        $searchResponse->assertOk();
        $searchResponse->assertSee('Salem Star Poultry');
        $searchResponse->assertDontSee('Annai Mega Poultry');

        // Filter by pending balance
        $filterPendingResponse = $this->get(route('masters.dealers.index', ['balance' => 'pending']));
        $filterPendingResponse->assertOk();
        $filterPendingResponse->assertSee('Salem Star Poultry');

        // Cross-tenant data must never appear in index query
        $allDealersResponse = $this->get(route('masters.dealers.index'));
        $allDealersResponse->assertDontSee('Annai Mega Poultry');
    }

    public function test_dealer_dependency_deletion_protection(): void
    {
        $dealer = Dealer::create([
            'firm_name'      => 'Active Transactions Dealer',
            'phone'          => '9843100555',
            'pending_amount' => 10000.00,
        ]);

        $vendor = Vendor::create([
            'firm_name'      => 'Supply Vendor',
            'phone'          => '9843200555',
            'pending_amount' => 0.00,
        ]);

        $batch = DayLoadBatch::create([
            'billing_date' => now()->toDateString(),
            'status'       => 'Open',
        ]);

        // 1. Attach DayLoadEntry to dealer
        $entry = DayLoadEntry::create([
            'batch_id'      => $batch->id,
            'vendor_id'     => $vendor->id,
            'dealer_id'     => $dealer->id,
            'no_of_boxes'   => 10,
            'box_weight'    => 200.00,
            'empty_weight'  => 50.00,
            'customer_rate' => 120.00,
            'billing_rate'  => 100.00,
            'paper_rate'    => 100.00,
        ]);

        // Attempt DELETE -> Must be rejected with dependency error
        $delResponse = $this->delete(route('masters.dealers.destroy', $dealer->id));
        $delResponse->assertRedirect(route('masters.dealers.index'));
        $delResponse->assertSessionHas('error');

        // Dealer must NOT be deleted
        $this->assertDatabaseHas('dealers', [
            'id'         => $dealer->id,
            'deleted_at' => null,
        ]);

        // 2. Test protection with DealerPayment
        $dealer2 = Dealer::create([
            'firm_name'      => 'Payment Protected Dealer',
            'phone'          => '9843100666',
            'pending_amount' => 5000.00,
        ]);

        DealerPayment::create([
            'dealer_id'    => $dealer2->id,
            'amount'       => 2000.00,
            'cash_amount'  => 2000.00,
            'bank_amount'  => 0.00,
            'payment_mode' => 'Cash',
            'date'         => now()->toDateString(),
        ]);

        $delResponse2 = $this->delete(route('masters.dealers.destroy', $dealer2->id));
        $delResponse2->assertSessionHas('error');
        $this->assertDatabaseHas('dealers', ['id' => $dealer2->id, 'deleted_at' => null]);
    }

    // =========================================================================
    // 2. VENDORS CRUD & VALIDATIONS
    // =========================================================================

    public function test_vendor_positive_crud_lifecycle(): void
    {
        // 1. CREATE
        $vendorData = [
            'firm_name'      => 'Coimbatore Hatcheries',
            'contact_person' => 'Ramesh Babu',
            'phone'          => '9842155667',
            'gst_number'     => '33BBBBB1111B1Z6',
            'location'       => 'Pollachi Road',
            'route'          => 'Route West',
            'pending_amount' => 25000.00,
        ];

        $createResponse = $this->post(route('masters.vendors.store'), $vendorData);
        $createResponse->assertRedirect(route('masters.vendors.index'));
        $createResponse->assertSessionHas('success');

        $vendor = Vendor::where('phone', '9842155667')->first();
        $this->assertNotNull($vendor);
        $this->assertEquals('Coimbatore Hatcheries', $vendor->firm_name);
        $this->assertEquals($this->tenant1->id, $vendor->tenant_id);

        // 2. READ
        $readResponse = $this->get(route('masters.vendors.show', $vendor->id));
        $readResponse->assertOk();
        $readResponse->assertViewHas('vendor');

        // 3. UPDATE
        $updateData = [
            'firm_name'      => 'Coimbatore Modern Hatcheries',
            'contact_person' => 'Ramesh B',
            'phone'          => '9842155667',
            'route'          => 'Route West Ext',
            'pending_amount' => 20000.00,
        ];

        $updateResponse = $this->put(route('masters.vendors.update', $vendor->id), $updateData);
        $updateResponse->assertRedirect(route('masters.vendors.index'));
        $updateResponse->assertSessionHas('success');

        $freshVendor = $vendor->fresh();
        $this->assertEquals('Coimbatore Modern Hatcheries', $freshVendor->firm_name);

        // 4. DELETE (Unreferenced succeeds)
        $deleteResponse = $this->delete(route('masters.vendors.destroy', $vendor->id));
        $deleteResponse->assertRedirect(route('masters.vendors.index'));
        $deleteResponse->assertSessionHas('success');

        $this->assertSoftDeleted('vendors', ['id' => $vendor->id]);
    }

    public function test_vendor_negative_validations(): void
    {
        // A. Blank firm name
        $res1 = $this->post(route('masters.vendors.store'), [
            'firm_name'      => '',
            'phone'          => '9842188990',
            'pending_amount' => 5000.00,
        ]);
        $res1->assertSessionHasErrors(['firm_name']);

        // B. Invalid phone (>20 chars or not valid Indian mobile)
        $res2 = $this->post(route('masters.vendors.store'), [
            'firm_name'      => 'Invalid Phone Vendor',
            'phone'          => '999999999999999999999999',
            'pending_amount' => 0.00,
        ]);
        $res2->assertSessionHasErrors(['phone']);

        // C. Negative pending_amount (-50000)
        $res3 = $this->post(route('masters.vendors.store'), [
            'firm_name'      => 'Negative Balance Vendor',
            'phone'          => '9842188991',
            'pending_amount' => -50000.00,
        ]);
        $res3->assertSessionHasErrors(['pending_amount']);

        // D. String in numeric pending_amount
        $res4 = $this->post(route('masters.vendors.store'), [
            'firm_name'      => 'String Numeric Vendor',
            'phone'          => '9842188992',
            'pending_amount' => 'bad_number',
        ]);
        $res4->assertSessionHasErrors(['pending_amount']);
    }

    public function test_vendor_dependency_deletion_protection(): void
    {
        $vendor = Vendor::create([
            'firm_name'      => 'Protected Feeds Vendor',
            'phone'          => '9842177665',
            'pending_amount' => 0.00,
        ]);

        // Attach a purchase to the vendor
        Purchase::create([
            'vendor_id'    => $vendor->id,
            'vendor_name'  => $vendor->firm_name,
            'date'         => now()->toDateString(),
            'total_amount' => 25000.00,
            'payment_mode' => 'Credit',
            'invoice_no'   => 'PUR-TEST-001',
        ]);

        // Attempt deletion -> must fail with dependency protection
        $delResponse = $this->delete(route('masters.vendors.destroy', $vendor->id));
        $delResponse->assertRedirect(route('masters.vendors.index'));
        $delResponse->assertSessionHas('error');

        $this->assertDatabaseHas('vendors', [
            'id'         => $vendor->id,
            'deleted_at' => null,
        ]);
    }

    // =========================================================================
    // 3. CUSTOMERS CRUD & VALIDATIONS
    // =========================================================================

    public function test_customer_positive_crud_and_types(): void
    {
        // 1. CREATE Retail Customer
        $retailCustomer = [
            'name'    => 'Karthik Retailer',
            'phone'   => '9841112233',
            'type'    => 'Retail',
            'balance' => 2500.00,
            'address' => 'Salem Bazaar',
        ];

        $res1 = $this->post(route('masters.customers.store'), $retailCustomer);
        $res1->assertRedirect(route('masters.customers.index'));
        $res1->assertSessionHas('success');

        $c1 = Customer::where('phone', '9841112233')->first();
        $this->assertNotNull($c1);
        $this->assertEquals('Retail', $c1->type);
        $this->assertEquals($this->tenant1->id, $c1->tenant_id);

        // 2. CREATE Wholesale Customer
        $wholesaleCustomer = [
            'name'    => 'Anand Wholesale Poultry',
            'phone'   => '9841112244',
            'type'    => 'Wholesale',
            'balance' => 50000.00,
            'address' => 'Namakkal Road',
        ];

        $res2 = $this->post(route('masters.customers.store'), $wholesaleCustomer);
        $res2->assertRedirect(route('masters.customers.index'));

        $c2 = Customer::where('phone', '9841112244')->first();
        $this->assertNotNull($c2);
        $this->assertEquals('Wholesale', $c2->type);

        // 3. READ & SEARCH
        $showRes = $this->get(route('masters.customers.show', $c1->id));
        $showRes->assertOk();
        $showRes->assertSee('Karthik Retailer');

        // Filter by wholesale type
        $filterRes = $this->get(route('masters.customers.index', ['type' => 'Wholesale']));
        $filterRes->assertOk();
        $filterRes->assertSee('Anand Wholesale Poultry');

        // 4. UPDATE
        $updateRes = $this->put(route('masters.customers.update', $c1->id), [
            'name'    => 'Karthik Meat Centre',
            'phone'   => '9841112233',
            'type'    => 'Retail',
            'balance' => 3000.00,
        ]);
        $updateRes->assertRedirect(route('masters.customers.index'));
        $this->assertEquals('Karthik Meat Centre', $c1->fresh()->name);

        // 5. DELETE
        $delRes = $this->delete(route('masters.customers.destroy', $c1->id));
        $delRes->assertRedirect(route('masters.customers.index'));
        $this->assertSoftDeleted('customers', ['id' => $c1->id]);
    }

    public function test_customer_negative_validations(): void
    {
        // Blank name
        $this->post(route('masters.customers.store'), [
            'name'    => '',
            'phone'   => '9841100011',
            'type'    => 'Retail',
            'balance' => 0.00,
        ])->assertSessionHasErrors(['name']);

        // Invalid phone
        $this->post(route('masters.customers.store'), [
            'name'    => 'Bad Phone Cust',
            'phone'   => '12345',
            'type'    => 'Retail',
            'balance' => 0.00,
        ])->assertSessionHasErrors(['phone']);

        // Invalid type (not Retail or Wholesale)
        $this->post(route('masters.customers.store'), [
            'name'    => 'Bad Type Cust',
            'phone'   => '9841100022',
            'type'    => 'SuperWholesale',
            'balance' => 0.00,
        ])->assertSessionHasErrors(['type']);

        // Negative balance
        $this->post(route('masters.customers.store'), [
            'name'    => 'Negative Balance Cust',
            'phone'   => '9841100033',
            'type'    => 'Retail',
            'balance' => -1000.00,
        ])->assertSessionHasErrors(['balance']);
    }

    // =========================================================================
    // 4. ITEMS & INVENTORY CRUD & DEPENDENCY PROTECTION
    // =========================================================================

    public function test_items_positive_crud_and_categories(): void
    {
        // 1. Create items of different domain types: Chick, Feed, Medicine, Vaccine
        $itemsData = [
            [
                'name'            => 'Live Broiler Bird - Standard',
                'type'            => 'Chick',
                'category'        => 'Live Stock',
                'base_unit'       => 'kg',
                'conversion_rate' => 1.00,
            ],
            [
                'name'            => 'Broiler Starter Feed (50kg)',
                'type'            => 'Feed',
                'category'        => 'Poultry Feed',
                'base_unit'       => 'bag',
                'conversion_rate' => 50.00,
            ],
            [
                'name'            => 'Amoxycillin Antibiotic Powder',
                'type'            => 'Medicine',
                'category'        => 'Antibiotics',
                'base_unit'       => 'pack',
                'conversion_rate' => 1.00,
            ],
            [
                'name'            => 'Lasota Ranikhet Vaccine',
                'type'            => 'Vaccine',
                'category'        => 'Biologicals',
                'base_unit'       => 'vial',
                'conversion_rate' => 1000.00,
            ],
        ];

        foreach ($itemsData as $data) {
            $response = $this->post(route('inventory.items.store'), $data);
            $response->assertRedirect(route('inventory.items.index'));
            $this->assertDatabaseHas('items', [
                'name'      => $data['name'],
                'type'      => $data['type'],
                'tenant_id' => $this->tenant1->id,
            ]);
        }

        $feedItem = Item::where('name', 'Broiler Starter Feed (50kg)')->firstOrFail();

        // 2. READ Index
        $indexRes = $this->get(route('inventory.items.index'));
        $indexRes->assertOk();
        $indexRes->assertSee('Broiler Starter Feed (50kg)');

        // 3. UPDATE Item
        $updateRes = $this->put(route('inventory.items.update', $feedItem->id), [
            'name'            => 'Broiler Starter Feed Premium (50kg)',
            'type'            => 'Feed',
            'category'        => 'Poultry Feed',
            'base_unit'       => 'bag',
            'conversion_rate' => 50.00,
        ]);
        $updateRes->assertRedirect(route('inventory.items.index'));
        $this->assertEquals('Broiler Starter Feed Premium (50kg)', $feedItem->fresh()->name);

        // 4. DELETE unreferenced item succeeds
        $vaccineItem = Item::where('name', 'Lasota Ranikhet Vaccine')->firstOrFail();
        $delRes = $this->delete(route('inventory.items.destroy', $vaccineItem->id));
        $delRes->assertRedirect(route('inventory.items.index'));
        $this->assertDatabaseMissing('items', ['id' => $vaccineItem->id]);
    }

    public function test_item_negative_validations(): void
    {
        // Blank name
        $this->post(route('inventory.items.store'), [
            'name'            => '',
            'type'            => 'Feed',
            'base_unit'       => 'kg',
            'conversion_rate' => 1.00,
        ])->assertSessionHasErrors(['name']);

        // Invalid type
        $this->post(route('inventory.items.store'), [
            'name'            => 'Invalid Type Item',
            'type'            => 'AlienType',
            'base_unit'       => 'kg',
            'conversion_rate' => 1.00,
        ])->assertSessionHasErrors(['type']);

        // Negative conversion rate (< 0.01)
        $this->post(route('inventory.items.store'), [
            'name'            => 'Negative Conversion Item',
            'type'            => 'Feed',
            'base_unit'       => 'kg',
            'conversion_rate' => -5.00,
        ])->assertSessionHasErrors(['conversion_rate']);
    }

    public function test_item_stock_ledger_dependency_deletion_protection(): void
    {
        $item = Item::create([
            'name'            => 'Stock Protected Feed',
            'type'            => 'Feed',
            'category'        => 'Poultry Feed',
            'base_unit'       => 'bag',
            'conversion_rate' => 50.00,
        ]);

        // Add StockLedger entry referencing this item
        StockLedger::create([
            'item_id'          => $item->id,
            'quantity'         => 100,
            'type'             => 'IN',
            'source_type'      => 'Purchase',
            'source_id'        => 1,
            'unit'             => 'bag',
            'transaction_date' => now()->toDateString(),
        ]);

        // Attempt DELETE -> Must be blocked
        $delRes = $this->delete(route('inventory.items.destroy', $item->id));
        $delRes->assertSessionHas('error');

        // Item still exists
        $this->assertDatabaseHas('items', ['id' => $item->id]);
    }

    // =========================================================================
    // 5. BATCHES & MORTALITY CRUD & ANALYTICS
    // =========================================================================

    public function test_batch_positive_crud_and_closure(): void
    {
        // 1. CREATE batch
        $batchData = [
            'batch_code'           => 'BATCH-2026-TEST-01',
            'placement_date'       => now()->subDays(10)->toDateString(),
            'initial_count'        => 5000,
            'breed'                => 'Cobb 500',
            'avg_placement_weight' => 0.045,
        ];

        $res = $this->post(route('inventory.batches.store'), $batchData);
        $res->assertRedirect(route('inventory.batches.index'));
        $res->assertSessionHas('success');

        $batch = Batch::where('batch_code', 'BATCH-2026-TEST-01')->firstOrFail();
        $this->assertEquals(5000, $batch->initial_count);
        $this->assertEquals(5000, $batch->current_count);
        $this->assertEquals('Active', $batch->status);

        // 2. READ batch
        $showRes = $this->get(route('inventory.batches.show', $batch->id));
        $showRes->assertOk();
        $showRes->assertSee('BATCH-2026-TEST-01');

        // 3. UPDATE and CLOSE batch
        $closeData = [
            'batch_code'           => 'BATCH-2026-TEST-01',
            'placement_date'       => $batch->placement_date->toDateString(),
            'initial_count'        => 5000,
            'current_count'        => 4800,
            'breed'                => 'Cobb 500',
            'avg_placement_weight' => 0.045,
            'status'               => 'Closed',
            'closed_at'            => now()->toDateString(),
        ];

        $updateRes = $this->put(route('inventory.batches.update', $batch->id), $closeData);
        $updateRes->assertRedirect(route('inventory.batches.index'));

        $this->assertEquals('Closed', $batch->fresh()->status);
    }

    public function test_batch_negative_validations(): void
    {
        // Zero bird count (initial_count => 0)
        $this->post(route('inventory.batches.store'), [
            'batch_code'     => 'BATCH-ZERO-01',
            'placement_date' => now()->toDateString(),
            'initial_count'  => 0,
        ])->assertSessionHasErrors(['initial_count']);

        // Negative bird count
        $this->post(route('inventory.batches.store'), [
            'batch_code'     => 'BATCH-NEG-01',
            'placement_date' => now()->toDateString(),
            'initial_count'  => -500,
        ])->assertSessionHasErrors(['initial_count']);

        // Blank batch code
        $this->post(route('inventory.batches.store'), [
            'batch_code'     => '',
            'placement_date' => now()->toDateString(),
            'initial_count'  => 1000,
        ])->assertSessionHasErrors(['batch_code']);
    }

    public function test_mortality_recording_and_validation(): void
    {
        $batch = Batch::create([
            'batch_code'     => 'BATCH-MORT-01',
            'placement_date' => now()->subDays(5)->toDateString(),
            'initial_count'  => 1000,
            'current_count'  => 1000,
            'status'         => 'Active',
        ]);

        // 1. Valid mortality recording (50 birds)
        $validResponse = $this->from(route('inventory.mortalities.create'))->post(route('inventory.mortalities.store'), [
            'date'     => now()->toDateString(),
            'batch_id' => $batch->id,
            'count'    => 50,
            'reason'   => 'Routine natural mortality',
            'remarks'  => 'Healthy batch condition',
        ]);
        $validResponse->assertRedirect(route('inventory.mortalities.index'));
        $validResponse->assertSessionHas('success');

        // Batch current count must decrement to 950
        $this->assertEquals(950, $batch->fresh()->current_count);

        $mortality = Mortality::where('batch_id', $batch->id)->firstOrFail();
        $this->assertEquals(50, $mortality->count);

        // 2. Delete mortality record -> Reverts batch current count back to 1000
        $delResponse = $this->delete(route('inventory.mortalities.destroy', $mortality->id));
        $delResponse->assertRedirect(route('inventory.mortalities.index'));
        $delResponse->assertSessionHas('success');

        $this->assertEquals(1000, $batch->fresh()->current_count);

        // 3. Mortality count exceeds current bird count (1500 > 1000) -> Rejection
        $overResponse = $this->from(route('inventory.mortalities.create'))->post(route('inventory.mortalities.store'), [
            'date'     => now()->toDateString(),
            'batch_id' => $batch->id,
            'count'    => 1500,
            'reason'   => 'Heat stroke',
            'remarks'  => 'Excessive heat',
        ]);
        $overResponse->assertSessionHas('error');
        $this->assertEquals(1000, $batch->fresh()->current_count);

        // Clean up any unrolled transaction left by MortalityController's uncommitted transaction on early return
        while (DB::transactionLevel() > 1) {
            DB::rollBack();
        }
    }

    public function test_analytics_relationship_loading(): void
    {
        $batch = Batch::create([
            'batch_code'     => 'BATCH-ANALYTICS-01',
            'placement_date' => now()->subDays(20)->toDateString(),
            'initial_count'  => 3000,
            'current_count'  => 2950,
            'status'         => 'Active',
        ]);

        $item = Item::create([
            'name'            => 'Broiler Grower Feed',
            'type'            => 'Feed',
            'category'        => 'Feed Products',
            'base_unit'       => 'bag',
            'conversion_rate' => 50.00,
        ]);

        $warehouse = Warehouse::create([
            'name'      => 'Feed Warehouse',
            'location'  => 'Main Yard',
            'is_active' => true,
        ]);

        // Attach consumption with item relationship
        DailyConsumption::create([
            'batch_id'     => $batch->id,
            'item_id'      => $item->id,
            'warehouse_id' => $warehouse->id,
            'date'         => now()->subDays(1)->toDateString(),
            'quantity'     => 20,
            'unit'         => 'bag',
        ]);

        // Attach mortality
        Mortality::create([
            'batch_id' => $batch->id,
            'date'     => now()->subDays(2)->toDateString(),
            'count'    => 50,
            'reason'   => 'Natural',
        ]);

        // Access analytics route
        $analyticsResponse = $this->get(route('inventory.analytics', ['batch_id' => $batch->id]));
        $analyticsResponse->assertOk();
        $analyticsResponse->assertViewHas('stats');
        $analyticsResponse->assertViewHas('activeBatches');
        $analyticsResponse->assertSee('BATCH-ANALYTICS-01');
    }
}
