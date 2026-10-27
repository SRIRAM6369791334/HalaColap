<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

config([
    'database.default' => 'mysql',
    'database.connections.mysql.database' => 'poultry_db',
]);
DB::purge();
DB::setDefaultConnection('mysql');

$tables = [
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

echo "=== Challenger M2-2 Replacement: 39 Business Tables Invalid Tenant ID Rejection ===\n";

$passed = 0;
$failed = 0;

foreach ($tables as $table) {
    $cols = DB::select("SHOW COLUMNS FROM `{$table}`");
    $row = ['tenant_id' => 99999999];

    foreach ($cols as $c) {
        if ($c->Field === 'id' || $c->Field === 'tenant_id') {
            continue;
        }

        if ($c->Null === 'NO' && $c->Default === null) {
            $type = strtolower($c->Type);
            if (str_starts_with($type, 'enum(')) {
                if (preg_match("/enum\('([^']+)'/", $c->Type, $m)) {
                    $row[$c->Field] = $m[1];
                } else {
                    $row[$c->Field] = 'default';
                }
            } elseif (str_contains($type, 'int') || str_contains($type, 'bigint') || str_contains($type, 'tinyint')) {
                $row[$c->Field] = 1;
            } elseif (str_contains($type, 'decimal') || str_contains($type, 'float') || str_contains($type, 'double')) {
                $row[$c->Field] = 10.0;
            } elseif (str_contains($type, 'date')) {
                $row[$c->Field] = '2026-10-09';
            } elseif (str_contains($type, 'timestamp') || str_contains($type, 'datetime')) {
                $row[$c->Field] = '2026-10-09 12:00:00';
            } elseif (str_contains($type, 'json')) {
                $row[$c->Field] = '{}';
            } else {
                $row[$c->Field] = 'test-' . uniqid();
            }
        }
    }

    try {
        DB::table($table)->insert($row);
        echo "FAIL [ALLOWED INSERT]: {$table}\n";
        $failed++;
    } catch (QueryException $e) {
        $msg = $e->getMessage();
        if (str_contains($msg, '1452') || str_contains($msg, 'foreign key constraint fails')) {
            echo "PASS [REJECTED Error 1452]: {$table}\n";
            $passed++;
        } else {
            echo "FAIL [UNEXPECTED ERROR]: {$table} -> {$msg}\n";
            $failed++;
        }
    }
}

echo "\nSummary: Total: " . count($tables) . " | Passed (1452 Rejection): {$passed} | Failed: {$failed}\n";
exit($failed === 0 ? 0 : 1);
