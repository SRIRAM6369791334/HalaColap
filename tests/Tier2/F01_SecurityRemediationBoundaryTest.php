<?php

namespace Tests\Tier2;

use Tests\E2ETestCase;

/**
 * Feature F01 Boundary Tests: Security Remediation
 * Verifies HTTP method boundaries, malformed query params, and directory traversal guards.
 */
class F01_SecurityRemediationBoundaryTest extends E2ETestCase
{
    public function test_post_request_to_export_db_is_rejected(): void
    {
        $response = $this->post('/export-db-2026');
        $this->assertContains($response->status(), [404, 405, 403, 401, 302]);
    }

    public function test_malformed_token_query_to_export_route_is_rejected(): void
    {
        $response = $this->get('/export-db-2026?token=../../etc/passwd');
        $this->assertContains($response->status(), [404, 405, 403, 401, 302]);
    }

    public function test_post_to_import_sql_with_fake_file_is_rejected(): void
    {
        $response = $this->post('/import-sql-dump', ['sql' => 'DROP TABLE users;']);
        $this->assertContains($response->status(), [404, 405, 403, 401, 302]);
    }

    public function test_dotfile_access_in_public_is_blocked(): void
    {
        $this->assertFileDoesNotExist(public_path('.env'));
        $this->assertFileDoesNotExist(public_path('.env.backup'));
    }

    public function test_sql_dump_files_absent_from_public_directory(): void
    {
        $sqlFiles = glob(public_path('*.sql'));
        $this->assertEmpty($sqlFiles, 'Unprotected .sql dump files found in public directory!');
    }
}
