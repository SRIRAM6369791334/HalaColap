<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Challenger M2-2 Replacement: Standalone Cascade Stress Test ===\n";

// 1. Establish MySQL connection
config([
    'database.default' => 'mysql',
    'database.connections.mysql.database' => 'poultry_db',
]);
DB::purge();
DB::setDefaultConnection('mysql');

$driver = DB::getDriverName();
echo "Connected database driver: {$driver}\n";
if ($driver !== 'mysql') {
    echo "FATAL: Not connected to MySQL\n";
    exit(1);
}

// 2. Insert test tenant
$testSlug = 'stress-tenant-' . uniqid();
$tenantId = DB::table('tenants')->insertGetId([
    'name' => 'Adversarial Stress Test Tenant',
    'slug' => $testSlug,
    'company_name' => 'Stress Test Enterprise',
    'is_active' => 1,
    'created_at' => now(),
    'updated_at' => now(),
]);
echo "Created test tenant ID: {$tenantId} (slug: {$testSlug})\n";

// 3. Insert multiple warehouses
$wh1 = DB::table('warehouses')->insertGetId([
    'tenant_id' => $tenantId,
    'name' => 'Main Silo ' . uniqid(),
    'location' => 'Block A',
    'created_at' => now(),
    'updated_at' => now(),
]);
$wh2 = DB::table('warehouses')->insertGetId([
    'tenant_id' => $tenantId,
    'name' => 'Feed Storage ' . uniqid(),
    'location' => 'Block B',
    'created_at' => now(),
    'updated_at' => now(),
]);
echo "Created warehouses: {$wh1}, {$wh2}\n";

// 4. Insert multiple items
$item1 = DB::table('items')->insertGetId([
    'tenant_id' => $tenantId,
    'name' => 'Starter Feed ' . uniqid(),
    'code' => 'FEED-STR-' . uniqid(),
    'type' => 'feed',
    'base_unit' => 'kg',
    'created_at' => now(),
    'updated_at' => now(),
]);
$item2 = DB::table('items')->insertGetId([
    'tenant_id' => $tenantId,
    'name' => 'Finisher Feed ' . uniqid(),
    'code' => 'FEED-FIN-' . uniqid(),
    'type' => 'feed',
    'base_unit' => 'kg',
    'created_at' => now(),
    'updated_at' => now(),
]);
$item3 = DB::table('items')->insertGetId([
    'tenant_id' => $tenantId,
    'name' => 'Vaccine B ' . uniqid(),
    'code' => 'MED-VAC-' . uniqid(),
    'type' => 'medicine',
    'base_unit' => 'vial',
    'created_at' => now(),
    'updated_at' => now(),
]);
echo "Created items: {$item1}, {$item2}, {$item3}\n";

// 5. Insert multiple batches
$batch1 = DB::table('batches')->insertGetId([
    'tenant_id' => $tenantId,
    'batch_code' => 'BATCH-A-' . uniqid(),
    'placement_date' => '2026-10-01',
    'initial_count' => 1000,
    'current_count' => 980,
    'status' => 'Active',
    'created_at' => now(),
    'updated_at' => now(),
]);
$batch2 = DB::table('batches')->insertGetId([
    'tenant_id' => $tenantId,
    'batch_code' => 'BATCH-B-' . uniqid(),
    'placement_date' => '2026-10-05',
    'initial_count' => 2000,
    'current_count' => 1990,
    'status' => 'Active',
    'created_at' => now(),
    'updated_at' => now(),
]);
echo "Created batches: {$batch1}, {$batch2}\n";

// 6. Insert multiple stock_ledgers covering combinations
// Row A: Full references (item, batch, warehouse)
$sl1 = DB::table('stock_ledgers')->insertGetId([
    'tenant_id' => $tenantId,
    'transaction_date' => '2026-10-09',
    'item_id' => $item1,
    'batch_id' => $batch1,
    'warehouse_id' => $wh1,
    'type' => 'IN',
    'source_type' => 'manual',
    'source_id' => 1,
    'unit' => 'kg',
    'quantity' => 500,
    'created_at' => now(),
    'updated_at' => now(),
]);

// Row B: Item + Warehouse, NULL batch
$sl2 = DB::table('stock_ledgers')->insertGetId([
    'tenant_id' => $tenantId,
    'transaction_date' => '2026-10-09',
    'item_id' => $item2,
    'batch_id' => null,
    'warehouse_id' => $wh2,
    'type' => 'IN',
    'source_type' => 'manual',
    'source_id' => 2,
    'unit' => 'kg',
    'quantity' => 250,
    'created_at' => now(),
    'updated_at' => now(),
]);

// Row C: Item + Batch, NULL warehouse
$sl3 = DB::table('stock_ledgers')->insertGetId([
    'tenant_id' => $tenantId,
    'transaction_date' => '2026-10-09',
    'item_id' => $item3,
    'batch_id' => $batch2,
    'warehouse_id' => null,
    'type' => 'OUT',
    'source_type' => 'manual',
    'source_id' => 3,
    'unit' => 'vial',
    'quantity' => 5,
    'created_at' => now(),
    'updated_at' => now(),
]);

// Row D: Second transaction referencing batch1 and item1
$sl4 = DB::table('stock_ledgers')->insertGetId([
    'tenant_id' => $tenantId,
    'transaction_date' => '2026-10-09',
    'item_id' => $item1,
    'batch_id' => $batch1,
    'warehouse_id' => $wh1,
    'type' => 'OUT',
    'source_type' => 'manual',
    'source_id' => 4,
    'unit' => 'kg',
    'quantity' => 50,
    'created_at' => now(),
    'updated_at' => now(),
]);
echo "Created stock_ledgers: {$sl1}, {$sl2}, {$sl3}, {$sl4}\n";

// 7. Verify pre-deletion record counts
$countTenantBefore = DB::table('tenants')->where('id', $tenantId)->count();
$countWhBefore = DB::table('warehouses')->where('tenant_id', $tenantId)->count();
$countItemsBefore = DB::table('items')->where('tenant_id', $tenantId)->count();
$countBatchesBefore = DB::table('batches')->where('tenant_id', $tenantId)->count();
$countLedgersBefore = DB::table('stock_ledgers')->where('tenant_id', $tenantId)->count();

echo "Pre-deletion counts:\n";
echo "  tenants: {$countTenantBefore}\n";
echo "  warehouses: {$countWhBefore}\n";
echo "  items: {$countItemsBefore}\n";
echo "  batches: {$countBatchesBefore}\n";
echo "  stock_ledgers: {$countLedgersBefore}\n";

if ($countTenantBefore !== 1 || $countWhBefore !== 2 || $countItemsBefore !== 3 || $countBatchesBefore !== 2 || $countLedgersBefore !== 4) {
    echo "FATAL: Pre-deletion counts do not match expected\n";
    exit(1);
}

// 8. Delete parent tenant and measure behavior
echo "Executing: DB::table('tenants')->where('id', {$tenantId})->delete()...\n";

$deleteSuccess = false;
$errorMsg = null;
try {
    $deletedRows = DB::table('tenants')->where('id', $tenantId)->delete();
    $deleteSuccess = true;
    echo "Deletion executed successfully! Rows deleted from tenants: {$deletedRows}\n";
} catch (\Throwable $e) {
    $errorMsg = $e->getMessage();
    echo "EXCEPTION THROWN during tenant deletion:\n" . $errorMsg . "\n";
}

if (!$deleteSuccess) {
    echo "VERIFICATION FAILED: Error encountered during tenant deletion.\n";
    // Attempt emergency cleanup
    DB::table('stock_ledgers')->where('tenant_id', $tenantId)->delete();
    DB::table('batches')->where('tenant_id', $tenantId)->delete();
    DB::table('items')->where('tenant_id', $tenantId)->delete();
    DB::table('warehouses')->where('tenant_id', $tenantId)->delete();
    DB::table('tenants')->where('id', $tenantId)->delete();
    exit(1);
}

// 9. Verify post-deletion orphan check
$countTenantAfter = DB::table('tenants')->where('id', $tenantId)->count();
$countWhAfter = DB::table('warehouses')->where('tenant_id', $tenantId)->count();
$countItemsAfter = DB::table('items')->where('tenant_id', $tenantId)->count();
$countBatchesAfter = DB::table('batches')->where('tenant_id', $tenantId)->count();
$countLedgersAfter = DB::table('stock_ledgers')->where('tenant_id', $tenantId)->count();

echo "Post-deletion orphan counts (tenant_id = {$tenantId}):\n";
echo "  tenants: {$countTenantAfter}\n";
echo "  warehouses: {$countWhAfter}\n";
echo "  items: {$countItemsAfter}\n";
echo "  batches: {$countBatchesAfter}\n";
echo "  stock_ledgers: {$countLedgersAfter}\n";

// Also check by specific IDs
$idOrphans = 0;
if (DB::table('stock_ledgers')->whereIn('id', [$sl1, $sl2, $sl3, $sl4])->count() > 0) $idOrphans++;
if (DB::table('batches')->whereIn('id', [$batch1, $batch2])->count() > 0) $idOrphans++;
if (DB::table('items')->whereIn('id', [$item1, $item2, $item3])->count() > 0) $idOrphans++;
if (DB::table('warehouses')->whereIn('id', [$wh1, $wh2])->count() > 0) $idOrphans++;

echo "Specific ID lookup orphan count: {$idOrphans}\n";

if ($countTenantAfter === 0 && $countWhAfter === 0 && $countItemsAfter === 0 && $countBatchesAfter === 0 && $countLedgersAfter === 0 && $idOrphans === 0) {
    echo "SUCCESS: All records cleanly cascade deleted with 0 orphan records and zero MySQL errors!\n";
    exit(0);
} else {
    echo "FAILURE: Orphan records detected after tenant deletion!\n";
    exit(1);
}
