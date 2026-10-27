<?php

namespace Tests\Tier2;

use App\Models\DayLoadBatch;
use App\Models\DayLoadEntry;
use Tests\E2ETestCase;

/**
 * Feature F16 Boundary Tests: Day-Load Poultry Formulas Preservation
 * Verifies zero loss weight, zero empty weight, extreme rates, negative gross margin, and rounding.
 */
class F16_DayLoadPoultryFormulasBoundaryTest extends E2ETestCase
{
    private function createEntryFixture(array $attributes): DayLoadEntry
    {
        $batch = $this->createDayLoadBatch();
        $vendor = $this->createVendor();
        $dealer = $this->createDealer();

        $defaultData = [
            'batch_id'      => $batch->id,
            'vendor_id'     => $vendor->id,
            'dealer_id'     => $dealer->id,
            'no_of_boxes'   => 10,
            'box_weight'    => 200.0,
            'empty_weight'  => 50.0,
            'customer_rate' => 100.0,
            'billing_rate'  => 90.0,
            'paper_rate'    => 90.0,
        ];

        return DayLoadEntry::create(array_merge($defaultData, $attributes));
    }

    public function test_zero_loss_weight_when_farm_equals_bird_weight(): void
    {
        // 200kg box - 50kg empty = 150kg bird_weight. farm_weight = 150kg
        $entry = $this->createEntryFixture([
            'box_weight'   => 200.00,
            'empty_weight' => 50.00,
            'farm_weight'  => 150.00,
        ]);

        $this->assertEquals(0.00, (float) $entry->loss_weight);
    }

    public function test_zero_empty_weight_boundary(): void
    {
        $entry = $this->createEntryFixture([
            'box_weight'   => 300.00,
            'empty_weight' => 0.00,
        ]);

        $this->assertEquals(300.00, (float) $entry->bird_weight);
    }

    public function test_extreme_rates_calculation(): void
    {
        // High rate: ₹500/kg
        $entryHigh = $this->createEntryFixture([
            'box_weight'    => 200.00,
            'empty_weight'  => 100.00, // 100kg
            'customer_rate' => 500.00,
        ]);

        $this->assertEquals(50000.00, $entryHigh->dealer_income);

        // Low rate: ₹5.50/kg
        $entryLow = $this->createEntryFixture([
            'box_weight'    => 200.00,
            'empty_weight'  => 100.00,
            'customer_rate' => 5.50,
        ]);

        $this->assertEquals(550.00, $entryLow->dealer_income);
    }

    public function test_negative_gross_margin_on_loss_making_transaction(): void
    {
        // Bird weight 100kg, customer_rate = ₹100 -> dealer_income = ₹10,000
        // Farm weight 100kg, billing_rate = ₹120 -> vendor_cost = ₹12,000
        // Gross margin = ₹10,000 - ₹12,000 = -₹2,000
        $entry = $this->createEntryFixture([
            'box_weight'    => 150.00,
            'empty_weight'  => 50.00,
            'farm_weight'   => 100.00,
            'customer_rate' => 100.00,
            'billing_rate'  => 120.00,
            'paper_rate'    => 120.00,
        ]);

        $this->assertEquals(-2000.00, $entry->gross_margin);
    }

    public function test_rounding_fractional_paise_to_two_decimals(): void
    {
        // 100.333kg * 123.456 = 12386.711848 -> 12386.71
        $birdWeight = 100.33;
        $customerRate = 123.45;
        $expected = round($birdWeight * $customerRate, 2);

        $entry = $this->createEntryFixture([
            'box_weight'    => 150.33,
            'empty_weight'  => 50.00,
            'customer_rate' => $customerRate,
        ]);

        $this->assertEquals($expected, $entry->dealer_income);
    }
}
