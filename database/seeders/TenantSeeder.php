<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantSeeder extends Seeder
{
    /**
     * Seed the core baseline Tenant #1 and establish memberships.
     */
    public function run(): void
    {
        // 1. Seed or update Tenant #1: MSR Broilers
        $tenant = Tenant::updateOrCreate(
            ['id' => 1],
            [
                'name' => 'MSR Broilers',
                'slug' => 'msr-broilers',
                'domain' => null,
                'company_name' => 'MSR Broilers',
                'email' => 'admin@poultry.com',
                'phone' => null,
                'address' => 'Headquarters, Main Poultry Yard',
                'gst_number' => null,
                'currency' => 'INR',
                'timezone' => 'Asia/Kolkata',
                'date_format' => 'd-m-Y',
                'is_active' => true,
                'reseller_id' => null,
            ]
        );

        // 2. Configure Main Platform Super Admin: admin@poultry.com
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@poultry.com'],
            [
                'name' => 'MSR Super Admin',
                'password' => bcrypt('Admin@1234'),
                'is_super_admin' => true,
                'current_tenant_id' => $tenant->id,
            ]
        );
        $superAdmin->update([
            'is_super_admin' => true,
            'current_tenant_id' => $tenant->id,
        ]);

        DB::table('tenant_user')->updateOrInsert(
            [
                'tenant_id' => $tenant->id,
                'user_id' => $superAdmin->id,
            ],
            [
                'username' => 'admin',
                'role' => 'admin',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 3. Configure Existing Staff / Accountant User: sriram@gmail.com
        $sriram = User::firstOrCreate(
            ['email' => 'sriram@gmail.com'],
            [
                'name' => 'Sriram',
                'password' => bcrypt('password'),
                'is_super_admin' => false,
                'current_tenant_id' => $tenant->id,
            ]
        );
        $sriram->update([
            'is_super_admin' => false,
            'current_tenant_id' => $tenant->id,
        ]);

        DB::table('tenant_user')->updateOrInsert(
            [
                'tenant_id' => $tenant->id,
                'user_id' => $sriram->id,
            ],
            [
                'username' => 'sriram',
                'role' => 'accountant',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // 4. Attach any remaining unlinked users to Tenant #1
        $otherUsers = User::whereNull('current_tenant_id')
            ->orWhere('current_tenant_id', 0)
            ->get();

        foreach ($otherUsers as $user) {
            $user->update(['current_tenant_id' => $tenant->id]);
            $username = strtolower(preg_replace('/[^a-z0-9_.-]+/i', '_', strstr($user->email, '@', true) ?: 'user'.$user->id));

            DB::table('tenant_user')->updateOrInsert(
                [
                    'tenant_id' => $tenant->id,
                    'user_id' => $user->id,
                ],
                [
                    'username' => $username,
                    'role' => 'staff',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
