<?php

namespace App\Services;

use App\Models\DealerPayment;
use App\Models\Dealer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DealerPaymentService
{
    public function paginated(?string $query, int $perPage = 15): LengthAwarePaginator
    {
        return DealerPayment::with('dealer')
            ->search($query)
            ->latest('date')
            ->paginate($perPage);
    }

    public function record(array $data): DealerPayment
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $dealer = Dealer::findOrFail($data['dealer_id']);
            $data['tenant_id'] = $dealer->tenant_id;
            
            $cashAmount = isset($data['cash_amount']) ? (float) $data['cash_amount'] : 0.00;
            $bankAmount = isset($data['bank_amount']) ? (float) $data['bank_amount'] : 0.00;
            $discountAmount = 0.00;
            
            // Fallback for old tests / seeds
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

            $totalToAllocate = round($amount + $discountAmount, 2);
            $remainingTotal = $totalToAllocate;

            $paymentGroupId = (string) \Illuminate\Support\Str::uuid();
            $createdPayments = [];
            $discountAssigned = false;

            // 0. Allocate to selected EMIs first
            if (!empty($data['selected_emi_ids'])) {
                $emis = \App\Models\Emi::whereIn('id', $data['selected_emi_ids'])->orderBy('due_date')->get();
                foreach ($emis as $emi) {
                    $due = max(0, $emi->amount - $emi->paid_amount);
                    if ($due <= 0) continue;

                    $alloc = min($remainingTotal, $due);
                    $emi->paid_amount += $alloc;
                    
                    if ($emi->paid_amount >= $emi->amount) {
                        $emi->status = 'Paid';
                    } else if ($emi->paid_amount > 0) {
                        $emi->status = 'Partial';
                    }
                    $emi->save();

                    // Create a payment record for the EMI
                    $recordCash = $amount > 0 ? round($alloc * ($cashAmount / $amount), 2) : 0.00;
                    $recordBank = round($alloc - $recordCash, 2);
                    
                    $paymentData = array_merge($data, [
                        'amount'            => $alloc,
                        'cash_amount'       => $recordCash,
                        'bank_amount'       => $recordBank,
                        'discount_amount'   => 0.00,
                        'payment_group_id'  => $paymentGroupId,
                        'day_load_entry_id' => null,
                        'invoice_id'        => null,
                        'notes'             => "EMI Payment: " . ($emi->loan_name ?: 'EMI'),
                    ]);
                    $createdPayments[] = DealerPayment::create($paymentData);

                    $remainingTotal = round($remainingTotal - $alloc, 2);
                    if ($remainingTotal <= 0) break;
                }
            }
            // 1. Allocate to base pending_amount (Only if selected_entry_ids is empty!)
            if ($remainingTotal > 0 && empty($data['selected_entry_ids'])) {
                $pendingAmount = (float) $dealer->pending_amount;
                if ($pendingAmount > 0) {
                    $deduct = min($remainingTotal, $pendingAmount);
                    // WE DO NOT DECREMENT PENDING AMOUNT HERE ANYMORE!
                    // $dealer->decrement('pending_amount', $deduct);
                    
                    $thisDiscount = 0.00;
                    if (!$discountAssigned && $discountAmount > 0) {
                        $thisDiscount = $discountAmount;
                        $discountAssigned = true;
                    }
                    
                    $thisAllocAmount = max(0.00, round($deduct - $thisDiscount, 2));
                    
                    $recordCash = $amount > 0 ? round($thisAllocAmount * ($cashAmount / $amount), 2) : 0.00;
                    $recordBank = round($thisAllocAmount - $recordCash, 2);
                    
                    $paymentData = array_merge($data, [
                        'amount'            => $thisAllocAmount,
                        'cash_amount'       => $recordCash,
                        'bank_amount'       => $recordBank,
                        'discount_amount'   => $thisDiscount,
                        'payment_group_id'  => $paymentGroupId,
                        'day_load_entry_id' => null,
                        'invoice_id'        => null,
                        'notes'             => ($data['notes'] ?? '') ?: 'Allocated to base pending balance',
                    ]);
                    $createdPayments[] = DealerPayment::create($paymentData);
                    
                    $remainingTotal = round($remainingTotal - $deduct, 2);
                }
            }
            
            // 2. Allocate to active day load entries (FIFO)
            if ($remainingTotal > 0) {
                $dayLoadPaymentService = app(DayLoadPaymentService::class);
                
                $entriesQuery = \App\Models\DayLoadEntry::where('dealer_id', $dealer->id)
                    ->where('status', '!=', 'Cancelled');

                // If specific entries are selected
                if (!empty($data['selected_entry_ids'])) {
                    $entriesQuery->whereIn('id', $data['selected_entry_ids']);
                }

                $entries = $entriesQuery->with(['dealerPayments', 'batch'])
                    ->get()
                    ->sortBy(function($entry) {
                        return $entry->batch ? $entry->batch->billing_date->timestamp : $entry->created_at->timestamp;
                    });
                
                foreach ($entries as $entry) {
                    $collected = (float) $entry->dealer_collected;
                    $due = round((float) $entry->amount - $collected, 2);
                    if ($due <= 0) {
                        continue;
                    }
                    
                    $alloc = min($remainingTotal, $due);
                    $entry->increment('dealer_collected', $alloc);
                    $dayLoadPaymentService->refreshDealerPaymentStatus($entry);
                    $dayLoadPaymentService->refreshBatchFinancials($entry->batch);
                    $dayLoadPaymentService->refreshInvoicePayment($entry->batch?->invoice);
                    
                    $thisDiscount = 0.00;
                    if (!$discountAssigned && $discountAmount > 0) {
                        $thisDiscount = $discountAmount;
                        $discountAssigned = true;
                    }
                    
                    $thisAllocAmount = max(0.00, round($alloc - $thisDiscount, 2));
                    
                    $recordCash = $amount > 0 ? round($thisAllocAmount * ($cashAmount / $amount), 2) : 0.00;
                    $recordBank = round($thisAllocAmount - $recordCash, 2);
                    
                    $paymentData = array_merge($data, [
                        'amount'            => $thisAllocAmount,
                        'cash_amount'       => $recordCash,
                        'bank_amount'       => $recordBank,
                        'discount_amount'   => $thisDiscount,
                        'payment_group_id'  => $paymentGroupId,
                        'day_load_entry_id' => $entry->id,
                        'invoice_id'        => $entry->batch?->invoice_id,
                        'notes'             => ($data['notes'] ?? '') ?: 'Auto-allocated to entry #' . $entry->id,
                    ]);
                    $createdPayments[] = DealerPayment::create($paymentData);
                    
                    $remainingTotal = round($remainingTotal - $alloc, 2);
                    if ($remainingTotal <= 0) {
                        break;
                    }
                }
            }
            
            // 3. Any excess amount is treated as advance
            if ($remainingTotal > 0) {
                $thisDiscount = 0.00;
                if (!$discountAssigned && $discountAmount > 0) {
                    $thisDiscount = $discountAmount;
                    $discountAssigned = true;
                }
                
                $thisAllocAmount = max(0.00, round($remainingTotal - $thisDiscount, 2));
                
                $recordCash = $amount > 0 ? round($thisAllocAmount * ($cashAmount / $amount), 2) : 0.00;
                $recordBank = round($thisAllocAmount - $recordCash, 2);
                
                $paymentData = array_merge($data, [
                    'amount'            => $thisAllocAmount,
                    'cash_amount'       => $recordCash,
                    'bank_amount'       => $recordBank,
                    'discount_amount'   => $thisDiscount,
                    'payment_group_id'  => $paymentGroupId,
                    'day_load_entry_id' => null,
                    'invoice_id'        => null,
                    'notes'             => ($data['notes'] ?? '') ?: 'Unallocated advance',
                ]);
                $createdPayments[] = DealerPayment::create($paymentData);
            }
            
            // If no payment was created, fallback
            if (empty($createdPayments)) {
                $paymentData = array_merge($data, [
                    'amount'            => $amount,
                    'cash_amount'       => $cashAmount,
                    'bank_amount'       => $bankAmount,
                    'discount_amount'   => $discountAmount,
                    'day_load_entry_id' => null,
                    'invoice_id'        => null,
                ]);
                $createdPayments[] = DealerPayment::create($paymentData);
            }
            
            // Update pending_balance_after on all created payments
            $finalOutstanding = $dealer->fresh()->displayed_outstanding;
            foreach ($createdPayments as $p) {
                $p->updateQuietly([
                    'pending_balance_after' => $finalOutstanding
                ]);
            }
            
            // BUG 6 FIX: Recalculate cash/bank ledger for ALL unique payment dates
            $uniqueDates = collect($createdPayments)
                ->pluck('date')
                ->map(fn($d) => \Carbon\Carbon::parse($d)->format('Y-m-d'))
                ->unique();
 
            foreach ($uniqueDates as $dateStr) {
                app(CashBankLedgerService::class)->recalculateForDate(\Carbon\Carbon::parse($dateStr));
            }
            
            return $createdPayments[0];
        });
    }

    public function allForExport(): \Illuminate\Database\Eloquent\Collection
    {
        return DealerPayment::with('dealer')->orderByDesc('date')->get();
    }

    public function deletePayment(DealerPayment $payment): bool
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($payment) {
            $payments = $payment->payment_group_id
                ? DealerPayment::where('payment_group_id', $payment->payment_group_id)->get()
                : collect([$payment]);

            $uniqueDates = [];

            foreach ($payments as $p) {
                if ($p->day_load_entry_id) {
                    $entry = \App\Models\DayLoadEntry::find($p->day_load_entry_id);
                    if ($entry) {
                        $entry->dealer_collected = max(0, (float)$entry->dealer_collected - (float)$p->amount);
                        if ($entry->dealer_collected <= 0) {
                            $entry->dealer_payment_status = 'Pending';
                        } elseif ($entry->dealer_collected < $entry->amount) {
                            $entry->dealer_payment_status = 'Partial';
                        }
                        $entry->save();
                    }
                }

                $uniqueDates[] = $p->date ? $p->date->format('Y-m-d') : null;
                $p->delete();
            }

            foreach (array_unique(array_filter($uniqueDates)) as $dStr) {
                app(CashBankLedgerService::class)->recalculateForDate(\Carbon\Carbon::parse($dStr));
            }

            return true;
        });
    }
}
