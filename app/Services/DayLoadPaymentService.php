<?php

namespace App\Services;

use App\Models\DayLoadBatch;
use App\Models\DayLoadEntry;
use App\Models\DayLoadInvoice;
use App\Models\DealerPayment;
use App\Models\PaymentAdjustmentLog;
use App\Models\VendorPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DayLoadPaymentService
{
    public function __construct(
        private CashBankLedgerService $cashBankLedgerService,
    ) {}

    public function recordDealerPayment(DayLoadEntry $entry, array $data): DealerPayment
    {
        return DB::transaction(function () use ($entry, $data) {
            // Ensure the batch relationship is fresh (invoice_id may have been set after entry creation)
            $entry->load('batch');

            $cashAmount = (float) ($data['cash_amount'] ?? $data['amount']);
            $bankAmount = (float) ($data['bank_amount'] ?? 0);

            // Legacy `amount` must always equal cash + bank to prevent drift
            $legacyAmount = round($cashAmount + $bankAmount, 2);

            // Update entry-level collected
            $entry->increment('dealer_collected', $legacyAmount);
            $this->refreshDealerPaymentStatus($entry);

            $payment = DealerPayment::create([
                'dealer_id'         => $entry->dealer_id,
                'day_load_entry_id' => $entry->id,
                'invoice_id'        => $entry->batch?->invoice_id,
                'date'              => $data['date'],
                'amount'            => $legacyAmount,
                'payment_mode'      => $data['payment_mode'],
                'cash_amount'       => $cashAmount,
                'bank_amount'       => $bankAmount,
                'bank_transfer_type' => $data['bank_transfer_type'] ?? null,
                'reference_number'  => $data['reference_number'] ?? null,
                'notes'             => $data['notes'] ?? null,
            ]);

            $payment->updateQuietly([
                'pending_balance_after' => $entry->dealer->fresh()->displayed_outstanding
            ]);

            // Update invoice-level aggregated payment
            $this->refreshInvoicePayment($entry->batch?->invoice);

            // Update batch-level financials
            $this->refreshBatchFinancials($entry->batch);

            // Recalculate cash/bank ledger for the payment date
            $this->cashBankLedgerService->recalculateForDate(\Carbon\Carbon::parse($payment->date));

            return $payment;
        });
    }

    public function recordLumpSumDealerPayment(array $data): array
    {
        $dealerId    = (int) $data['dealer_id'];
        $date        = $data['date'];
        $allocations = $data['allocations'];
        $cashAmount  = (float) ($data['cash_amount'] ?? 0);
        $bankAmount  = (float) ($data['bank_amount'] ?? 0);
        $totalLump   = round($cashAmount + $bankAmount, 2);

        if ($totalLump <= 0) {
            throw new \InvalidArgumentException('Total payment amount must be greater than zero.');
        }

        $payments = DB::transaction(function () use (
            $dealerId, $date, $allocations, $cashAmount, $bankAmount, $totalLump, $data
        ) {
            // --- 1. Parse allocations --------------------------------------------------
            $allocSum = 0;
            $active   = [];
            foreach ($allocations as $entryId => $amount) {
                $amt = round((float) $amount, 2);
                if ($amt <= 0) {
                    continue;
                }
                $active[(int) $entryId] = $amt;
                $allocSum = round($allocSum + $amt, 2);
            }

            if (empty($active)) {
                throw new \InvalidArgumentException('At least one entry allocation must be greater than zero.');
            }

            if ($allocSum > $totalLump) {
                throw new \InvalidArgumentException(
                    'Total allocation (Rs ' . number_format($allocSum, 2) . ') exceeds lump-sum amount (Rs ' . number_format($totalLump, 2) . ').'
                );
            }

            // --- 2. Lock and load entries ----------------------------------------------
            $entries = DayLoadEntry::with(['vendor', 'batch.invoice'])
                ->whereIn('id', array_keys($active))
                ->where('dealer_id', $dealerId)
                ->where('status', '!=', 'Cancelled')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // --- 3. Validate no overpayment per entry ----------------------------------
            $violations = [];
            foreach ($active as $entryId => $amount) {
                $entry = $entries->get($entryId);
                if (!$entry) {
                    $violations[] = "Entry #{$entryId}: not found or does not belong to this dealer.";
                    continue;
                }
                $balanceDue = round($entry->dealer_income - (float) $entry->dealer_collected, 2);
                if ($amount > $balanceDue) {
                    $over = number_format($amount - $balanceDue, 2);
                    $violations[] = "Entry #{$entryId} ({$entry->vendor->firm_name}): Rs "
                        . number_format($amount, 2) . ' allocated exceeds balance due of Rs '
                        . number_format($balanceDue, 2) . ' by Rs ' . $over . '.';
                }
            }

            if (!empty($violations)) {
                throw new \InvalidArgumentException(
                    "Overpayment blocked:\n- " . implode("\n- ", $violations)
                );
            }

            // --- 4. Build item list (entries + optional remainder) --------------------
            $unallocated = round($totalLump - $allocSum, 2);

            $items = [];
            foreach ($active as $entryId => $amount) {
                $items[] = [
                    'entry_id' => $entryId,
                    'amount'   => round($amount, 2),
                    'is_remainder' => false,
                ];
            }
            if ($unallocated > 0) {
                $items[] = [
                    'entry_id' => null,
                    'amount'   => $unallocated,
                    'is_remainder' => true,
                ];
            }

            // --- 5. Largest-remainder cash/bank split across all items ----------------
            // Work in integer paise to guarantee exact sums.
            $cashPaise   = round($cashAmount * 100);
            $totalPaise  = round($totalLump * 100);

            $itemPaise = [];
            foreach ($items as $i => $item) {
                $amtPaise = round($item['amount'] * 100);
                // floor-cash in paise: floor(amtPaise * cashPaise / totalPaise)
                $floorCash   = intdiv($amtPaise * $cashPaise, $totalPaise);
                $idealFloat  = $amtPaise * $cashPaise / $totalPaise;
                $remainder   = $idealFloat - $floorCash; // fractional part, 0 <= r < 1
                $itemPaise[$i] = [
                    'floor_cash' => $floorCash,
                    'remainder'  => $remainder,
                ];
            }

            $distributedPaise = array_sum(array_column($itemPaise, 'floor_cash'));
            $remainingPaise   = $cashPaise - $distributedPaise;

            if ($remainingPaise > 0) {
                // Give 1 paisa each to rows with largest fractional remainders
                $order = array_keys($itemPaise);
                usort($order, function ($a, $b) use ($itemPaise) {
                    return $itemPaise[$b]['remainder'] <=> $itemPaise[$a]['remainder'];
                });
                for ($i = 0; $i < $remainingPaise; $i++) {
                    $itemPaise[$order[$i]]['floor_cash']++;
                }
            }

            // --- 6. Create payment records -------------------------------------------
            $paymentGroupId = (string) Str::uuid();
            $payments = [];

            foreach ($items as $i => $item) {
                $cashPaiseForItem = $itemPaise[$i]['floor_cash'];
                $cashForItem      = round($cashPaiseForItem / 100, 2);
                $bankForItem      = round($item['amount'] - $cashForItem, 2);

                if ($item['is_remainder']) {
                    $payment = DealerPayment::create([
                        'dealer_id'          => $dealerId,
                        'day_load_entry_id'  => null,
                        'invoice_id'         => null,
                        'payment_group_id'   => $paymentGroupId,
                        'date'               => $date,
                        'amount'             => $item['amount'],
                        'payment_mode'       => $data['payment_mode'] ?? 'Cash',
                        'cash_amount'        => $cashForItem,
                        'bank_amount'        => $bankForItem,
                        'bank_transfer_type' => $data['bank_transfer_type'] ?? null,
                        'reference_number'   => $data['reference_number'] ?? null,
                        'notes'              => 'Unallocated advance from lump-sum payment',
                    ]);
                } else {
                    $entry = $entries->get($item['entry_id']);

                    $payment = DealerPayment::create([
                        'dealer_id'          => $dealerId,
                        'day_load_entry_id'  => $item['entry_id'],
                        'invoice_id'         => $entry->batch?->invoice_id,
                        'payment_group_id'   => $paymentGroupId,
                        'date'               => $date,
                        'amount'             => $item['amount'],
                        'payment_mode'       => $data['payment_mode'] ?? 'Cash',
                        'cash_amount'        => $cashForItem,
                        'bank_amount'        => $bankForItem,
                        'bank_transfer_type' => $data['bank_transfer_type'] ?? null,
                        'reference_number'   => $data['reference_number'] ?? null,
                        'notes'              => ($data['notes'] ?? '') ?: 'Lump-sum allocation to entry #' . $item['entry_id'],
                    ]);

                    $entry->increment('dealer_collected', $item['amount']);
                    $this->refreshDealerPaymentStatus($entry);
                }

                $payments[] = $payment;
            }

            // --- 7. Refresh financials (deduplicated) --------------------------------
            $seenBatches  = collect();
            $seenInvoices = collect();
            foreach ($active as $entryId => $amount) {
                $entry = $entries->get($entryId);
                if (!$entry || !$entry->batch) {
                    continue;
                }
                if (!$seenBatches->has($entry->batch->id)) {
                    $seenBatches->put($entry->batch->id, $entry->batch);
                    $this->refreshBatchFinancials($entry->batch);
                }
                $invoice = $entry->batch->invoice;
                if ($invoice && !$seenInvoices->has($invoice->id)) {
                    $seenInvoices->put($invoice->id, $invoice);
                    $this->refreshInvoicePayment($invoice);
                }
            }

            $finalOutstanding = \App\Models\Dealer::find($dealerId)->displayed_outstanding;
            foreach ($payments as $p) {
                $p->updateQuietly(['pending_balance_after' => $finalOutstanding]);
            }

            return $payments;
        });

        $this->cashBankLedgerService->recalculateForDate(\Carbon\Carbon::parse($date));

        return $payments;
    }

    public function updateDealerPayment(DealerPayment $payment, array $data, string $reason): DealerPayment
    {
        return DB::transaction(function () use ($payment, $data, $reason) {
            $oldValues = $payment->toArray();

            $cashAmount = (float) ($data['cash_amount'] ?? $payment->cash_amount);
            $bankAmount = (float) ($data['bank_amount'] ?? $payment->bank_amount);
            $legacyAmount = round($cashAmount + $bankAmount, 2);

            $payment->update([
                'date'              => $data['date'] ?? $payment->date,
                'amount'            => $legacyAmount,
                'payment_mode'      => $data['payment_mode'] ?? $payment->payment_mode,
                'cash_amount'       => $cashAmount,
                'bank_amount'       => $bankAmount,
                'bank_transfer_type' => $data['bank_transfer_type'] ?? $payment->bank_transfer_type,
                'reference_number'  => $data['reference_number'] ?? $payment->reference_number,
                'notes'             => $data['notes'] ?? $payment->notes,
            ]);

            PaymentAdjustmentLog::create([
                'payment_id'   => $payment->id,
                'action_type'  => 'Edit',
                'old_values'   => $oldValues,
                'new_values'   => $payment->fresh()->toArray(),
                'reason'       => $reason,
                'adjusted_by'  => auth()->id(),
            ]);

            // Recalculate entry-level collected from all payments for this entry
            $entry = $payment->dayLoadEntry;
            if ($entry) {
                $totalCollected = (float) $entry->dealerPayments()->sum('amount');
                $entry->updateQuietly(['dealer_collected' => $totalCollected]);
                $this->refreshDealerPaymentStatus($entry);
                $this->refreshBatchFinancials($entry->batch);
                $this->refreshInvoicePayment($entry->batch?->invoice);
            }

            $payment->updateQuietly([
                'pending_balance_after' => $payment->dealer->fresh()->displayed_outstanding
            ]);

            $this->cashBankLedgerService->recalculateForDate(\Carbon\Carbon::parse($payment->date));

            return $payment->fresh();
        });
    }

    public function recordVendorPayment(DayLoadEntry $entry, array $data): VendorPayment
    {
        return DB::transaction(function () use ($entry, $data) {
            $cashAmount = isset($data['cash_amount']) ? (float) $data['cash_amount'] : 0.00;
            $bankAmount = isset($data['bank_amount']) ? (float) $data['bank_amount'] : 0.00;
            
            // Backward compatibility fallback
            if (!isset($data['cash_amount']) && !isset($data['bank_amount'])) {
                $amount = (float) ($data['amount'] ?? 0);
                if (($data['payment_mode'] ?? 'Cash') === 'Cash') {
                    $cashAmount = $amount;
                    $bankAmount = 0.00;
                } else {
                    $cashAmount = 0.00;
                    $bankAmount = $amount;
                }
            }

            $amount = round($cashAmount + $bankAmount, 2);
            $entry->increment('vendor_paid', $amount);

            $this->refreshVendorPaymentStatus($entry);
            $this->refreshBatchFinancials($entry->batch);

            $payment = VendorPayment::create([
                'vendor_id'        => $entry->vendor_id,
                'day_load_entry_id'=> $entry->id,
                'date'             => $data['date'],
                'amount'           => $amount,
                'payment_mode'     => $data['payment_mode'],
                'cash_amount'      => $cashAmount,
                'bank_amount'      => $bankAmount,
                'bank_transfer_type'=> $data['bank_transfer_type'] ?? null,
                'reference_number' => $data['reference_number'] ?? null,
                'notes'            => $data['notes'] ?? null,
            ]);

            $payment->updateQuietly([
                'pending_balance_after' => $entry->vendor->fresh()->outstanding_balance
            ]);

            $this->cashBankLedgerService->recalculateForDate(\Carbon\Carbon::parse($payment->date));

            return $payment;
        });
    }

    public function refreshInvoicePayment(?DayLoadInvoice $invoice): void
    {
        if (!$invoice) {
            return;
        }

        $amountPaid = (float) $invoice->dealerPayments()->sum('amount');
        $totalAmount = (float) $invoice->total_amount;

        if ($amountPaid <= 0) {
            $paymentStatus = 'Pending';
        } elseif ($amountPaid >= $totalAmount && $totalAmount > 0) {
            $paymentStatus = 'Paid';
        } elseif ($amountPaid > 0 && $amountPaid < $totalAmount) {
            $paymentStatus = 'Partial';
        } else {
            // Edge case: total_amount is 0 but there's a payment
            $paymentStatus = 'Paid';
        }

        $invoice->updateQuietly([
            'amount_paid'    => $amountPaid,
            'payment_status' => $paymentStatus,
        ]);
    }

    public function refreshDealerPaymentStatus(DayLoadEntry $entry): void
    {
        $income = $entry->dealer_income;
        $collected = (float) $entry->dealer_collected;

        if ($collected <= 0) {
            $status = 'Pending';
        } elseif ($collected >= $income) {
            $status = 'Paid';
        } elseif ($collected > 0 && $collected < $income) {
            $status = 'Partial';
        } else {
            $status = 'Overpaid';
        }

        if ($collected > $income) {
            $status = 'Overpaid';
        }

        $entry->updateQuietly(['dealer_payment_status' => $status]);
    }

    public function refreshVendorPaymentStatus(DayLoadEntry $entry): void
    {
        $cost = $entry->vendor_cost;
        $paid = (float) $entry->vendor_paid;

        if ($paid <= 0) {
            $status = 'Pending';
        } elseif ($paid >= $cost) {
            $status = 'Paid';
        } elseif ($paid > 0 && $paid < $cost) {
            $status = 'Partial';
        } else {
            $status = 'Overpaid';
        }

        if ($paid > $cost) {
            $status = 'Overpaid';
        }

        $entry->updateQuietly(['vendor_payment_status' => $status]);
    }

    public function refreshBatchFinancials(DayLoadBatch $batch): void
    {
        $entries = $batch->entries()->where('status', '!=', 'Cancelled')->get();

        $batch->update([
            'total_dealer_income'    => $entries->sum(fn($e) => $e->dealer_income),
            'total_vendor_cost'      => $entries->sum(fn($e) => $e->vendor_cost),
            'total_dealer_collected' => $entries->sum(fn($e) => (float) $e->dealer_collected),
            'total_vendor_paid'      => $entries->sum(fn($e) => (float) $e->vendor_paid),
        ]);
    }
}
