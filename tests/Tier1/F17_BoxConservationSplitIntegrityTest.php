<?php

namespace Tests\Tier1;

use App\Exceptions\BoxConservationException;
use App\Models\DayLoadBatch;
use App\Models\DayLoadEntry;
use App\Models\Dealer;
use App\Models\Vendor;
use App\Services\DayLoadBillingService;
use Tests\E2ETestCase;

/**
 * Feature F17: Box Conservation & Split Integrity
 * Verifies that entry splitting strictly enforces BoxConservationException invariant,
 * scales weights proportionally, and updates versions atomically.
 */
class F17_BoxConservationSplitIntegrityTest extends E2ETestCase
{
    private function createSampleEntry(int $boxes = 50, float $boxWeight = 1000.0, float $emptyWeight = 200.0): DayLoadEntry
    {
        $batch = DayLoadBatch::create(['billing_date' => '2026-10-09', 'status' => 'Open']);
        $vendor = $this->createVendor(['firm_name' => 'Box Vendor']);
        $dealer = $this->createDealer(['firm_name' => 'Box Dealer']);

        return DayLoadEntry::create([
            'batch_id'      => $batch->id,
            'vendor_id'     => $vendor->id,
            'dealer_id'     => $dealer->id,
            'no_of_boxes'   => $boxes,
            'box_weight'    => $boxWeight,
            'empty_weight'  => $emptyWeight,
            'customer_rate' => 120.00,
            'billing_rate'  => 110.00,
            'paper_rate'    => 110.00,
            'version'       => 1,
            'status'        => 'Active',
        ]);
    }

    public function test_valid_box_split_preserves_conservation_sum(): void
    {
        $service = app(DayLoadBillingService::class);
        $entry = $this->createSampleEntry(50, 1000.0, 200.0);
        $dealer2 = $this->createDealer(['firm_name' => 'Split Recipient']);

        [$originalFresh, $newEntry] = $service->splitEntry($entry, [
            'remaining_boxes' => 30,
            'split_boxes'     => 20,
            'dealer_id'       => $dealer2->id,
        ], 'Customer demand shift');

        $this->assertBoxConservation(30, 20, 50);

        $entry->refresh();
        $this->assertEquals(30, $entry->no_of_boxes);
        $this->assertEquals(20, $newEntry->no_of_boxes);
    }

    public function test_violating_box_conservation_throws_box_conservation_exception(): void
    {
        $service = app(DayLoadBillingService::class);
        $entry = $this->createSampleEntry(50);
        $dealer2 = $this->createDealer(['firm_name' => 'Split Recipient 2']);

        $this->expectException(BoxConservationException::class);

        // 30 + 25 = 55 !== 50 (Violates conservation)
        $service->splitEntry($entry, [
            'remaining_boxes' => 30,
            'split_boxes'     => 25,
            'dealer_id'       => $dealer2->id,
        ], 'Invalid split test');
    }

    public function test_split_calculates_proportional_box_and_empty_weights(): void
    {
        $service = app(DayLoadBillingService::class);
        // 50 boxes, 1000kg box weight, 200kg empty weight -> 800kg bird weight
        $entry = $this->createSampleEntry(50, 1000.0, 200.0);
        $dealer2 = $this->createDealer(['firm_name' => 'Split Recipient 3']);

        // Split 20 boxes (40% of load)
        [$originalFresh, $newEntry] = $service->splitEntry($entry, [
            'remaining_boxes' => 30,
            'split_boxes'     => 20,
            'dealer_id'       => $dealer2->id,
        ], 'Proportional weight test');

        $entry->refresh();

        // 40% of 1000 = 400kg box_weight, 40% of 200 = 80kg empty_weight
        $this->assertEquals(400.00, (float) $newEntry->box_weight);
        $this->assertEquals(80.00, (float) $newEntry->empty_weight);

        // Remaining 60%: 600kg box_weight, 120kg empty_weight
        $this->assertEquals(600.00, (float) $entry->box_weight);
        $this->assertEquals(120.00, (float) $entry->empty_weight);
    }

    public function test_original_entry_is_marked_adjusted_with_version_incremented(): void
    {
        $service = app(DayLoadBillingService::class);
        $entry = $this->createSampleEntry(50);
        $dealer2 = $this->createDealer(['firm_name' => 'Split Recipient 4']);

        $service->splitEntry($entry, [
            'remaining_boxes' => 35,
            'split_boxes'     => 15,
            'dealer_id'       => $dealer2->id,
        ], 'Version check test');

        $entry->refresh();
        $this->assertEquals('Adjusted', $entry->status);
        $this->assertEquals(2, $entry->version);
    }

    public function test_split_entry_operation_is_atomic(): void
    {
        $entry = $this->createSampleEntry(50);
        $initialBoxes = $entry->no_of_boxes;

        try {
            $service = app(DayLoadBillingService::class);
            $service->splitEntry($entry, [
                'remaining_boxes' => 10,
                'split_boxes'     => 10, // Sum = 20 != 50 -> throws exception
                'dealer_id'       => 999999,
            ], 'Atomicity test');
        } catch (\Throwable $e) {
            // Expected
        }

        $entry->refresh();
        $this->assertEquals($initialBoxes, $entry->no_of_boxes, 'Database was mutated despite failed split!');
    }
}
