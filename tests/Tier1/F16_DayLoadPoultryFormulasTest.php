<?php

namespace Tests\Tier1;

use App\Models\DayLoadBatch;
use App\Models\DayLoadEntry;
use App\Models\Dealer;
use App\Models\Vendor;
use Tests\E2ETestCase;

/**
 * Feature F16: Day-Load Poultry Formulas Preservation
 * Verifies that all 10 core poultry business formulas produce exact mathematical
 * results as defined in ORIGINAL_REQUEST §R3 and PROJECT.md §F16.
 */
class F16_DayLoadPoultryFormulasTest extends E2ETestCase
{
    public function test_bird_weight_formula_preservation(): void
    {
        $boxWeight = 1250.75;
        $emptyWeight = 250.25;

        $expectedBirdWeight = $this->calculateExpectedBirdWeight($boxWeight, $emptyWeight);
        $this->assertEquals(1000.50, $expectedBirdWeight);

        $batch = DayLoadBatch::create(['billing_date' => '2026-10-09', 'status' => 'Open']);
        $vendor = $this->createVendor(['firm_name' => 'Formula Vendor']);
        $dealer = $this->createDealer(['firm_name' => 'Formula Dealer']);

        $entry = DayLoadEntry::create([
            'batch_id'      => $batch->id,
            'vendor_id'     => $vendor->id,
            'dealer_id'     => $dealer->id,
            'no_of_boxes'   => 50,
            'box_weight'    => $boxWeight,
            'empty_weight'  => $emptyWeight,
            'customer_rate' => 120.00,
            'billing_rate'  => 110.00,
            'paper_rate'    => 110.00,
        ]);

        $this->assertEquals(1000.50, (float) $entry->bird_weight, 'Bird weight calculation invariant failed.');
    }

    public function test_loss_weight_formula_preservation(): void
    {
        $farmWeight = 1050.00;
        $boxWeight = 1250.00;
        $emptyWeight = 250.00; // bird_weight = 1000.00

        $expectedLoss = $this->calculateExpectedLossWeight($farmWeight, 1000.00);
        $this->assertEquals(50.00, $expectedLoss);

        $batch = DayLoadBatch::create(['billing_date' => '2026-10-09', 'status' => 'Open']);
        $vendor = $this->createVendor();
        $dealer = $this->createDealer();

        $entry = DayLoadEntry::create([
            'batch_id'      => $batch->id,
            'vendor_id'     => $vendor->id,
            'dealer_id'     => $dealer->id,
            'no_of_boxes'   => 40,
            'farm_weight'   => $farmWeight,
            'box_weight'    => $boxWeight,
            'empty_weight'  => $emptyWeight,
            'customer_rate' => 130.00,
            'billing_rate'  => 115.00,
            'paper_rate'    => 115.00,
        ]);

        $this->assertEquals(50.00, (float) $entry->loss_weight, 'Loss weight calculation invariant failed.');
    }

    public function test_dealer_income_formula_preservation(): void
    {
        $birdWeight = 1000.00;
        $customerRate = 125.50;

        $expectedIncome = $this->calculateExpectedDealerIncome($birdWeight, $customerRate);
        $this->assertEquals(125500.00, $expectedIncome);

        $batch = DayLoadBatch::create(['billing_date' => '2026-10-09', 'status' => 'Open']);
        $vendor = $this->createVendor();
        $dealer = $this->createDealer();

        $entry = DayLoadEntry::create([
            'batch_id'      => $batch->id,
            'vendor_id'     => $vendor->id,
            'dealer_id'     => $dealer->id,
            'no_of_boxes'   => 30,
            'box_weight'    => 1200.00,
            'empty_weight'  => 200.00,
            'customer_rate' => $customerRate,
            'billing_rate'  => 110.00,
            'paper_rate'    => 110.00,
        ]);

        $this->assertEquals(125500.00, $entry->dealer_income, 'Dealer income calculation invariant failed.');
    }

    public function test_vendor_cost_formula_preservation(): void
    {
        $batch = DayLoadBatch::create(['billing_date' => '2026-10-09', 'status' => 'Open']);
        $vendor = $this->createVendor();
        $dealer = $this->createDealer();

        $entry = DayLoadEntry::create([
            'batch_id'      => $batch->id,
            'vendor_id'     => $vendor->id,
            'dealer_id'     => $dealer->id,
            'no_of_boxes'   => 20,
            'farm_weight'   => 500.00,
            'box_weight'    => 600.00,
            'empty_weight'  => 100.00,
            'customer_rate' => 140.00,
            'billing_rate'  => 120.00,
            'paper_rate'    => 118.00,
        ]);

        // Effective farm weight = 500.00, vendor rate = 120.00
        $expectedVendorCost = $this->calculateExpectedVendorCost(500.00, 120.00);
        $this->assertEquals(60000.00, $expectedVendorCost);
        $this->assertEquals(60000.00, $entry->vendor_cost, 'Vendor cost calculation invariant failed.');
    }

    public function test_gross_margin_formula_preservation(): void
    {
        $batch = DayLoadBatch::create(['billing_date' => '2026-10-09', 'status' => 'Open']);
        $vendor = $this->createVendor();
        $dealer = $this->createDealer();

        $entry = DayLoadEntry::create([
            'batch_id'      => $batch->id,
            'vendor_id'     => $vendor->id,
            'dealer_id'     => $dealer->id,
            'no_of_boxes'   => 20,
            'farm_weight'   => 500.00,
            'box_weight'    => 600.00,
            'empty_weight'  => 100.00, // bird_weight = 500
            'customer_rate' => 140.00, // dealer_income = 500 * 140 = 70000
            'billing_rate'  => 120.00, // vendor_cost = 500 * 120 = 60000
            'paper_rate'    => 120.00,
        ]);

        $expectedMargin = $this->calculateExpectedGrossMargin(70000.00, 60000.00);
        $this->assertEquals(10000.00, $expectedMargin);
        $this->assertEquals(10000.00, $entry->gross_margin, 'Gross margin calculation invariant failed.');
    }
}
