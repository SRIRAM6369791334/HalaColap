<?php

/**
 * Flockwise BizTrack Multi-Tenancy SaaS - E2E Opaque-Box Test Runner
 *
 * Usage:
 *   php tests/run-all-tests.php
 *   php tests/run-all-tests.php --tier=1
 *   php tests/run-all-tests.php --tier=2
 *   php tests/run-all-tests.php --tier=3
 *   php tests/run-all-tests.php --tier=4
 *   php tests/run-all-tests.php --filter=F01
 */

$baseDir = dirname(__DIR__);
require_once $baseDir . '/vendor/autoload.php';

$options = getopt('', ['tier:', 'filter:', 'verbose', 'help']);

if (isset($options['help'])) {
    echo <<<HELP
Flockwise BizTrack Multi-Tenancy SaaS - Test Runner
Usage:
  php tests/run-all-tests.php [options]

Options:
  --tier=<1|2|3|4>   Run tests for a specific tier (default: run all tiers)
  --filter=<pattern> Run tests matching filter pattern
  --verbose          Display detailed execution output
  --help             Show this help screen

HELP;
    exit(0);
}

$tierArg = $options['tier'] ?? null;
$filterArg = $options['filter'] ?? null;
$verbose = isset($options['verbose']);

$phpunitBin = $baseDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'bin' . DIRECTORY_SEPARATOR . (PHP_OS_FAMILY === 'Windows' ? 'phpunit.bat' : 'phpunit');

if (!file_exists($phpunitBin)) {
    // Fallback to phpunit php script
    $phpunitBin = 'php "' . $baseDir . '/vendor/phpunit/phpunit/phpunit"';
} else {
    $phpunitBin = '"' . $phpunitBin . '"';
}

$cmdArgs = ['--colors=always'];

if ($tierArg) {
    $tierName = 'Tier' . $tierArg;
    $cmdArgs[] = '--testsuite=' . escapeshellarg($tierName);
} elseif (!$filterArg) {
    $cmdArgs[] = '--testsuite=' . escapeshellarg('Tier1,Tier2,Tier3,Tier4');
}

if ($filterArg) {
    $cmdArgs[] = '--filter=' . escapeshellarg($filterArg);
}

$command = "{$phpunitBin} " . implode(' ', $cmdArgs);

echo "\n====================================================================\n";
echo " Flockwise BizTrack - E2E Opaque-Box Test Suite Execution\n";
echo "====================================================================\n";
echo "Time: " . date('Y-m-d H:i:s T') . "\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Database Target: SQLite (In-Memory Isolation / Testing Profile)\n";
if ($tierArg) echo "Target Tier: Tier {$tierArg}\n";
if ($filterArg) echo "Filter: {$filterArg}\n";
echo "Command: {$command}\n";
echo "--------------------------------------------------------------------\n\n";

passthru($command, $exitCode);

echo "\n--------------------------------------------------------------------\n";
if ($exitCode === 0) {
    echo " RESULT: ALL TESTS PASSED (Exit Code: 0)\n";
    echo " Verification successful. Zero regressions or unhandled errors detected.\n";
} else {
    echo " RESULT: TEST SUITE FAILED (Exit Code: {$exitCode})\n";
}
echo "====================================================================\n\n";

exit($exitCode);
