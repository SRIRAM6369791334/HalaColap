<?php

namespace App\Services;

use App\Models\Dealer;
use App\Models\WeeklyBill;
use App\Models\DealerPurchase;
use App\Models\DealerPayment;
use App\Models\DayLoadEntry;
use App\Services\Tax\GSTCalculator;
use App\Services\InvoiceNumberService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

class WeeklyBillingService
{
    public function __construct(
        private InvoiceNumberService $invoiceService,
        private StockService $stockService
    ) {}

    /**
     * Create a daily/individual Dealer Purchase.
     */
    public function createPurchase(array $data, ?int $createdBy = null): DealerPurchase
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $itemsData = $data['items'];
            $subtotal = 0;
            foreach ($itemsData as $item) {
                $subtotal += $item['qty'] * $item['rate'];
            }

            $gstData = GSTCalculator::calculate($subtotal, 18);

            $purchase = DealerPurchase::create([
                'dealer_id'      => $data['dealer_id'],
                'date'           => $data['date'],
                'invoice_no'     => $this->invoiceService->generateUnique('DPUR', 'dealer_purchases'),
                'amount'         => $subtotal,
                'gst_percentage' => 18,
                'gst_amount'     => $gstData['total_gst'],
                'net_amount'     => $gstData['net_amount'],
            ]);

            // Increment dealer's pending_amount
            $dealer = Dealer::find($data['dealer_id']);
            if ($dealer) {
                $dealer->increment('pending_amount', $gstData['net_amount']);
            }

            foreach ($itemsData as $item) {
                $base = $item['qty'] * $item['rate'];
                $tax  = round($base * 18 / 100, 2);

                $purchase->items()->create([
                    'item_name'    => $item['name'],
                    'quantity_kg'  => $item['qty'],
                    'rate_per_kg'  => $item['rate'],
                    'tax_amount'   => $tax,
                    'total_amount' => $base + $tax,
                ]);

                // Reduce stock immediately on purchase
                $this->stockService->recordOut([
                    'item_name'      => $item['name'],
                    'quantity'       => $item['qty'],
                    'rate'           => $item['rate'],
                    'reference_type' => DealerPurchase::class,
                    'reference_id'   => $purchase->id,
                    'date'           => $purchase->date,
                    'created_by'     => $createdBy ?? auth()->id() ?? 1,
                ]);
            }

            return $purchase;
        });
    }

    /**
     * Calculate weekly billing totals for a dealer.
     */
    public function calculateWeeklyTotals(int $dealerId, string $startDate, string $endDate, float $discountAmount = 0.0): array
    {
        $dealer = Dealer::findOrFail($dealerId);

        // 1. Sum of all daily purchases from day_load_entries (including existing weekly bill if re-generating)
        $existingBill = WeeklyBill::where('dealer_id', $dealerId)
            ->whereDate('period_start', $startDate)
            ->whereDate('period_end', $endDate)
            ->first();

        $purchasesQuery = DayLoadEntry::where('dealer_id', $dealerId)
            ->where('status', '!=', 'Cancelled')
            ->where(function ($q) use ($existingBill) {
                $q->whereNull('weekly_bill_id');
                if ($existingBill) {
                    $q->orWhere('weekly_bill_id', $existingBill->id);
                }
            })
            ->whereHas('batch', fn($q) => $q
                ->whereBetween('billing_date', [$startDate, $endDate])
            );

        $totalPurchases = (float) $purchasesQuery->sum('amount');
        $purchasesList = $purchasesQuery->with(['batch', 'vendor'])->get();

        // 2. Sum of all dealer payments made during this week
        $paymentsQuery = DealerPayment::where('dealer_id', $dealerId)
            ->whereBetween('date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        $totalPayments = (float) $paymentsQuery->sum('amount');

        // 3. Outstanding balance before this week
        $priorPurchases = (float) DayLoadEntry::where('dealer_id', $dealerId)
            ->where('status', '!=', 'Cancelled')
            ->whereHas('batch', fn($q) => $q->where('billing_date', '<', $startDate))
            ->sum('amount');

        $priorPayments = (float) DealerPayment::where('dealer_id', $dealerId)
            ->where('date', '<', $startDate)
            ->sum('amount');

        $priorEmis = (float) \App\Models\Emi::where('emi_type', 'Dealer')
            ->where('entity_id', $dealerId)
            ->where('status', '!=', 'Paid')
            ->where('due_date', '<', $startDate)
            ->get()
            ->sum('remaining_amount');

        $priorDailyDiscounts = (float) \App\Models\DailyBill::where('dealer_id', $dealerId)
            ->where('date_to', '<', $startDate)
            ->sum('discount_amount');
            
        $priorWeeklyDiscounts = (float) \App\Models\WeeklyBill::where('dealer_id', $dealerId)
            ->where('period_end', '<', $startDate)
            ->sum('discount_amount');

        $previousOutstanding = max(0, (float) $dealer->pending_amount + $priorPurchases + $priorEmis - $priorPayments - $priorDailyDiscounts - $priorWeeklyDiscounts);

        // Net Invoice Amount = Previous Outstanding + Purchases - Discount (Gross Billed Amount)
        $netInvoiceAmount = $previousOutstanding + $totalPurchases - $discountAmount;
        $balanceDue = max(0, $netInvoiceAmount - $totalPayments);

        return [
            'dealer' => $dealer,
            'previous_outstanding' => $previousOutstanding,
            'total_purchases' => $totalPurchases,
            'total_payments' => $totalPayments,
            'net_invoice_amount' => $netInvoiceAmount,
            'balance_due' => $balanceDue,
            'purchases' => $purchasesList,
        ];
    }

    /**
     * Generate weekly bill with Monday/Friday split payment schedules.
     */
    public function generateWeeklyBill(array $data, ?int $createdBy = null): WeeklyBill
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $dealerId = $data['dealer_id'];
            $startDate = $data['period_start'];
            $endDate = $data['period_end'];
            $discountAmount = (float)($data['discount_amount'] ?? 0.0);

            $invoiceNo = null;
            if (!empty($data['replace_existing'])) {
                $oldBill = WeeklyBill::where('dealer_id', $dealerId)
                    ->whereDate('period_start', $startDate)
                    ->whereDate('period_end', $endDate)
                    ->first();
                if ($oldBill) {
                    $invoiceNo = $oldBill->invoice_no;
                    
                    // Unlink all day load entries connected to it
                    DayLoadEntry::where('weekly_bill_id', $oldBill->id)
                        ->update(['weekly_bill_id' => null]);
                    
                    $oldBill->items()->delete();
                    $oldBill->delete();
                }
            }

            $totals = $this->calculateWeeklyTotals($dealerId, $startDate, $endDate, $discountAmount);
            $grossPurchases = (float) $totals['total_purchases'];
            $discountPct = $grossPurchases > 0 ? round(($discountAmount / $grossPurchases) * 100, 2) : 0.00;
            $netAmount = max(0, round($grossPurchases - $discountAmount, 2));

            $bill = WeeklyBill::create([
                'dealer_id'             => $dealerId,
                'period_start'          => $startDate,
                'period_end'            => $endDate,
                'invoice_no'            => $invoiceNo ?: $this->invoiceService->generateUnique('INV-W', 'weekly_bills'),
                'amount'                => $grossPurchases,
                'gst_percentage'        => 0.00,
                'gst_amount'            => 0.00,
                'net_amount'            => $netAmount,
                'discount_percentage'   => $discountPct,
                'discount_amount'       => $discountAmount,
                'status'                => $totals['balance_due'] > 0 ? 'Pending' : 'Paid',
                'payment_mode'          => 'Credit',
                'previous_outstanding'  => $totals['previous_outstanding'],
                'payments_during_week'  => $totals['total_payments'],
            ]);

            // Link daily purchases to weekly bill and copy items
            foreach ($totals['purchases'] as $purchase) {
                $bill->items()->create([
                    'item_name'    => 'Day-Load Batch #' . $purchase->batch_id . ' (' . ($purchase->vendor->firm_name ?? '-') . ')',
                    'quantity_kg'  => $purchase->bird_weight,
                    'rate_per_kg'  => $purchase->customer_rate,
                    'tax_amount'   => 0.00,
                    'total_amount' => $purchase->amount,
                ]);
                $purchase->update(['weekly_bill_id' => $bill->id]);
            }

            if ($totals['purchases']->isEmpty() && $netAmount > 0) {
                $bill->items()->create([
                    'item_name'    => 'Outstanding Balance Carried Forward',
                    'quantity_kg'  => 1,
                    'rate_per_kg'  => $baseAmount,
                    'tax_amount'   => $gstAmount,
                    'total_amount' => $netAmount,
                ]);
            }

            // Decrement dealer's running pending_amount by the discount amount
            if ($discountAmount > 0) {
                $dealer = Dealer::findOrFail($dealerId);
                $dealer->decrement('pending_amount', $discountAmount);
            }

            return $bill;
        });
    }

    /**
     * Create a single Weekly Bill with itemised lines (Deprecated / Legacy Manual Mode).
     */
    public function create(array $data, ?int $createdBy = null): WeeklyBill
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $itemsData   = $data['items'];
            $paymentMode = $data['payment_mode'];
            $status      = $data['status'];

            $subtotal = 0;
            foreach ($itemsData as $item) {
                $subtotal += $item['qty'] * $item['rate'];
            }

            $gstData = GSTCalculator::calculate($subtotal, 18);

            $bill = WeeklyBill::create([
                'dealer_id'    => $data['dealer_id'],
                'period_start'   => $data['period_start'],
                'period_end'     => $data['period_end'],
                'invoice_no'     => $this->invoiceService->generateUnique('INV-W', 'weekly_bills'),
                'amount'         => $subtotal,
                'gst_percentage' => 18,
                'gst_amount'     => $gstData['total_gst'],
                'net_amount'     => $gstData['net_amount'],
                'status'         => $status,
                'payment_mode'   => $paymentMode,
            ]);

            if ($paymentMode === 'Credit' || $status === 'Pending') {
                $dealer = Dealer::find($data['dealer_id']);
                if ($dealer) {
                    $dealer->increment('pending_amount', $gstData['net_amount']);
                }
            }

            foreach ($itemsData as $item) {
                $base = $item['qty'] * $item['rate'];
                $tax  = round($base * 18 / 100, 2);

                $billItem = $bill->items()->create([
                    'item_name'    => $item['name'],
                    'quantity_kg'  => $item['qty'],
                    'rate_per_kg'  => $item['rate'],
                    'tax_amount'   => $tax,
                    'total_amount' => $base + $tax,
                ]);

                $this->stockService->recordOut([
                    'item_name'      => $billItem->item_name,
                    'quantity'       => $billItem->quantity_kg,
                    'rate'           => $billItem->rate_per_kg,
                    'reference_type' => WeeklyBill::class,
                    'reference_id'   => $bill->id,
                    'date'           => $bill->period_end,
                    'created_by'     => $createdBy ?? auth()->id() ?? 1,
                ]);
            }

            if ($paymentMode === 'Pay later(EMI)' && isset($data['emis']) && is_array($data['emis'])) {
                foreach ($data['emis'] as $emiData) {
                    \App\Models\Emi::create([
                        'emi_type'   => 'Dealer',
                        'entity_id'  => $bill->dealer_id,
                        'loan_name'  => 'Sales EMI - ' . $bill->invoice_no,
                        'bank_name'  => 'Weekly Bill',
                        'amount'     => $emiData['amount'],
                        'due_date'   => $emiData['due_date'],
                        'status'     => 'Upcoming',
                    ]);
                }
            }

            return $bill;
        });
    }

    /**
     * Create a Weekly Bill for each dealer in bulk (Legacy Flat-rate).
     */
    public function bulkCreate(array $dealerIds, array $data): int
    {
        return DB::transaction(function () use ($dealerIds, $data) {
            $paymentMode = $data['payment_mode'];
            $status      = $data['status'];
            $count       = 0;

            foreach ($dealerIds as $did) {
                $gstData = GSTCalculator::calculate($data['amount'], 18);

                $bill = WeeklyBill::create([
                    'invoice_no'     => $this->invoiceService->generateUnique('INV-W', 'weekly_bills'),
                    'dealer_id'      => $did,
                    'period_start'   => $data['period_start'],
                    'period_end'     => $data['period_end'],
                    'amount'         => $data['amount'],
                    'gst_percentage' => 18,
                    'gst_amount'     => $gstData['total_gst'],
                    'net_amount'     => $gstData['net_amount'],
                    'status'         => $status,
                    'payment_mode'   => $paymentMode,
                ]);

                if ($paymentMode === 'Credit' || $status === 'Pending') {
                    $dealer = Dealer::find($did);
                    if ($dealer) {
                        $dealer->increment('pending_amount', $gstData['net_amount']);
                    }
                }

                $bill->items()->create([
                    'item_name'    => 'Weekly MSR BROILERS Settlement',
                    'quantity_kg'  => 1,
                    'rate_per_kg'  => $data['amount'],
                    'tax_amount'   => $gstData['total_gst'],
                    'total_amount' => $gstData['net_amount'],
                ]);

                $count++;
            }

            return $count;
        });
    }

    /**
     * Delete a weekly bill.
     */
    public function deleteWeeklyBill(WeeklyBill $bill): void
    {
        DB::transaction(function () use ($bill) {
            $hasPurchases = DealerPurchase::where('weekly_bill_id', $bill->id)->exists();

            if ($hasPurchases) {
                // Unlink daily purchases
                DealerPurchase::where('weekly_bill_id', $bill->id)->update(['weekly_bill_id' => null]);
            } else {
                // If it was manually created, revert dealer balance
                if ($bill->payment_mode === 'Credit' || $bill->status === 'Pending') {
                    $dealer = Dealer::find($bill->dealer_id);
                    if ($dealer) {
                        $dealer->decrement('pending_amount', $bill->net_amount);
                        if ($dealer->pending_amount < 0) {
                            $dealer->update(['pending_amount' => 0]);
                        }
                    }
                }
            }

            // Delete weekly bill items
            $bill->items()->delete();

            // Delete the bill itself
            $bill->delete();
        });
    }
}
