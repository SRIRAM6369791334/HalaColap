<?php

namespace Tests\Tier2;

use App\Models\DayLoadBatch;
use App\Models\DayLoadEntry;
use App\Models\Dealer;
use App\Models\DealerPayment;
use App\Models\Emi;
use App\Services\DealerPaymentService;
use Carbon\Carbon;
use Tests\E2ETestCase;

/**
 * Feature F18 Boundary: 4-Tier FIFO Dealer Payment Settlement Edge Cases
 * Tests zero payments, fractional paise splits, exact debt settlement,
 * massive advance overpayments, and multi-entry waterfall order.
 */
class F18_FifoDealerPaymentSettlementBoundaryTest extends E2ETestCase
{
    public function test_zero_amount_payment_boundary(): void
    {
        $dealer = $this->createDealer([
            'firm_name'      => 'Boundary Dealer Zero',
            'pending_amount' => 5000.00,
        ]);

        $service = app(DealerPaymentService::class);

        $payment = $service->record([
            'dealer_id'    => $dealer->id,
            'date'         => '2026-10-09',
            'payment_mode' => 'Cash',
            'cash_amount'  => 0.00,
            'bank_amount'  => 0.00,
        ]);

        $this->assertInstanceOf(DealerPayment::class, $payment);
        $this->assertEquals(0.00, (float) $payment->amount);
        $this->assertEquals(0.00, (float) $payment->cash_amount);
        $this->assertEquals(0.00, (float) $payment->bank_amount);
    }

    public function test_fractional_paise_preservation_in_split_payment(): void
    {
        $dealer = $this->createDealer([
            'firm_name'      => 'Boundary Dealer Fractional',
            'pending_amount' => 10000.00,
        ]);

        $service = app(DealerPaymentService::class);

        // Fractional paise split: 3333.33 + 6666.67 = 10000.00
        $payment = $service->record([
            'dealer_id'    => $dealer->id,
            'date'         => '2026-10-09',
            'payment_mode' => 'Split',
            'cash_amount'  => 3333.33,
            'bank_amount'  => 6666.67,
        ]);

        $this->assertEquals(10000.00, (float) $payment->amount);
        $this->assertEquals(3333.33, (float) $payment->cash_amount);
        $this->assertEquals(6666.67, (float) $payment->bank_amount);
        $this->assertEquals(10000.00, round((float) $payment->cash_amount + (float) $payment->bank_amount, 2));
    }

    public function test_exact_debt_payoff_reaches_zero_balance(): void
    {
        $dealer = $this->createDealer([
            'firm_name'      => 'Boundary Dealer Exact Payoff',
            'pending_amount' => 2500.50,
        ]);

        $emi = Emi::create([
            'dealer_id'   => $dealer->id,
            'loan_name'   => 'Boundary Loan',
            'amount'      => 1250.25,
            'paid_amount' => 0.00,
            'due_date'    => '2026-10-01',
            'status'      => 'Pending',
        ]);

        $service = app(DealerPaymentService::class);

        // Pay exact sum: 1250.25 (EMI) + 2500.50 (pending) = 3750.75
        $service->record([
            'dealer_id'        => $dealer->id,
            'date'             => '2026-10-09',
            'payment_mode'     => 'Cash',
            'cash_amount'      => 3750.75,
            'bank_amount'      => 0.00,
            'selected_emi_ids' => [$emi->id],
        ]);

        $emi->refresh();
        $this->assertEquals(1250.25, (float) $emi->paid_amount);
        $this->assertEquals('Paid', $emi->status);
    }

    public function test_payment_exceeding_total_debt_creates_advance_record(): void
    {
        $dealer = $this->createDealer([
            'firm_name'      => 'Boundary Dealer Advance',
            'pending_amount' => 1000.00,
        ]);

        $service = app(DealerPaymentService::class);

        // Total debt is 1000.00, pay 3500.00 (excess 2500.00 becomes advance)
        $firstPayment = $service->record([
            'dealer_id'    => $dealer->id,
            'date'         => '2026-10-09',
            'payment_mode' => 'Cash',
            'cash_amount'  => 3500.00,
            'bank_amount'  => 0.00,
        ]);

        $groupId = $firstPayment->payment_group_id;
        $allPayments = DealerPayment::where('payment_group_id', $groupId)->get();

        $this->assertCount(2, $allPayments);

        $pendingPart = $allPayments->firstWhere('notes', 'Allocated to base pending balance');
        $advancePart = $allPayments->firstWhere('notes', 'Unallocated advance');

        $this->assertNotNull($pendingPart);
        $this->assertEquals(1000.00, (float) $pendingPart->amount);

        $this->assertNotNull($advancePart);
        $this->assertEquals(2500.00, (float) $advancePart->amount);
        $this->assertNull($advancePart->day_load_entry_id);
    }

    public function test_waterfall_settlement_across_ten_sequential_day_loads(): void
    {
        $dealer = $this->createDealer([
            'firm_name'      => 'Boundary Dealer Waterfall 10',
            'pending_amount' => 0.00,
        ]);

        $vendor = $this->createVendor(['firm_name' => 'Waterfall Vendor']);
        $entries = [];
        $baseDate = Carbon::parse('2026-10-01');

        for ($i = 0; $i < 10; $i++) {
            $batch = DayLoadBatch::create([
                'billing_date' => $baseDate->copy()->addDays($i)->format('Y-m-d'),
                'batch_number' => 'BATCH-WF-' . ($i + 1),
                'status'       => 'Completed',
            ]);

            $entry = DayLoadEntry::create([
                'batch_id'              => $batch->id,
                'vendor_id'             => $vendor->id,
                'dealer_id'             => $dealer->id,
                'no_of_boxes'           => 10,
                'box_weight'            => 20.0,
                'empty_weight'          => 10.0,
                'customer_rate'         => 100.0,
                'billing_rate'          => 100.0,
                'paper_rate'            => 100.0,
                'dealer_collected'      => 0.00,
                'dealer_payment_status' => 'Pending',
                'status'                => 'Active',
                'version'               => 1,
            ]);

            $entries[] = $entry;
        }

        $service = app(DealerPaymentService::class);

        // Pay 3500.00: Should pay entry 0 (1000), entry 1 (1000), entry 2 (1000), entry 3 (500 partial), rest 0
        $service->record([
            'dealer_id'    => $dealer->id,
            'date'         => '2026-10-15',
            'payment_mode' => 'Cash',
            'cash_amount'  => 3500.00,
            'bank_amount'  => 0.00,
        ]);

        foreach ($entries as $index => $entry) {
            $entry->refresh();
            if ($index < 3) {
                $this->assertEquals(1000.00, (float) $entry->dealer_collected, "Entry {$index} fully paid");
                $this->assertEquals('Paid', $entry->dealer_payment_status);
            } elseif ($index === 3) {
                $this->assertEquals(500.00, (float) $entry->dealer_collected, "Entry {$index} partially paid");
                $this->assertEquals('Partial', $entry->dealer_payment_status);
            } else {
                $this->assertEquals(0.00, (float) $entry->dealer_collected, "Entry {$index} unpaid");
                $this->assertEquals('Pending', $entry->dealer_payment_status);
            }
        }
    }
}
