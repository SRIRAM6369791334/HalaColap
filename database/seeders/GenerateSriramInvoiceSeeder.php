<?php

namespace Database\Seeders;

use App\Models\WeeklyBill;
use App\Models\WeeklyBillItem;
use App\Models\DayLoadEntry;
use App\Models\Dealer;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class GenerateSriramInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $dealer = Dealer::find(1);

        $entries = DayLoadEntry::with(['batch', 'vendor'])
            ->where('dealer_id', 1)
            ->where('status', 'Active')
            ->whereHas('batch', function ($q) {
                $q->whereDate('billing_date', '>=', '2026-06-02')
                  ->whereDate('billing_date', '<=', '2026-06-08');
            })
            ->get();

        $grouped = $entries->groupBy(function ($e) {
            return $e->batch->billing_date->format('Y-m-d');
        });

        $currentBillTotal = 0;
        $lineItems = [];

        foreach ($entries as $entry) {
            $kg = (float) $entry->bird_weight;
            $rate = (float) $entry->billing_rate;
            $total = round($kg * $rate, 2);

            $lineItems[] = [
                'item_name'    => 'Day-Load (' . $entry->batch->billing_date->format('d M') . ')',
                'vendor_name'  => $entry->vendor->firm_name ?? '-',
                'quantity_kg'  => $kg,
                'rate_per_kg'  => $rate,
                'total_amount' => $total,
            ];
            $currentBillTotal += $total;
        }

        $previousBalance = (float) $dealer->pending_amount;
        $grandTotal = $currentBillTotal + $previousBalance;

        $bill = WeeklyBill::create([
            'dealer_id'            => 1,
            'period_start'         => '2026-06-02',
            'period_end'           => '2026-06-08',
            'invoice_no'           => 'INV-DL-0001',
            'amount'               => $currentBillTotal,
            'gst_percentage'       => 0,
            'gst_amount'           => 0,
            'net_amount'           => $grandTotal,
            'status'               => 'Pending',
            'payment_mode'         => 'Pending',
            'previous_outstanding' => $previousBalance,
            'payments_during_week' => 0,
        ]);

        foreach ($lineItems as $item) {
            WeeklyBillItem::create(array_merge($item, [
                'weekly_bill_id' => $bill->id,
                'tax_amount'     => 0,
            ]));
        }

        $dealer->update(['pending_amount' => $grandTotal]);

        $this->command->info("Bill #{$bill->id} created. Amount: Rs {$currentBillTotal}, Grand: Rs {$grandTotal}");
    }
}
