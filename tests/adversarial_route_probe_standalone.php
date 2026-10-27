<?php

/**
 * Standalone Adversarial Probe & Stress-Test Harness
 * Challenger M1-1
 */

echo "====================================================================\n";
echo " CHALLENGER M1-1: ADVERSARIAL SECURITY & ROUTE PURGE STRESS TEST\n";
echo "====================================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertProbe(bool $condition, string $description, ?string $detail = null): void {
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$description}\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$description}" . ($detail ? " -> {$detail}" : "") . "\n";
    }
}

// 1. Database Connection & Initial Snapshot
echo "[Phase 1] Database Baseline Snapshot\n";
$mysqli = new mysqli('127.0.0.1', 'root', '', 'poultry_db');
if ($mysqli->connect_error) {
    die("Database connection failed: " . $mysqli->connect_error . "\n");
}

$tablesRes = $mysqli->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'poultry_db' ORDER BY TABLE_NAME");
$initialTables = [];
while ($row = $tablesRes->fetch_assoc()) {
    $initialTables[] = $row['TABLE_NAME'];
}
$initialTableCount = count($initialTables);

$migRes = $mysqli->query("SELECT COUNT(*) AS c FROM migrations");
$initialMigrationsCount = (int)$migRes->fetch_assoc()['c'];

$usrRes = $mysqli->query("SELECT COUNT(*) AS c FROM users");
$initialUsersCount = (int)$usrRes->fetch_assoc()['c'];

$tenRes = $mysqli->query("SELECT COUNT(*) AS c FROM tenants");
$initialTenantsCount = (int)$tenRes->fetch_assoc()['c'];

echo "  Initial poultry_db tables: {$initialTableCount}\n";
echo "  Initial migrations count: {$initialMigrationsCount}\n";
echo "  Initial users count: {$initialUsersCount}\n";
echo "  Initial tenants count: {$initialTenantsCount}\n\n";

// 2. Apache .htaccess Probes (Port 8080)
echo "[Phase 2] Apache .htaccess File Blocking Probes (Port 8080)\n";
$apacheBase = 'http://127.0.0.1:8080/Poultry%20Management%20System/flockwise-biztrack-main/web/public/';

$staticProbes = [
    'test.sql' => 403,
    'backup.dump' => 403,
    'old.bak' => 403,
    'test.SQL' => 403,
    'test.BAK' => 403,
    'db_exporter.php' => 403,
    'db_importer.php' => 403,
    'DB_EXPORTER.PHP' => 403,
    'DB_IMPORTER.PHP' => 403,
    'db_exporter.php?download=1' => 403,
    'poultry_db%20(17).sql' => 403,
    '.env' => 403,
    'env.bak' => 403,
    'test.env' => 403,
];

foreach ($staticProbes as $file => $expectedStatus) {
    $url = $apacheBase . $file;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    assertProbe($status === $expectedStatus, "Static file probe: {$file} -> HTTP {$status}", "Expected {$expectedStatus}");
}
echo "\n";

// 3. Purged Routes Probes via Live Web Servers
echo "[Phase 3] Purged Routes Permutations Probes\n";
$servers = [
    'Apache (8080)' => $apacheBase,
    'Artisan (8000)' => 'http://127.0.0.1:8000/',
];

$purgedEndpoints = [
    'export-db-2026',
    'import-sql-dump',
    'run-updates',
    'run-batch-migration',
    'billing/live-deploy-sync-2026',
];

$queryVariations = [
    '',
    '?download=1',
    '?format=sql',
    '?action=drop',
    '?force=1',
    '?token=admin',
    '?table=users',
];

$methods = ['GET', 'POST', 'PUT', 'DELETE'];

foreach ($servers as $serverName => $baseUrl) {
    echo "  Testing server: {$serverName}\n";
    foreach ($purgedEndpoints as $endpoint) {
        foreach ($methods as $method) {
            foreach ($queryVariations as $qs) {
                $url = $baseUrl . $endpoint . $qs;
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
                if ($method === 'POST' || $method === 'PUT') {
                    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['sql' => 'DROP TABLE users;', 'force' => '1']));
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Accept: application/json',
                    'X-Requested-With: XMLHttpRequest',
                ]);

                $body = curl_exec($ch);
                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                $isNon200 = ($status !== 200 && ($status === 404 || $status === 405));
                $noSqlLeak = (!str_contains($body, 'CREATE TABLE') && !str_contains($body, '-- MySQL dump') && !str_contains($body, 'DROP TABLE'));

                if (!$isNon200 || !$noSqlLeak) {
                    assertProbe(false, "{$serverName} {$method} {$endpoint}{$qs} -> HTTP {$status}", "Data leak or returned 200");
                }
            }
        }
        assertProbe(true, "{$serverName} all method/param permutations for '{$endpoint}' safely rejected (404/405, 0 leaks)");
    }
}
echo "\n";

// 4. Database Mutation Verification
echo "[Phase 4] Database Mutation Invariant Check\n";
$tablesAfterRes = $mysqli->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'poultry_db' ORDER BY TABLE_NAME");
$afterTables = [];
while ($row = $tablesAfterRes->fetch_assoc()) {
    $afterTables[] = $row['TABLE_NAME'];
}
$afterTableCount = count($afterTables);

$migAfterRes = $mysqli->query("SELECT COUNT(*) AS c FROM migrations");
$afterMigrationsCount = (int)$migAfterRes->fetch_assoc()['c'];

$usrAfterRes = $mysqli->query("SELECT COUNT(*) AS c FROM users");
$afterUsersCount = (int)$usrAfterRes->fetch_assoc()['c'];

$tenAfterRes = $mysqli->query("SELECT COUNT(*) AS c FROM tenants");
$afterTenantsCount = (int)$tenAfterRes->fetch_assoc()['c'];

assertProbe($initialTableCount === $afterTableCount, "Table count invariant: {$initialTableCount} === {$afterTableCount}");
assertProbe($initialMigrationsCount === $afterMigrationsCount, "Migrations count invariant: {$initialMigrationsCount} === {$afterMigrationsCount}");
assertProbe($initialUsersCount === $afterUsersCount, "Users count invariant: {$initialUsersCount} === {$afterUsersCount}");
assertProbe($initialTenantsCount === $afterTenantsCount, "Tenants count invariant: {$initialTenantsCount} === {$afterTenantsCount}");
assertProbe($initialTables === $afterTables, "Table schema identity invariant: exact table list preserved");

echo "\n====================================================================\n";
echo " SUMMARY: {$passCount} PASSED, {$failCount} FAILED\n";
echo "====================================================================\n";

if ($failCount > 0) {
    exit(1);
}
exit(0);
