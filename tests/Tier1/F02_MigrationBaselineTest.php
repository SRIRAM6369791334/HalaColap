<?php

namespace Tests\Tier1;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\E2ETestCase;

/**
 * Feature F02: Migration Baseline Synchronization
 * Verifies that the migration tracking table is synchronized, legacy migrations
 * are tracked, and subsequent migrations execute cleanly without collision.
 */
class F02_MigrationBaselineTest extends E2ETestCase
{
    public function test_migrations_table_exists_and_tracks_schema(): void
    {
        $this->assertTrue(Schema::hasTable('migrations'), 'Migrations table does not exist.');
        $this->assertTrue(Schema::hasColumn('migrations', 'migration'), 'Migrations table missing migration column.');
        $this->assertTrue(Schema::hasColumn('migrations', 'batch'), 'Migrations table missing batch column.');
    }

    public function test_baseline_migrations_are_recorded_in_migrations_table(): void
    {
        $count = DB::table('migrations')->count();
        $this->assertGreaterThan(0, $count, 'Migrations table has zero tracked migrations.');
    }

    public function test_migration_table_records_have_valid_structure(): void
    {
        $records = DB::table('migrations')->limit(10)->get();
        foreach ($records as $record) {
            $this->assertNotEmpty($record->migration);
            $this->assertIsNumeric($record->batch);
        }
    }

    public function test_running_migrations_is_idempotent_without_table_collision(): void
    {
        // Calling migrate in current environment should complete without fatal collision
        $exitCode = \Illuminate\Support\Facades\Artisan::call('migrate');
        $this->assertSame(0, $exitCode, 'Artisan migrate command failed to complete cleanly.');
    }

    public function test_database_schema_integrity_core_business_tables_exist(): void
    {
        $coreTables = ['dealers', 'vendors', 'day_load_entries', 'cash_bank_ledgers', 'purchases'];
        foreach ($coreTables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Core table '{$table}' is missing from database schema.");
        }
    }
}
