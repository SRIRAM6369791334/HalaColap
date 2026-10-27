<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('email', 'like', '%demo%')->orWhere('id', 70)->first();
if (!$user) {
    $user = \App\Models\User::create([
        'name' => 'Demo MD',
        'username' => 'demo_md',
        'email' => 'demo@gmail.com',
        'password' => \Illuminate\Support\Facades\Hash::make('password'),
        'is_active' => true,
        'is_super_admin' => false,
    ]);
}

$tenant = $user->tenants()->first();
if (!$tenant) {
    $tenant = \App\Models\Tenant::firstOrCreate(
        ['slug' => 'demo-poultry'],
        [
            'name' => 'Demo Poultry Farm',
            'company_name' => 'Demo Poultry Farm',
            'email' => 'demo@gmail.com',
            'is_active' => true,
        ]
    );
    $user->current_tenant_id = $tenant->id;
    $user->save();
    \Illuminate\Support\Facades\DB::table('tenant_user')->updateOrInsert(
        ['tenant_id' => $tenant->id, 'user_id' => $user->id],
        ['username' => 'admin', 'role' => 'admin', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]
    );
    $user->load('tenants');
}

echo "Testing User: {$user->name} ({$user->email}), is_super_admin: " . ($user->is_super_admin ? 'YES' : 'NO') . PHP_EOL;
echo "Active Tenant: {$tenant->name} (ID: {$tenant->id})" . PHP_EOL;

auth()->login($user);

app(\App\Services\Tenancy\TenantContext::class)->setTenant($tenant);
if (function_exists('setPermissionsTeamId')) {
    setPermissionsTeamId($tenant->id);
}

$view = view('partials.sidebar')->render();

$items = [
    'Dashboard' => true,
    'Dayload Billing' => true,
    'Customers' => true,
    'Dealers' => true,
    'Vendors' => true,
    'Daily Billing Generate' => true,
    'Weekly Billing Generate' => true,
    'Purchases' => true,
    'Purchase Invoices' => true,
    'Dealer Payments' => true,
    'Vendor Payments' => true,
    'Cash & Bank Ledger' => true,
    'Capital & Investments' => true,
    'Expenses' => true,
    'EMI Records' => true,
    'Profit & Loss' => true,
    'User Management' => true,
    'Role Management' => true,
    'Permissions' => true,
    'Business Accounts (SaaS)' => false, // MUST BE HIDDEN FOR TENANT ADMIN!
];

$allPassed = true;
echo PHP_EOL . "=== MENU VISIBILITY VERIFICATION FOR TENANT ADMIN ===" . PHP_EOL;
foreach ($items as $item => $shouldBeVisible) {
    $found = (strpos($view, $item) !== false) || (strpos($view, e($item)) !== false);
    $passed = ($found === $shouldBeVisible);
    if (!$passed) {
        $allPassed = false;
    }
    $status = $passed ? "[OK]" : "[FAIL]";
    $state = $found ? "VISIBLE" : "HIDDEN ";
    $expected = $shouldBeVisible ? "EXPECTED VISIBLE" : "EXPECTED HIDDEN";
    echo "{$status} {$item} -> {$state} ({$expected})" . PHP_EOL;
}

// Diagnostics
echo PHP_EOL . "--- DIAGNOSTICS FOR HIDDEN ITEMS ---" . PHP_EOL;
foreach (['billing.cash-bank-ledger.index', 'billing.investments.index', 'profit.index'] as $routeName) {
    $exists = \Illuminate\Support\Facades\Route::has($routeName);
    echo "Route '{$routeName}': " . ($exists ? "EXISTS" : "DOES NOT EXIST") . PHP_EOL;
}

// Brand check
$hasBrand = strpos($view, $tenant->name) !== false;
echo PHP_EOL . "Brand Display: " . ($hasBrand ? "[OK] Shows '{$tenant->name}'" : "[FAIL] Did not show '{$tenant->name}'") . PHP_EOL;

// Super Admin test
echo PHP_EOL . "=== MENU VISIBILITY VERIFICATION FOR SUPER ADMIN ===" . PHP_EOL;
$superAdmin = \App\Models\User::where('email', 'admin@poultry.com')->first();
auth()->login($superAdmin);
$superView = view('partials.sidebar')->render();
$hasSaas = strpos($superView, 'Business Accounts (SaaS)') !== false;
echo "Super Admin SaaS Menu: " . ($hasSaas ? "[OK] VISIBLE" : "[FAIL] HIDDEN") . PHP_EOL;

echo PHP_EOL . "RESULT: " . ($allPassed && $hasBrand && $hasSaas ? "ALL CHECKS PASSED PERFECTLY!" : "SOME CHECKS FAILED!") . PHP_EOL;
