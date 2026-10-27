<?php

namespace Tests\Tier2;

use Illuminate\Support\Facades\DB;
use Tests\E2ETestCase;

/**
 * Feature F02 Boundary Tests: Migration Baseline Synchronization
 * Verifies boundary states for migrations tracking, batch numbers, and idempotency.
 */
class F02_MigrationBaselineBoundaryTest extends E2ETestCase
{
    public function test_zero_duplicate_migration_names_in_migrations_table(): void
    {
        $duplicates = DB::table('migrations')
            ->select('migration', DB::raw('count(*) as count'))
            ->groupBy('migration')
            ->having('count', '>', 1)
            ->get();

        $this->assertCount(0, $duplicates, 'Found duplicate migration records in migrations table.');
    }

    public function test_batch_numbers_are_positive_integers(): void
    {
        $invalidBatches = DB::table('migrations')
            ->where('batch', '<=', 0)
            ->orWhereNull('batch')
            ->count();

        $this->assertSame(0, $invalidBatches, 'Found migrations with non-positive or null batch numbers.');
    }

    public function test_migration_records_have_valid_filename_format(): void
    {
        $records = DB::table('migrations')->pluck('migration');
        foreach ($records as $m) {
            $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_]+$/', $m);
        }
    }

    public function test_repeated_migration_check_does_not_mutate_state(): void
    {
        $initialCount = DB::table('migrations')->count();
        \Illuminate\Support\Facades\Artisan::call('migrate:status');
        $afterCount = DB::table('migrations')->count();

        $this->assertSame($initialCount, $afterCount);
    }

    public function test_empty_string_migration_name_does_not_exist(): void
    {
        $emptyRecords = DB::table('migrations')->where('migration', '')->count();
        $this->assertSame(0, $emptyRecords);
    }
}
