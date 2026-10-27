<?php

namespace Tests\Tier2;

use App\Exceptions\BoxConservationException;
use App\Models\DayLoadEntry;
use App\Services\DayLoadBillingService;
use Tests\E2ETestCase;

/**
 * Feature F17 Boundary Tests: Box Conservation & Split Integrity
 * Verifies single-box minimal splits, odd-box splits, zero/negative box validations, and large box counts.
 */
class F17_BoxConservationSplitIntegrityBoundaryTest extends E2ETestCase
{
    private function createFixtureEntry(int $boxes): DayLoadEntry
    {
        $batch = $this->createDayLoadBatch();
        $vendor = $this->createVendor();
        $dealer = $this->createDealer();

        return DayLoadEntry::create([
            'batch_id'      => $batch->id,
            'vendor_id'     => $vendor->id,
            'dealer_id'     => $dealer->id,
            'no_of_boxes'   => $boxes,
            'box_weight'    => (float) ($boxes * 20),
            'empty_weight'  => (float) ($boxes * 4),
            'customer_rate' => 110.00,
            'billing_rate'  => 100.00,
            'paper_rate'    => 100.00,
            'version'       => 1,
            'status'        => 'Active',
        ]);
    }

    public function test_minimal_single_box_split(): void
    {
        $service = app(DayLoadBillingService::class);
        $entry = $this->createFixtureEntry(100);
        $dealer2 = $this->createDealer();

        [$original, $newEntry] = $service->splitEntry($entry, [
            'remaining_boxes' => 99,
            'split_boxes'     => 1,
            'dealer_id'       => $dealer2->id,
        ], 'Minimal split test');

        $this->assertBoxConservation(99, 1, 100);
        $this->assertEquals(99, $original->no_of_boxes);
        $this->assertEquals(1, $newEntry->no_of_boxes);
    }

    public function test_odd_boxes_split(): void
    {
        $service = app(DayLoadBillingService::class);
        $entry = $this->createFixtureEntry(49);
        $dealer2 = $this->createDealer();

        [$original, $newEntry] = $service->splitEntry($entry, [
            'remaining_boxes' => 25,
            'split_boxes'     => 24,
            'dealer_id'       => $dealer2->id,
        ], 'Odd box count split');

        $this->assertBoxConservation(25, 24, 49);
        $this->assertEquals(25, $original->no_of_boxes);
        $this->assertEquals(24, $newEntry->no_of_boxes);
    }

    public function test_negative_boxes_split_throws_box_conservation_exception(): void
    {
        $service = app(DayLoadBillingService::class);
        $entry = $this->createFixtureEntry(50);
        $dealer2 = $this->createDealer();

        $this->expectException(BoxConservationException::class);
        $service->splitEntry($entry, [
            'remaining_boxes' => 50,
            'split_boxes'     => -5, // 50 + (-5) = 45 !== 50 violates conservation sum
            'dealer_id'       => $dealer2->id,
        ], 'Negative split test');
    }

    public function test_sum_exceeding_original_boxes_throws_exception(): void
    {
        $service = app(DayLoadBillingService::class);
        $entry = $this->createFixtureEntry(50);
        $dealer2 = $this->createDealer();

        $this->expectException(BoxConservationException::class);
        $service->splitEntry($entry, [
            'remaining_boxes' => 30,
            'split_boxes'     => 30, // 30 + 30 = 60 !== 50
            'dealer_id'       => $dealer2->id,
        ], 'Over split test');
    }

    public function test_large_box_count_split(): void
    {
        $service = app(DayLoadBillingService::class);
        $entry = $this->createFixtureEntry(10000);
        $dealer2 = $this->createDealer();

        [$original, $newEntry] = $service->splitEntry($entry, [
            'remaining_boxes' => 6000,
            'split_boxes'     => 4000,
            'dealer_id'       => $dealer2->id,
        ], 'Large volume split');

        $this->assertBoxConservation(6000, 4000, 10000);
        $this->assertEquals(6000, $original->no_of_boxes);
        $this->assertEquals(4000, $newEntry->no_of_boxes);
    }
}
