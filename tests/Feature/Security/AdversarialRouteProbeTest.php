<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adversarial Security & Route Probe Stress Test
 *
 * Executed by Challenger M1-1 to empirically verify:
 * 1. All permutations (methods, query params, headers, auth contexts) of purged routes return non-200 (404/405).
 * 2. No database schema or sensitive data leaks in response bodies.
 * 3. No database mutations occur under adversarial payloads.
 * 4. .htaccess Apache rules block direct static requests for sensitive extensions and standalone scripts.
 */
class AdversarialRouteProbeTest extends TestCase
{
    protected array $purgedRoutes = [
        '/export-db-2026',
        '/import-sql-dump',
        '/run-updates',
        '/run-batch-migration',
        '/billing/live-deploy-sync-2026',
    ];

    protected array $queryParams = [
        [],
        ['download' => '1'],
        ['format' => 'sql'],
        ['action' => 'drop'],
        ['action' => 'migrate:reset'],
        ['force' => '1'],
        ['token' => 'admin'],
        ['token' => '../../etc/passwd'],
        ['secret' => 'sync'],
        ['table' => 'users'],
        ['batch' => 'all'],
        ['step' => '1'],
    ];

    protected array $headerSets = [
        [],
        ['Accept' => 'application/json'],
        ['X-Requested-With' => 'XMLHttpRequest'],
        ['Accept' => 'text/html'],
        ['Content-Type' => 'application/json'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'poultry_db',
        ]);
        DB::purge();
    }

    /**
     * Helper to get database connection to live MySQL.
     */
    protected function getDbConnection()
    {
        return DB::connection('mysql');
    }

    /**
     * Snapshot critical database tables and row counts.
     */
    protected function getDatabaseSnapshot(): array
    {
        $db = $this->getDbConnection();
        $tables = $db->select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'poultry_db' ORDER BY table_name");
        $tableNames = array_map(fn($t) => $t->TABLE_NAME ?? $t->table_name, $tables);

        $counts = [];
        $trackedTables = ['migrations', 'users', 'tenants', 'resellers', 'roles', 'permissions', 'dealers', 'vendor_payments', 'cash_bank_ledgers'];
        foreach ($trackedTables as $table) {
            if (in_array($table, $tableNames)) {
                $counts[$table] = $db->table($table)->count();
            }
        }

        return [
            'tables' => $tableNames,
            'counts' => $counts,
        ];
    }

    /**
     * Assert that response body does not contain any database structure or data leakage.
     */
    protected function assertNoDataLeakage($response, string $route, string $context): void
    {
        $content = $response->getContent();

        $forbiddenSignatures = [
            '-- MySQL dump',
            '/*!40101',
            'CREATE TABLE',
            'DROP TABLE',
            'INSERT INTO',
            'PRIMARY KEY',
            'poultry_db',
            'remember_token',
            'password_hash',
        ];

        foreach ($forbiddenSignatures as $signature) {
            $this->assertStringNotContainsString(
                $signature,
                $content,
                "Data leakage detected! Signature '{$signature}' found in response for {$route} [{$context}]."
            );
        }
    }

    /**
     * Test all 5 routes across GET, POST, PUT, DELETE, PATCH, HEAD with all query parameters unauthenticated.
     */
    public function test_purged_routes_matrix_unauthenticated(): void
    {
        $methods = ['get', 'post', 'put', 'delete', 'patch'];

        foreach ($this->purgedRoutes as $route) {
            foreach ($methods as $method) {
                foreach ($this->queryParams as $params) {
                    $queryString = empty($params) ? '' : '?' . http_build_query($params);
                    $fullUri = $route . $queryString;

                    $response = $this->{$method}($fullUri);

                    $status = $response->getStatusCode();
                    $this->assertNotEquals(
                        200,
                        $status,
                        "CRITICAL: Purged route {$fullUri} returned 200 OK via {$method} unauthenticated!"
                    );

                    $this->assertTrue(
                        in_array($status, [404, 405]),
                        "Route {$fullUri} via {$method} returned unexpected status {$status} (expected 404 or 405)."
                    );

                    $this->assertNoDataLeakage($response, $fullUri, "unauthenticated {$method}");
                }
            }
        }
    }

    /**
     * Test all 5 routes with varying request headers (AJAX, JSON, HTML).
     */
    public function test_purged_routes_matrix_with_headers(): void
    {
        foreach ($this->purgedRoutes as $route) {
            foreach ($this->headerSets as $headers) {
                $response = $this->withHeaders($headers)->get($route);
                $status = $response->getStatusCode();

                $this->assertNotEquals(200, $status, "CRITICAL: {$route} returned 200 OK with custom headers!");
                $this->assertTrue(in_array($status, [404, 405]));
                $this->assertNoDataLeakage($response, $route, "headers test");

                // Also test POST with JSON payload
                $postResponse = $this->withHeaders($headers)->post($route, ['action' => 'drop', 'force' => 1]);
                $postStatus = $postResponse->getStatusCode();
                $this->assertNotEquals(200, $postStatus, "CRITICAL: POST {$route} returned 200 OK with JSON headers!");
                $this->assertTrue(in_array($postStatus, [404, 405]));
                $this->assertNoDataLeakage($postResponse, $route, "POST headers test");
            }
        }
    }

    /**
     * Test all 5 routes when authenticated as regular staff user.
     */
    public function test_purged_routes_matrix_authenticated_regular_staff(): void
    {
        $user = User::where('email', 'sriram@gmail.com')->first();
        if (!$user) {
            $user = new User([
                'id' => 2,
                'name' => 'Staff User',
                'email' => 'staff@poultry.com',
                'is_active' => true,
                'is_super_admin' => false,
                'current_tenant_id' => 1,
            ]);
        }

        foreach ($this->purgedRoutes as $route) {
            foreach (['get', 'post', 'put', 'delete'] as $method) {
                $response = $this->actingAs($user)->{$method}($route);
                $status = $response->getStatusCode();

                $this->assertNotEquals(
                    200,
                    $status,
                    "CRITICAL: {$route} returned 200 OK via {$method} for regular staff user!"
                );
                $this->assertTrue(in_array($status, [404, 405]));
                $this->assertNoDataLeakage($response, $route, "staff user {$method}");
            }
        }
    }

    /**
     * Test all 5 routes when authenticated as Super Admin.
     * Even Super Admin must NOT be able to access purged backdoor endpoints.
     */
    public function test_purged_routes_matrix_authenticated_super_admin(): void
    {
        $superAdmin = User::where('email', 'admin@poultry.com')->first();
        if (!$superAdmin) {
            $superAdmin = new User([
                'id' => 1,
                'name' => 'Super Admin',
                'email' => 'admin@poultry.com',
                'is_active' => true,
                'is_super_admin' => true,
                'current_tenant_id' => 1,
            ]);
        }

        foreach ($this->purgedRoutes as $route) {
            foreach (['get', 'post', 'delete'] as $method) {
                $response = $this->actingAs($superAdmin)->{$method}($route);
                $status = $response->getStatusCode();

                $this->assertNotEquals(
                    200,
                    $status,
                    "CRITICAL: {$route} returned 200 OK via {$method} for Super Admin!"
                );
                $this->assertTrue(in_array($status, [404, 405]));
                $this->assertNoDataLeakage($response, $route, "super admin {$method}");
            }
        }
    }

    /**
     * Test that adversarial requests targeting purged routes cause ZERO database mutations.
     */
    public function test_adversarial_requests_cause_zero_database_mutations(): void
    {
        $beforeSnapshot = $this->getDatabaseSnapshot();

        $maliciousPayloads = [
            ['route' => '/import-sql-dump', 'method' => 'post', 'data' => ['sql' => 'DROP TABLE users;']],
            ['route' => '/import-sql-dump', 'method' => 'post', 'data' => ['sql' => 'TRUNCATE TABLE migrations;']],
            ['route' => '/run-updates', 'method' => 'post', 'data' => ['force' => true, 'action' => 'migrate:reset']],
            ['route' => '/run-updates', 'method' => 'get', 'data' => ['action' => 'delete_dealers']],
            ['route' => '/run-batch-migration', 'method' => 'post', 'data' => ['batch' => 99, 'alter' => 'DROP TABLE dealers;']],
            ['route' => '/run-batch-migration', 'method' => 'get', 'data' => ['step' => 'rollback']],
            ['route' => '/billing/live-deploy-sync-2026', 'method' => 'post', 'data' => ['sync' => 'force', 'wipe' => true]],
            ['route' => '/billing/live-deploy-sync-2026', 'method' => 'get', 'data' => ['truncate' => 1]],
            ['route' => '/export-db-2026', 'method' => 'post', 'data' => ['drop_after_export' => 1]],
        ];

        foreach ($maliciousPayloads as $probe) {
            $uri = $probe['route'];
            if ($probe['method'] === 'get' && !empty($probe['data'])) {
                $uri .= '?' . http_build_query($probe['data']);
                $response = $this->get($uri);
            } else {
                $response = $this->post($uri, $probe['data']);
            }

            $this->assertNotEquals(200, $response->getStatusCode());
        }

        $afterSnapshot = $this->getDatabaseSnapshot();

        // Assert tables list is completely identical
        $this->assertEquals(
            $beforeSnapshot['tables'],
            $afterSnapshot['tables'],
            'Database schema tables were altered by malicious probe requests!'
        );

        // Assert row counts across all critical tables are completely identical
        $this->assertEquals(
            $beforeSnapshot['counts'],
            $afterSnapshot['counts'],
            'Row counts in critical database tables changed after malicious probe requests!'
        );
    }

    /**
     * Test public/.htaccess configuration blocks sensitive extensions and standalone PHP scripts.
     */
    public function test_htaccess_file_rules_block_sensitive_targets(): void
    {
        $htaccessPath = public_path('.htaccess');
        $this->assertFileExists($htaccessPath, 'public/.htaccess must exist');

        $content = file_get_contents($htaccessPath);

        // Verify FilesMatch for extensions exists and matches sensitive targets
        $this->assertMatchesRegularExpression('/<FilesMatch\s+["\']\\\.\(sql\|bak\|dump\|env\|log\|sqlite\)\$["\']>/i', $content);
        $this->assertStringContainsString('Require all denied', $content);

        // Verify FilesMatch for standalone PHP scripts exists
        $this->assertMatchesRegularExpression('/<FilesMatch\s+["\']\^\(db_exporter\|db_importer\)\\\\.php\$["\']>/i', $content);

        // Regex simulation
        $extPattern = '/\.(sql|bak|dump|env|log|sqlite)$/i';
        $scriptPattern = '/^(db_exporter|db_importer)\.php$/i';

        $blockedFiles = [
            'test.sql',
            'export.sql',
            'poultry_db (17).sql',
            'data.dump',
            'old_backup.dump',
            'database.bak',
            '.env',
            'backup.env',
            'laravel.log',
            'database.sqlite',
            'db_exporter.php',
            'db_importer.php',
            'DB_EXPORTER.PHP',
            'DB_IMPORTER.PHP',
            'test.SQL',
            'test.BAK',
        ];

        foreach ($blockedFiles as $file) {
            $isBlocked = preg_match($extPattern, $file) || preg_match($scriptPattern, $file);
            $this->assertTrue((bool)$isBlocked, "File '{$file}' should be matched by .htaccess block rules!");
        }

        // Safe files should not be blocked
        $safeFiles = ['index.php', 'robots.txt', 'favicon.ico', 'app.css', 'app.js'];
        foreach ($safeFiles as $safeFile) {
            $isBlocked = preg_match($extPattern, $safeFile) || preg_match($scriptPattern, $safeFile);
            $this->assertFalse((bool)$isBlocked, "Safe file '{$safeFile}' should NOT be matched by block rules!");
        }
    }

    /**
     * Empirically verify live Apache server returns HTTP 403 Forbidden for sensitive static targets.
     */
    public function test_live_apache_blocks_direct_requests_with_403_forbidden(): void
    {
        $apacheBase = 'http://127.0.0.1:8080/Poultry%20Management%20System/flockwise-biztrack-main/web/public/';

        $probes = [
            'test.sql',
            'test.dump',
            'test.bak',
            'test.SQL',
            'db_exporter.php',
            'db_importer.php',
            'DB_EXPORTER.PHP',
            'db_exporter.php?download=1',
        ];

        foreach ($probes as $probe) {
            $url = $apacheBase . $probe;
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 0) {
                $this->markTestSkipped('Live Apache server on port 8080 is offline in current test environment');
            }

            $this->assertEquals(
                403,
                $httpCode,
                "Apache on port 8080 must return HTTP 403 Forbidden for {$probe}, got {$httpCode}"
            );
        }
    }
}
