<?php

namespace Database\Seeders;

use App\Models\CashBankLedger;
use App\Models\DayLoadBatch;
use App\Models\DayLoadEntry;
use App\Models\Dealer;
use App\Models\DealerPayment;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendor;
use App\Services\Tenancy\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class MultiTenantVerificationSeeder extends Seeder
{
    /**
     * Run the database seeds for multi-tenant verification.
     */
    public function run(): void
    {
        // 1. Ensure core permissions exist
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId(1);
        }

        if (Permission::count() === 0) {
            $this->call(RolesAndPermissionsSeeder::class);
        }

        $allPermissions = Permission::all();
        $accountantPermissionNames = [
            'view customers', 'view dealers', 'view vendors',
            'view bills', 'create bills', 'edit bills',
            'view payments', 'create payments', 'edit payments',
            'view expenses', 'create expenses', 'edit expenses',
            'view reports', 'view profit dashboard',
            'view stock'
        ];
        $accountantPermissions = Permission::whereIn('name', $accountantPermissionNames)->get();

        $staffPermissionNames = [
            'view customers', 'view dealers', 'view vendors',
            'view bills', 'create bills', 'view reports'
        ];
        $staffPermissions = Permission::whereIn('name', $staffPermissionNames)->get();

        $context = app(TenantContext::class);

        // 2. Tenants definition
        $tenantsData = [
            [
                'name'         => 'SK Farm Broilers',
                'company_name' => 'SK Farm Broilers',
                'slug'         => 'sk-farm',
                'email'        => 'sk_admin@skfarm.com',
                'phone'        => '9842000001',
                'address'      => 'Salem Main Road, Namakkal District, Tamil Nadu',
                'currency'     => 'INR',
                'admin_name'   => 'SK Farm Admin',
                'admin_user'   => 'sk_admin',
                'admin_email'  => 'sk_admin@skfarm.com',
                'acc_name'     => 'SK Farm Accountant',
                'acc_user'     => 'sk_acc',
                'acc_email'    => 'sk_acc@skfarm.com',
                'staff_name'   => 'SK Farm Staff',
                'staff_user'   => 'sk_staff',
                'staff_email'  => 'sk_staff@skfarm.com',
                'dealer'       => [
                    'firm_name'      => 'Salem Modern Broilers',
                    'contact_person' => 'Ravi Kumar',
                    'phone'          => '9842100001',
                    'location'       => 'Salem Market',
                    'pending_amount' => 15000.00,
                ],
                'vendor'       => [
                    'firm_name'      => 'SK Feed Traders',
                    'contact_person' => 'Karthik Raja',
                    'phone'          => '9842200001',
                    'location'       => 'Namakkal Feed Belt',
                    'pending_amount' => 50000.00,
                ],
                'dayload'      => [
                    'boxes'         => 100,
                    'box_weight'    => 2500.50,
                    'empty_weight'  => 500.50,
                    'farm_weight'   => 2050.00,
                    'customer_rate' => 135.00,
                    'billing_rate'  => 120.00,
                    'paper_rate'    => 120.00,
                ],
                'payment'      => [
                    'cash' => 10000.00,
                    'bank' => 5000.00,
                ],
                'ledger'       => [
                    'opening_cash' => 5000.00,
                    'opening_bank' => 50000.00,
                ],
            ],
            [
                'name'         => 'Annai Poultry Farms',
                'company_name' => 'Annai Poultry Farms',
                'slug'         => 'annai-poultry',
                'email'        => 'annai_admin@annai.com',
                'phone'        => '9842000002',
                'address'      => 'Bhavani Road, Erode District, Tamil Nadu',
                'currency'     => 'INR',
                'admin_name'   => 'Annai Poultry Admin',
                'admin_user'   => 'annai_admin',
                'admin_email'  => 'annai_admin@annai.com',
                'acc_name'     => 'Annai Poultry Accountant',
                'acc_user'     => 'annai_acc',
                'acc_email'    => 'annai_acc@annai.com',
                'staff_name'   => 'Annai Poultry Staff',
                'staff_user'   => 'annai_staff',
                'staff_email'  => 'annai_staff@annai.com',
                'dealer'       => [
                    'firm_name'      => 'Erode Central Chicken',
                    'contact_person' => 'Senthil Nathan',
                    'phone'          => '9842100002',
                    'location'       => 'Erode Central Hub',
                    'pending_amount' => 25000.00,
                ],
                'vendor'       => [
                    'firm_name'      => 'Namakkal Chicks Hatcheries',
                    'contact_person' => 'Palanisamy',
                    'phone'          => '9842200002',
                    'location'       => 'Namakkal Hatchery Line',
                    'pending_amount' => 75000.00,
                ],
                'dayload'      => [
                    'boxes'         => 80,
                    'box_weight'    => 2040.00,
                    'empty_weight'  => 440.00,
                    'farm_weight'   => 1630.00,
                    'customer_rate' => 140.00,
                    'billing_rate'  => 125.00,
                    'paper_rate'    => 125.00,
                ],
                'payment'      => [
                    'cash' => 12000.00,
                    'bank' => 8000.00,
                ],
                'ledger'       => [
                    'opening_cash' => 10000.00,
                    'opening_bank' => 100000.00,
                ],
            ],
            [
                'name'         => 'Kongu Broilers Network',
                'company_name' => 'Kongu Broilers Network',
                'slug'         => 'kongu-broilers',
                'email'        => 'kongu_admin@kongu.com',
                'phone'        => '9842000003',
                'address'      => 'Avinashi Road, Coimbatore District, Tamil Nadu',
                'currency'     => 'INR',
                'admin_name'   => 'Kongu Broilers Admin',
                'admin_user'   => 'kongu_admin',
                'admin_email'  => 'kongu_admin@kongu.com',
                'acc_name'     => 'Kongu Broilers Accountant',
                'acc_user'     => 'kongu_acc',
                'acc_email'    => 'kongu_acc@kongu.com',
                'staff_name'   => 'Kongu Broilers Staff',
                'staff_user'   => 'kongu_staff',
                'staff_email'  => 'kongu_staff@kongu.com',
                'dealer'       => [
                    'firm_name'      => 'Coimbatore Royal Broilers',
                    'contact_person' => 'Murugan Swamy',
                    'phone'          => '9842100003',
                    'location'       => 'RS Puram, Coimbatore',
                    'pending_amount' => 35000.00,
                ],
                'vendor'       => [
                    'firm_name'      => 'Pollachi Maize Feeds',
                    'contact_person' => 'Velusamy',
                    'phone'          => '9842200003',
                    'location'       => 'Pollachi Feed Mills',
                    'pending_amount' => 60000.00,
                ],
                'dayload'      => [
                    'boxes'         => 120,
                    'box_weight'    => 3050.00,
                    'empty_weight'  => 650.00,
                    'farm_weight'   => 2460.00,
                    'customer_rate' => 138.00,
                    'billing_rate'  => 122.00,
                    'paper_rate'    => 122.00,
                ],
                'payment'      => [
                    'cash' => 15000.00,
                    'bank' => 10000.00,
                ],
                'ledger'       => [
                    'opening_cash' => 8000.00,
                    'opening_bank' => 80000.00,
                ],
            ],
        ];

        foreach ($tenantsData as $data) {
            // A. Provision Tenant
            $tenant = Tenant::updateOrCreate(
                ['slug' => $data['slug']],
                [
                    'name'         => $data['name'],
                    'company_name' => $data['company_name'],
                    'email'        => $data['email'],
                    'phone'        => $data['phone'],
                    'address'      => $data['address'],
                    'currency'     => $data['currency'],
                    'timezone'     => 'Asia/Kolkata',
                    'date_format'  => 'd-m-Y',
                    'is_active'    => true,
                ]
            );

            $context->setTenant($tenant);

            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($tenant->id);
            }

            // B. Provision Spatie Roles scoped to this tenant team_id
            $adminRole = Role::firstOrCreate([
                'name'       => 'admin',
                'team_id'    => $tenant->id,
                'guard_name' => 'web',
            ]);
            $adminRole->syncPermissions($allPermissions);

            $accRole = Role::firstOrCreate([
                'name'       => 'accountant',
                'team_id'    => $tenant->id,
                'guard_name' => 'web',
            ]);
            $accRole->syncPermissions($accountantPermissions);

            $staffRole = Role::firstOrCreate([
                'name'       => 'staff',
                'team_id'    => $tenant->id,
                'guard_name' => 'web',
            ]);
            $staffRole->syncPermissions($staffPermissions);

            // C. Provision Users
            // 1. Tenant Admin
            $adminUser = User::updateOrCreate(
                ['email' => $data['admin_email']],
                [
                    'name'              => $data['admin_name'],
                    'password'          => Hash::make('Admin@1234'),
                    'is_active'         => true,
                    'is_super_admin'    => false,
                    'current_tenant_id' => $tenant->id,
                ]
            );
            $adminUser->update(['current_tenant_id' => $tenant->id, 'is_super_admin' => false]);

            DB::table('tenant_user')->updateOrInsert(
                ['tenant_id' => $tenant->id, 'user_id' => $adminUser->id],
                ['username' => $data['admin_user'], 'role' => 'admin', 'is_active' => true, 'updated_at' => now()]
            );

            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($tenant->id);
            }
            $adminUser->syncRoles([$adminRole]);

            // 2. Accountant User
            $accUser = User::updateOrCreate(
                ['email' => $data['acc_email']],
                [
                    'name'              => $data['acc_name'],
                    'password'          => Hash::make('Admin@1234'),
                    'is_active'         => true,
                    'is_super_admin'    => false,
                    'current_tenant_id' => $tenant->id,
                ]
            );
            $accUser->update(['current_tenant_id' => $tenant->id, 'is_super_admin' => false]);

            DB::table('tenant_user')->updateOrInsert(
                ['tenant_id' => $tenant->id, 'user_id' => $accUser->id],
                ['username' => $data['acc_user'], 'role' => 'accountant', 'is_active' => true, 'updated_at' => now()]
            );

            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($tenant->id);
            }
            $accUser->syncRoles([$accRole]);

            // 3. Billing Staff User
            $staffUser = User::updateOrCreate(
                ['email' => $data['staff_email']],
                [
                    'name'              => $data['staff_name'],
                    'password'          => Hash::make('Admin@1234'),
                    'is_active'         => true,
                    'is_super_admin'    => false,
                    'current_tenant_id' => $tenant->id,
                ]
            );
            $staffUser->update(['current_tenant_id' => $tenant->id, 'is_super_admin' => false]);

            DB::table('tenant_user')->updateOrInsert(
                ['tenant_id' => $tenant->id, 'user_id' => $staffUser->id],
                ['username' => $data['staff_user'], 'role' => 'staff', 'is_active' => true, 'updated_at' => now()]
            );

            if (function_exists('setPermissionsTeamId')) {
                setPermissionsTeamId($tenant->id);
            }
            $staffUser->syncRoles([$staffRole]);

            // D. Operational Masters
            // 1. Dealer
            $dealer = Dealer::updateOrCreate(
                ['tenant_id' => $tenant->id, 'firm_name' => $data['dealer']['firm_name']],
                [
                    'contact_person' => $data['dealer']['contact_person'],
                    'phone'          => $data['dealer']['phone'],
                    'location'       => $data['dealer']['location'],
                    'pending_amount' => $data['dealer']['pending_amount'],
                ]
            );

            // 2. Vendor
            $vendor = Vendor::updateOrCreate(
                ['tenant_id' => $tenant->id, 'firm_name' => $data['vendor']['firm_name']],
                [
                    'contact_person' => $data['vendor']['contact_person'],
                    'phone'          => $data['vendor']['phone'],
                    'location'       => $data['vendor']['location'],
                    'pending_amount' => $data['vendor']['pending_amount'],
                ]
            );

            // E. DayLoad Billing & Entries
            $billingDate = '2026-10-10';
            $batch = DayLoadBatch::firstOrCreate(
                ['tenant_id' => $tenant->id, 'billing_date' => $billingDate],
                ['status' => 'Open']
            );

            $dayload = $data['dayload'];
            $entry = DayLoadEntry::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'batch_id'  => $batch->id,
                    'dealer_id' => $dealer->id,
                    'vendor_id' => $vendor->id,
                ],
                [
                    'no_of_boxes'   => $dayload['boxes'],
                    'box_weight'    => $dayload['box_weight'],
                    'empty_weight'  => $dayload['empty_weight'],
                    'farm_weight'   => $dayload['farm_weight'],
                    'customer_rate' => $dayload['customer_rate'],
                    'billing_rate'  => $dayload['billing_rate'],
                    'paper_rate'    => $dayload['paper_rate'],
                    'status'        => 'Active',
                    'sort_order'    => 1,
                ]
            );

            // F. Dealer Payment & Ledger Roll-forward
            $paymentData = $data['payment'];
            $totalPayment = $paymentData['cash'] + $paymentData['bank'];

            $dealerPayment = DealerPayment::updateOrCreate(
                [
                    'tenant_id'         => $tenant->id,
                    'dealer_id'         => $dealer->id,
                    'day_load_entry_id' => $entry->id,
                ],
                [
                    'date'              => $billingDate,
                    'payment_mode'      => 'Split',
                    'cash_amount'       => $paymentData['cash'],
                    'bank_amount'       => $paymentData['bank'],
                    'amount'            => $totalPayment,
                    'payment_group_id'  => (string) Str::uuid(),
                    'notes'             => "Operational settlement against Day-Load Entry #{$entry->id}",
                ]
            );

            $entry->updateQuietly([
                'dealer_collected'      => $totalPayment,
                'dealer_payment_status' => $totalPayment >= (float) $entry->amount ? 'Paid' : 'Partial',
            ]);

            // G. Cash & Bank Daily Ledger
            $ledgerData = $data['ledger'];
            CashBankLedger::updateOrCreate(
                [
                    'tenant_id'   => $tenant->id,
                    'ledger_date' => $billingDate,
                ],
                [
                    'opening_cash_balance' => $ledgerData['opening_cash'],
                    'opening_bank_balance' => $ledgerData['opening_bank'],
                    'cash_income'          => $paymentData['cash'],
                    'bank_income'          => $paymentData['bank'],
                    'cash_expense'         => 0.00,
                    'bank_expense'         => 0.00,
                    'closing_cash_balance' => $ledgerData['opening_cash'] + $paymentData['cash'],
                    'closing_bank_balance' => $ledgerData['opening_bank'] + $paymentData['bank'],
                    'is_approved'          => false,
                ]
            );
        }
    }
}
