<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;

class RoutePurgeTest extends TestCase
{
    /**
     * Test /export-db-2026 is completely purged and returns 404 Not Found.
     */
    public function test_export_db_route_returns_404_not_found(): void
    {
        $response = $this->get('/export-db-2026');
        $response->assertNotFound();
    }

    /**
     * Test /import-sql-dump is completely purged and returns 404 Not Found.
     */
    public function test_import_sql_dump_route_returns_404_not_found(): void
    {
        $response = $this->get('/import-sql-dump');
        $response->assertNotFound();
    }

    /**
     * Test /run-updates is completely purged and returns 404 Not Found.
     */
    public function test_run_updates_route_returns_404_not_found(): void
    {
        $response = $this->get('/run-updates');
        $response->assertNotFound();
    }

    /**
     * Test /run-batch-migration is completely purged and returns 404 Not Found.
     */
    public function test_run_batch_migration_route_returns_404_not_found(): void
    {
        $response = $this->get('/run-batch-migration');
        $response->assertNotFound();
    }

    /**
     * Test billing/live-deploy-sync-2026 returns 404 when unauthenticated.
     */
    public function test_billing_live_deploy_sync_unauthenticated_returns_404(): void
    {
        $response = $this->get('/billing/live-deploy-sync-2026');
        $response->assertNotFound();
    }

    /**
     * Test billing/live-deploy-sync-2026 returns 404 even when authenticated.
     */
    public function test_billing_live_deploy_sync_authenticated_returns_404(): void
    {
        $user = new User([
            'id' => 1,
            'name' => 'Test Admin',
            'email' => 'admin@poultry.com',
            'is_active' => true,
            'is_super_admin' => true,
        ]);

        $response = $this->actingAs($user)->get('/billing/live-deploy-sync-2026');
        $response->assertNotFound();
    }
}
