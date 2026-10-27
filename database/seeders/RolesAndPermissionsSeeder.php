<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        if (config('permission.teams', false) && function_exists('setPermissionsTeamId')) {
            setPermissionsTeamId(1);
        }

        // Create Permission Groups and Permissions
        $permissionGroups = [
            'Customers' => ['view customers', 'create customers', 'edit customers', 'delete customers', 'view customer bills', 'view customer payments', 'view customer emis'],
            'Dealers' => ['view dealers', 'create dealers', 'edit dealers', 'delete dealers', 'view dealer purchases', 'view dealer ledger'],
            'Vendors' => ['view vendors', 'create vendors', 'edit vendors', 'delete vendors', 'view vendor purchases'],
            'Bills' => ['view bills', 'create bills', 'edit bills', 'delete bills'],
            'Purchases' => ['view purchases', 'create purchases', 'edit purchases', 'delete purchases'],
            'Payments' => ['view payments', 'create payments', 'edit payments', 'delete payments'],
            'Expenses' => ['view expenses', 'create expenses', 'edit expenses', 'delete expenses'],
            'EMIs' => ['view emis', 'create emis', 'edit emis', 'delete emis'],
            'Reports & Dashboards' => ['view reports', 'view profit dashboard'],
            'User Management' => [
                'view users', 'create users', 'edit users', 'delete users',
                'view roles', 'create roles', 'edit roles', 'delete roles',
                'view permissions', 'create permissions', 'edit permissions', 'delete permissions',
                'manage users', 'manage roles', 'manage permissions', 'view activity logs'
            ],
            'Inventory & Stock' => [
                'view stock', 'create stock', 'edit stock', 'delete stock', 
                'view batches', 'create batches', 'edit batches', 'delete batches',
                'view warehouses', 'create warehouses', 'edit warehouses', 'delete warehouses',
                'view items', 'create items', 'edit items', 'delete items',
                'view consumptions', 'create consumptions', 'delete consumptions',
                'view mortalities', 'create mortalities', 'delete mortalities',
                'view analytics'
            ],
            'Routes & Delivery' => [
                'view routes', 'create routes', 'edit routes', 'delete routes', 'mark delivery status',
                'view vehicles', 'create vehicles', 'edit vehicles', 'delete vehicles',
                'view drivers', 'create drivers', 'edit drivers', 'delete drivers'
            ],
        ];

        foreach ($permissionGroups as $groupName => $permissions) {
            $group = \App\Models\PermissionGroup::firstOrCreate(['name' => $groupName]);
            foreach ($permissions as $permission) {
                $perm = Permission::firstOrCreate(['name' => $permission]);
                $perm->update(['permission_group_id' => $group->id]);
            }
        }

        // ROLE 1 - Admin: Full access
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // ROLE 2 - Accountant: Billing, Payment, Reports, Profit, Expenses. NO delete, NO user management.
        $accountantRole = Role::firstOrCreate(['name' => 'accountant']);
        $accountantRole->givePermissionTo([
            'view customers', 'view dealers', 'view vendors',
            'view bills', 'create bills', 'edit bills',
            'view payments', 'create payments', 'edit payments',
            'view expenses', 'create expenses', 'edit expenses',
            'view reports', 'view profit dashboard',
            'view stock'
        ]);

        // ROLE 3 - Delivery Staff: View Route, view Bills, mark delivery status. NO financial data.
        $deliveryStaffRole = Role::firstOrCreate(['name' => 'delivery_staff']);
        $deliveryStaffRole->givePermissionTo([
            'view routes',
            'view bills',
            'mark delivery status'
        ]);

        // ROLE 4 - Data Entry Operator: Purchase Entry, Stock Entry, Master (Add/Edit only). NO delete.
        $dataEntryRole = Role::firstOrCreate(['name' => 'data_entry']);
        $dataEntryRole->givePermissionTo([
            'view customers', 'create customers', 'edit customers',
            'view dealers', 'create dealers', 'edit dealers',
            'view vendors', 'create vendors', 'edit vendors',
            'view purchases', 'create purchases', 'edit purchases',
            'view stock', 'create stock', 'edit stock',
            'view batches', 'create batches', 'edit batches'
        ]);

        // ROLE 5 - Manager: High level access over all business areas, including delete except user configuration.
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $managerRole->givePermissionTo([
            'view customers', 'create customers', 'edit customers', 'delete customers',
            'view dealers', 'create dealers', 'edit dealers', 'delete dealers',
            'view vendors', 'create vendors', 'edit vendors', 'delete vendors',
            'view bills', 'create bills', 'edit bills', 'delete bills',
            'view purchases', 'create purchases', 'edit purchases', 'delete purchases',
            'view payments', 'create payments', 'edit payments', 'delete payments',
            'view expenses', 'create expenses', 'edit expenses', 'delete expenses',
            'view emis', 'create emis', 'edit emis', 'delete emis',
            'view reports', 'view profit dashboard',
            'view stock', 'create stock', 'edit stock', 'delete stock',
            'view routes', 'create routes', 'edit routes', 'delete routes',
            'view batches', 'create batches', 'edit batches', 'delete batches',
            'mark delivery status'
        ]);

        // ROLE 6 - Sales: Customer operations, daily/weekly bills, payments. No expenses or backend settings.
        $salesRole = Role::firstOrCreate(['name' => 'sales']);
        $salesRole->givePermissionTo([
            'view customers', 'create customers', 'edit customers',
            'view dealers', 'create dealers', 'edit dealers',
            'view bills', 'create bills', 'edit bills',
            'view payments', 'create payments', 'edit payments',
            'view routes',
            'mark delivery status'
        ]);

        // ROLE 7 - Store Keeper: Full control over inventory, stock items, warehouses, and flock batches.
        $storeKeeperRole = Role::firstOrCreate(['name' => 'store_keeper']);
        $storeKeeperRole->givePermissionTo([
            'view stock', 'create stock', 'edit stock',
            'view batches', 'create batches', 'edit batches',
            'view routes'
        ]);

        // Create Default Admin User
        $admin = User::firstOrCreate(
            ['email' => 'admin@poultry.com'],
            [
                'name'              => 'Super Admin',
                'password'          => Hash::make('Admin@1234'),
                'is_active'         => true,
                'is_super_admin'    => true,
                'current_tenant_id' => 1,
            ]
        );
        $admin->update([
            'is_super_admin'    => true,
            'current_tenant_id' => $admin->current_tenant_id ?? 1,
        ]);
        $admin->syncRoles(['admin']);

    }
}
