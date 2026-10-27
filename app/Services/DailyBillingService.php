<?php

namespace App\Services;

use App\Models\DailyBill;
use App\Models\Dealer;
use App\Models\Customer;
use App\Models\DayLoadEntry;
use App\Models\DealerPayment;
use App\Services\Tax\GSTCalculator;
use App\Services\InvoiceNumberService;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

class DailyBillingService
{
    public function __construct(
        private InvoiceNumberService $invoiceService,
        private StockService $stockService
    ) {}

    /**
     * Calculate daily billing totals for a dealer on a given date.
     */
    public function calculateDailyTotals(int $dealerId, string $dateFrom, ?string $dateTo = null, float $discountAmount = 0.0): array
    {
        $dateTo = $dateTo ?: $dateFrom;
        $dealer = Dealer::findOrFail($dealerId);

        // 1. Existing daily bills in range if re-generating
        $existingBillIds = DailyBill::where('dealer_id', $dealerId)
            ->where(function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('date', [$dateFrom, $dateTo])
                  ->orWhereBetween('date_from', [$dateFrom, $dateTo])
                  ->orWhereBetween('date_to', [$dateFrom, $dateTo]);
            })
            ->pluck('id')
            ->toArray();

        $purchasesQuery = DayLoadEntry::where('dealer_id', $dealerId)
            ->where('status', '!=', 'Cancelled')
            ->where(function ($q) use ($existingBillIds) {
                $q->whereNull('daily_bill_id');
                if (!empty($existingBillIds)) {
                    $q->orWhereIn('daily_bill_id', $existingBillIds);
                }
            })
            ->whereHas('batch', fn($q) => $q->whereBetween('billing_date', [$dateFrom, $dateTo]));

        $totalPurchases = (float) $purchasesQuery->sum('amount');
        $purchasesList = $purchasesQuery->with(['batch', 'vendor'])->get();

        // 2. Dealer payments between dateFrom and dateTo with exact payment dates
        $paymentsList = \App\Models\DealerPayment::where('dealer_id', $dealerId)
            ->whereBetween('date', [$dateFrom, $dateTo])
            ->orderBy('date', 'asc')
            ->get();
        $totalPayments = (float) $paymentsList->sum('amount');

        // 3. Outstanding balance before dateFrom
        $priorPurchases = (float) DayLoadEntry::where('dealer_id', $dealerId)
            ->where('status', '!=', 'Cancelled')
            ->whereHas('batch', fn($q) => $q->where('billing_date', '<', $dateFrom))
            ->sum('amount');

        $priorPayments = (float) \App\Models\DealerPayment::where('dealer_id', $dealerId)
            ->where('date', '<', $dateFrom)
            ->sum('amount');

        $priorEmis = (float) \App\Models\Emi::where('emi_type', 'Dealer')
            ->where('entity_id', $dealerId)
            ->where('status', '!=', 'Paid')
            ->where('due_date', '<', $dateFrom)
            ->get()
            ->sum('remaining_amount');
            
        $priorDiscounts = (float) \App\Models\DailyBill::where('dealer_id', $dealerId)
            ->where('date_to', '<', $dateFrom)
            ->sum('discount_amount');
            
        $priorWeeklyDiscounts = (float) \App\Models\WeeklyBill::where('dealer_id', $dealerId)
            ->where('period_end', '<', $dateFrom)
            ->sum('discount_amount');

        $previousOutstanding = max(0, (float) $dealer->pending_amount + $priorPurchases + $priorEmis - $priorPayments - $priorDiscounts - $priorWeeklyDiscounts);

        // Net Invoice Amount = Previous Outstanding + Purchases - Discount (Gross Billed Amount)
        $netInvoiceAmount = $previousOutstanding + $totalPurchases - $discountAmount;
        $balanceDue = max(0, $netInvoiceAmount - $totalPayments);

        // 4. Build daily breakdown for each day in dateFrom..dateTo
        $dailyBreakdown = [];
        $start = \Carbon\Carbon::parse($dateFrom);
        $end = \Carbon\Carbon::parse($dateTo);

        for ($dt = $start->copy(); $dt->lte($end); $dt->addDay()) {
            $dStr = $dt->format('Y-m-d');
            $dayPurchases = $purchasesList->filter(fn($p) => $p->batch && $p->batch->billing_date->format('Y-m-d') === $dStr);
            $dayPayments  = $paymentsList->filter(fn($pay) => \Carbon\Carbon::parse($pay->date)->format('Y-m-d') === $dStr);

            $dailyBreakdown[] = [
                'date'          => $dStr,
                'day_name'      => $dt->format('l'),
                'has_entries'   => $dayPurchases->isNotEmpty(),
                'purchases'     => $dayPurchases,
                'total_amount'  => $dayPurchases->sum('dealer_income'),
                'payments'      => $dayPayments,
                'paid_amount'   => $dayPayments->sum('amount'),
            ];
        }

        return [
            'dealer'               => $dealer,
            'date_from'            => $dateFrom,
            'date_to'              => $dateTo,
            'previous_outstanding' => $previousOutstanding,
            'total_purchases'      => $totalPurchases,
            'total_payments'       => $totalPayments,
            'net_invoice_amount'   => $netInvoiceAmount,
            'balance_due'          => $balanceDue,
            'purchases'            => $purchasesList,
            'payments_list'        => $paymentsList,
            'daily_breakdown'      => $dailyBreakdown,
            'existing_bills_count' => count($existingBillIds),
        ];
    }

    /**
     * Generate daily bill for a dealer on a given date range (matching weekly billing flow).
     */
    public function generateDailyBill(array $data, ?int $createdBy = null): DailyBill
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $dealerId = $data['dealer_id'];
            $dateFrom = $data['date_from'] ?? $data['date'];
            $dateTo   = $data['date_to']   ?? $data['date'];
            $discountAmount = (float)($data['discount_amount'] ?? 0.0);

            $invoiceNo = null;
            if (!empty($data['replace_existing'])) {
                $oldBills = DailyBill::where('dealer_id', $dealerId)
                    ->where(function ($q) use ($dateFrom, $dateTo) {
                        $q->whereBetween('date', [$dateFrom, $dateTo])
                          ->orWhereBetween('date_from', [$dateFrom, $dateTo])
                          ->orWhereBetween('date_to', [$dateFrom, $dateTo]);
                    })->get();

                foreach ($oldBills as $oldBill) {
                    if (!$invoiceNo && $oldBill->invoice_no) {
                        $invoiceNo = $oldBill->invoice_no;
                    }

                    DayLoadEntry::where('daily_bill_id', $oldBill->id)->update(['daily_bill_id' => null]);
                    
                    $oldBill->items()->delete();
                    $oldBill->delete();
                }
            }

            $totals = $this->calculateDailyTotals($dealerId, $dateFrom, $dateTo, $discountAmount);
            $grossPurchases = (float) $totals['total_purchases'];
            $discountPct = $grossPurchases > 0 ? round(($discountAmount / $grossPurchases) * 100, 2) : 0.00;
            $netAmount = max(0, round($grossPurchases - $discountAmount, 2));

            $bill = DailyBill::create([
                'dealer_id'             => $dealerId,
                'date'                  => $dateTo,
                'date_from'             => $dateFrom,
                'date_to'               => $dateTo,
                'invoice_no'            => $invoiceNo ?: $this->invoiceService->generateUnique('INV-D', 'daily_bills'),
                'amount'                => $grossPurchases,
                'gst_percentage'        => 0.00,
                'gst_amount'            => 0.00,
                'net_amount'            => $netAmount,
                'discount_percentage'   => $discountPct,
                'discount_amount'       => $discountAmount,
                'status'                => $totals['balance_due'] > 0 ? 'Pending' : 'Paid',
                'payment_mode'          => 'Credit',
                'previous_outstanding'  => $totals['previous_outstanding'],
                'payments_during_day'   => $totals['total_payments'],
            ]);

            // Link daily purchases to daily bill and copy items
            foreach ($totals['purchases'] as $purchase) {
                $bill->items()->create([
                    'item_name'    => 'Day-Load Batch #' . $purchase->batch_id . ' (' . ($purchase->vendor->firm_name ?? '-') . ')',
                    'quantity_kg'  => $purchase->bird_weight,
                    'rate_per_kg'  => $purchase->customer_rate,
                    'tax_amount'   => 0.00,
                    'total_amount' => $purchase->amount,
                ]);
                $purchase->update(['daily_bill_id' => $bill->id]);
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

            if ($discountAmount > 0) {
                $dealer = Dealer::findOrFail($dealerId);
                $dealer->decrement('pending_amount', $discountAmount);
            }

            return $bill;
        });
    }

    /**
     * Delete a daily bill.
     */
    public function deleteDailyBill(DailyBill $bill): void
    {
        DB::transaction(function () use ($bill) {
            // Unlink daily purchases
            DayLoadEntry::where('daily_bill_id', $bill->id)->update(['daily_bill_id' => null]);
            
            // Clean up auto-generated customer payment if any
            if ($bill->customer_id && $bill->invoice_no) {
                $autoPayment = \App\Models\CustomerPayment::where('customer_id', $bill->customer_id)
                    ->where('notes', 'like', '%' . $bill->invoice_no . '%')
                    ->first();
                if ($autoPayment) {
                    $paymentDate = $autoPayment->date;
                    $autoPayment->delete();
                    app(\App\Services\CashBankLedgerService::class)->recalculateForDate(\Carbon\Carbon::parse($paymentDate));
                }
            }

            // Reverse customer balance if bill was Pending
            if ($bill->customer_id && ($bill->status === 'Pending' || $bill->payment_mode === 'Pay later(EMI)')) {
                Customer::find($bill->customer_id)?->decrement('balance', $bill->net_amount);
            }

            $bill->items()->delete();
            $bill->delete();
        });
    }

    /**
     * Legacy/Customer Create Method (Backward Compatibility).
     */
    public function create(array $data, ?int $createdBy = null): DailyBill
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $itemsData = $data['items'];
            $gstPercent = $data['gst_percentage'] ?? 0;
            $paymentMode = $data['payment_mode'] ?? 'Cash';
            $status = $data['status'] ?? 'Pending';

            $subtotal = 0;
            foreach ($itemsData as $item) {
                $subtotal += $item['qty'] * $item['rate'];
            }

            $gstData = GSTCalculator::calculate($subtotal, $gstPercent);

            $bill = DailyBill::create([
                'customer_id'    => $data['customer_id'] ?? null,
                'dealer_id'      => $data['dealer_id'] ?? null,
                'date'           => $data['date'],
                'invoice_no'     => $this->invoiceService->generateUnique('INV-D', 'daily_bills'),
                'amount'         => $subtotal,
                'gst_percentage' => $gstPercent,
                'gst_amount'     => $gstData['total_gst'],
                'net_amount'     => $gstData['net_amount'],
                'status'         => $status,
                'payment_mode'   => $paymentMode,
            ]);

            // Standalone Customer Daily Invoice:
            // Purely for billing records & invoice printing.
            // Does NOT affect Dealer, Cash/Bank Ledger, Customer Payments, or Inventory Stock.
            foreach ($itemsData as $item) {
                $base = $item['qty'] * $item['rate'];
                $tax = round($base * $gstPercent / 100, 2);

                $bill->items()->create([
                    'item_name'    => $item['name'],
                    'quantity_kg'  => $item['qty'],
                    'rate_per_kg'  => $item['rate'],
                    'tax_amount'   => $tax,
                    'total_amount' => $base + $tax,
                    'unit'         => $item['unit'] ?? 'kg',
                ]);
            }

            // If status is Pending / Credit, update customer outstanding balance
            if (($status === 'Pending' || $paymentMode === 'Pay later(EMI)') && !empty($data['customer_id'])) {
                Customer::find($data['customer_id'])?->increment('balance', $gstData['net_amount']);
            }

            // Auto-record in Customer Collections (/payments/customers) if Paid
            if ($status === 'Paid' && !empty($data['customer_id'])) {
                $customer = Customer::find($data['customer_id']);
                $codAmount = in_array($paymentMode, ['Cash', 'COD']) ? (float)$gstData['net_amount'] : 0.00;
                $bankAmount = !in_array($paymentMode, ['Cash', 'COD']) ? (float)$gstData['net_amount'] : 0.00;

                \App\Models\CustomerPayment::create([
                    'customer_id'          => $data['customer_id'],
                    'date'                 => $data['date'],
                    'cod_amount'           => $codAmount,
                    'bank_transfer_amount' => $bankAmount,
                    'amount'               => $gstData['net_amount'],
                    'payment_mode'         => $paymentMode,
                    'payment_type'         => 'Full',
                    'notes'                => 'Auto-generated for Daily Invoice ' . $bill->invoice_no,
                    'balance_after'        => $customer ? $customer->balance : 0,
                ]);

                app(\App\Services\CashBankLedgerService::class)->recalculateForDate(\Carbon\Carbon::parse($data['date']));
            }

            return $bill;
        });
    }

    /**
     * Legacy/Customer Update Method.
     */
    public function update(DailyBill $bill, array $data, ?int $updatedBy = null): DailyBill
    {
        return DB::transaction(function () use ($bill, $data, $updatedBy) {
            $itemsData = $data['items'];
            $gstPercent = $data['gst_percentage'] ?? 0;
            $paymentMode = $data['payment_mode'] ?? 'Cash';
            $status = $data['status'] ?? 'Pending';

            $subtotal = 0;
            foreach ($itemsData as $item) {
                $subtotal += $item['qty'] * $item['rate'];
            }

            $gstData = GSTCalculator::calculate($subtotal, $gstPercent);

            // 1. REVERSE OLD STATE
            if ($bill->status === 'Pending' || $bill->payment_mode === 'Credit') {
                if ($bill->customer_id) {
                    Customer::find($bill->customer_id)?->decrement('balance', $bill->net_amount);
                } elseif ($bill->dealer_id) {
                    Dealer::find($bill->dealer_id)?->decrement('pending_amount', $bill->net_amount);
                }
            } else {
                if ($bill->customer_id) {
                    $existingPayment = \App\Models\CustomerPayment::where('customer_id', $bill->customer_id)
                        ->where('notes', 'like', '%Auto-generated for Invoice ' . $bill->invoice_no . '%')
                        ->first();
                    if ($existingPayment) {
                        $existingPayment->delete();
                    }
                }
            }

            $bill->update([
                'customer_id'    => $data['customer_id'] ?? $bill->customer_id,
                'dealer_id'      => $data['dealer_id'] ?? $bill->dealer_id,
                'date'           => $data['date'],
                'amount'         => $subtotal,
                'gst_percentage' => $gstPercent,
                'gst_amount'     => $gstData['total_gst'],
                'net_amount'     => $gstData['net_amount'],
                'status'         => $status,
                'payment_mode'   => $paymentMode,
            ]);

            // 2. APPLY NEW STATE
            if ($paymentMode === 'Credit' || $status === 'Pending') {
                if (!empty($data['customer_id'])) {
                    Customer::find($data['customer_id'])?->increment('balance', $gstData['net_amount']);
                } elseif (!empty($data['dealer_id'])) {
                    Dealer::find($data['dealer_id'])?->increment('pending_amount', $gstData['net_amount']);
                }
            } elseif (in_array($status, ['Paid', 'COD', 'Bank'])) {
                if (!empty($data['customer_id'])) {
                    $codAmount = ($paymentMode === 'Cash' || $status === 'COD') ? $gstData['net_amount'] : 0;
                    $bankAmount = (in_array($paymentMode, ['UPI', 'NEFT', 'Cheque(Bank Transfer)']) || $status === 'Bank') ? $gstData['net_amount'] : 0;
                    $customer = Customer::find($data['customer_id']);
                    
                    \App\Models\CustomerPayment::create([
                        'customer_id' => $data['customer_id'],
                        'date' => $data['date'],
                        'cod_amount' => $codAmount,
                        'bank_transfer_amount' => $bankAmount,
                        'amount' => $gstData['net_amount'],
                        'payment_mode' => $paymentMode,
                        'payment_type' => 'Invoice Payment',
                        'notes' => 'Auto-generated for Invoice ' . $bill->invoice_no,
                        'balance_after' => $customer ? $customer->balance : 0,
                    ]);
                    
                    app(\App\Services\CashBankLedgerService::class)->recalculateForDate(\Carbon\Carbon::parse($data['date']));
                }
            }

            $this->stockService->revertMovement(DailyBill::class, $bill->id);

            $bill->items()->delete();
            foreach ($itemsData as $item) {
                $base = $item['qty'] * $item['rate'];
                $tax = round($base * $gstPercent / 100, 2);

                $billItem = $bill->items()->create([
                    'item_name'    => $item['name'],
                    'quantity_kg'  => $item['qty'],
                    'rate_per_kg'  => $item['rate'],
                    'tax_amount'   => $tax,
                    'total_amount' => $base + $tax,
                    'unit'         => $item['unit'] ?? 'kg',
                ]);

                if (empty($data['dealer_id'])) {
                    $this->stockService->recordOut([
                        'item_name'      => $billItem->item_name,
                        'quantity'       => $billItem->quantity_kg,
                        'rate'           => $billItem->rate_per_kg,
                        'reference_type' => DailyBill::class,
                        'reference_id'   => $bill->id,
                        'date'           => $bill->date,
                        'created_by'     => $updatedBy ?? auth()->id() ?? 1,
                    ]);
                }
            }

            return $bill->fresh();
        });
    }

    /**
     * Legacy Delete.
     */
    public function delete(DailyBill $bill): bool
    {
        $this->deleteDailyBill($bill);
        return true;
    }
}
