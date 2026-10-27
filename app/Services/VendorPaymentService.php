<?php

namespace App\Services;

use App\Models\DayLoadEntry;
use App\Models\VendorPayment;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class VendorPaymentService
{
    public function paginated(
        ?string $search,
        ?int    $vendorId,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $paymentMode,
        int     $perPage = 15
    ): LengthAwarePaginator {
        return VendorPayment::with('vendor')
            ->when($search, function ($q) use ($search) {
                $q->where(function ($nested) use ($search) {
                    $nested->whereHas('vendor', fn($v) => $v->where('firm_name', 'like', "%{$search}%"))
                           ->orWhere('reference_number', 'like', "%{$search}%");
                });
            })
            ->when($vendorId, fn($q) => $q->where('vendor_id', $vendorId))
            ->when($dateFrom, fn($q) => $q->whereDate('date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('date', '<=', $dateTo))
            ->when($paymentMode, fn($q) => $q->where('payment_mode', $paymentMode))
            ->latest('date')
            ->paginate($perPage);
    }

    public function paginatedDailyGrouped(
        ?string $search,
        ?int    $vendorId,
        ?string $dateFrom,
        ?string $dateTo,
        ?string $paymentMode,
        int     $perPage = 15
    ): LengthAwarePaginator {
        $baseQuery = VendorPayment::query()
            ->when($search, function ($q) use ($search) {
                $q->where(function ($nested) use ($search) {
                    $nested->whereHas('vendor', fn($v) => $v->where('firm_name', 'like', "%{$search}%"))
                           ->orWhere('reference_number', 'like', "%{$search}%");
                });
            })
            ->when($vendorId, fn($q) => $q->where('vendor_id', $vendorId))
            ->when($dateFrom, fn($q) => $q->whereDate('date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('date', '<=', $dateTo))
            ->when($paymentMode, fn($q) => $q->where('payment_mode', $paymentMode));

        $groupedQuery = (clone $baseQuery)
            ->selectRaw('
                vendor_id,
                date,
                SUM(amount) as amount,
                SUM(cash_amount) as cash_amount,
                SUM(bank_amount) as bank_amount,
                MAX(id) as latest_id,
                COUNT(*) as entry_count
            ')
            ->groupBy('vendor_id', 'date')
            ->orderByDesc('date')
            ->orderByDesc('latest_id');

        $paginated = $groupedQuery->paginate($perPage);

        $latestIds = $paginated->pluck('latest_id')->filter()->all();
        $latestPayments = VendorPayment::with('vendor')->whereIn('id', $latestIds)->get()->keyBy('id');

        return $paginated->through(function ($group) use ($latestPayments) {
            $latest = $latestPayments->get($group->latest_id);
            if (!$latest) {
                return $group;
            }

            $hasCash = (float) $group->cash_amount > 0;
            $hasBank = (float) $group->bank_amount > 0;

            if ($hasCash && $hasBank) {
                $mode = 'Split';
            } elseif ($hasBank) {
                $mode = $latest->bank_transfer_type ?: ($latest->payment_mode === 'Advance Adjustment' ? 'Advance Adjustment' : 'Bank Transfer');
            } elseif ($hasCash) {
                $mode = 'Cash';
            } else {
                $mode = $latest->payment_mode ?? 'UPI';
            }

            $item = clone $latest;
            $item->amount = (float) $group->amount;
            $item->cash_amount = (float) $group->cash_amount;
            $item->bank_amount = (float) $group->bank_amount;
            $item->payment_mode = $mode;
            $item->entry_count = (int) $group->entry_count;
            $item->date = \Carbon\Carbon::parse($group->date);

            return $item;
        });
    }

    public function record(array $data): VendorPayment
    {
        return DB::transaction(function () use ($data) {
            $vendor = Vendor::findOrFail($data['vendor_id']);

            $cashAmount = isset($data['cash_amount']) ? (float) $data['cash_amount'] : 0.00;
            $bankAmount = isset($data['bank_amount']) ? (float) $data['bank_amount'] : 0.00;

            $totalAmount = round($cashAmount + $bankAmount, 2);
            $remainingTotal = $totalAmount;

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
                    $recordCash = $totalAmount > 0 ? round($alloc * ($cashAmount / $totalAmount), 2) : 0.00;
                    $recordBank = round($alloc - $recordCash, 2);
                    $createdPayments[] = VendorPayment::create([
                        'vendor_id'          => $vendor->id,
                        'day_load_entry_id'  => null,
                        'date'               => $data['date'],
                        'amount'             => $alloc,
                        'cash_amount'        => $recordCash,
                        'bank_amount'        => $recordBank,
                        'total_amount'       => $alloc,
                        'pending_balance_after' => $vendor->outstanding_balance,
                        'payment_mode'       => $data['payment_mode'],
                        'bank_transfer_type' => $data['bank_transfer_type'] ?? null,
                        'notes'              => "EMI Payment: " . ($emi->loan_name ?: 'EMI'),
                        'reference_number'   => $data['notes'] ?? null, // UI's "Remarks / Reference"
                    ]);

                    $remainingTotal = round($remainingTotal - $alloc, 2);
                    if ($remainingTotal <= 0) break;
                }
            }

            $dayLoadService = app(DayLoadPaymentService::class);

            // 1. Allocate to day-load entries (FIFO)
            if ($remainingTotal > 0) {
                $entriesQuery = DayLoadEntry::where('vendor_id', $vendor->id)
                    ->where('status', '!=', 'Cancelled');

                if (!empty($data['selected_entry_ids'])) {
                    $entriesQuery->whereIn('id', $data['selected_entry_ids']);
                }

                $entries = $entriesQuery->with('batch')
                    ->get()
                    ->sortBy(function($e) {
                        return $e->batch ? $e->batch->billing_date->timestamp : $e->created_at->timestamp;
                    });

                foreach ($entries as $entry) {
                    $balance = round((float) $entry->vendor_cost - (float) $entry->vendor_paid, 2);
                    if ($balance <= 0) continue;

                    $alloc = min($remainingTotal, $balance);
                    $entry->increment('vendor_paid', $alloc);
                    $dayLoadService->refreshVendorPaymentStatus($entry);
                    if ($entry->batch) {
                        $dayLoadService->refreshBatchFinancials($entry->batch);
                    }

                    $recordCash = $totalAmount > 0 ? round($alloc * ($cashAmount / $totalAmount), 2) : 0.00;
                    $recordBank = round($alloc - $recordCash, 2);

                    $payment = VendorPayment::create([
                        'vendor_id'        => $vendor->id,
                        'day_load_entry_id'=> $entry->id,
                        'date'             => $data['date'],
                        'amount'           => $alloc,
                        'cash_amount'      => $recordCash,
                        'bank_amount'      => $recordBank,
                        'total_amount'       => $alloc,
                        'pending_balance_after' => $vendor->outstanding_balance,
                        'payment_mode'     => $data['payment_mode'],
                        'bank_transfer_type' => $data['bank_transfer_type'] ?? null,
                        'reference_number' => $data['reference_number'] ?? null,
                        'notes'            => ($data['notes'] ?? '') ?: 'Auto-allocated to entry #' . $entry->id,
                    ]);

                    $createdPayments[] = $payment;
                    $remainingTotal = round($remainingTotal - $alloc, 2);

                    if ($remainingTotal <= 0) {
                        break;
                    }
                }
            }

            // 2. Any remaining amount — record against vendor balance directly
            if ($remainingTotal > 0) {
                $payment = VendorPayment::create([
                    'vendor_id'        => $vendor->id,
                    'day_load_entry_id'=> null,
                    'date'             => $data['date'],
                    'amount'           => $remainingTotal,
                    'cash_amount'      => $totalAmount > 0 ? round($remainingTotal * ($cashAmount / $totalAmount), 2) : 0.00,
                    'bank_amount'      => $totalAmount > 0 ? round($remainingTotal * ($bankAmount / $totalAmount), 2) : 0.00,
                    'payment_mode'     => $data['payment_mode'],
                    'bank_transfer_type' => $data['bank_transfer_type'] ?? null,
                    'notes'            => ($data['notes'] ?? '') ?: 'Unallocated General Vendor Payment',
                ]);

                $createdPayments[] = $payment;
            }

            if (empty($createdPayments)) {
                $payment = VendorPayment::create([
                    'vendor_id'        => $vendor->id,
                    'date'             => $data['date'],
                    'amount'           => $totalAmount,
                    'cash_amount'      => $cashAmount,
                    'bank_amount'      => $bankAmount,
                    'payment_mode'     => $data['payment_mode'],
                    'bank_transfer_type' => $data['bank_transfer_type'] ?? null,
                    'notes'            => $data['notes'] ?? 'Vendor payment',
                ]);
                $createdPayments[] = $payment;
            }

            // Update pending_balance_after on all created payments
            $finalOutstanding = $vendor->fresh()->outstanding_balance;
            foreach ($createdPayments as $p) {
                $p->updateQuietly([
                    'pending_balance_after' => $finalOutstanding
                ]);
            }

            // Recalculate cash/bank ledger for the payment date
            $paymentDate = $createdPayments[0]->date;
            app(CashBankLedgerService::class)->recalculateForDate(\Carbon\Carbon::parse($paymentDate));

            return $createdPayments[0];
        });
    }

    public function allForExport(): \Illuminate\Database\Eloquent\Collection
    {
        return VendorPayment::with('vendor')->orderByDesc('date')->get();
    }

    public function allForExportDailyGrouped(): \Illuminate\Support\Collection
    {
        $grouped = VendorPayment::with('vendor')
            ->selectRaw('
                vendor_id,
                date,
                SUM(amount) as amount,
                SUM(cash_amount) as cash_amount,
                SUM(bank_amount) as bank_amount,
                MAX(id) as latest_id,
                COUNT(*) as entry_count
            ')
            ->groupBy('vendor_id', 'date')
            ->orderByDesc('date')
            ->get();

        $latestIds = $grouped->pluck('latest_id')->filter()->all();
        $latestPayments = VendorPayment::with('vendor')->whereIn('id', $latestIds)->get()->keyBy('id');

        return $grouped->map(function ($group) use ($latestPayments) {
            $latest = $latestPayments->get($group->latest_id);
            $hasCash = (float) $group->cash_amount > 0;
            $hasBank = (float) $group->bank_amount > 0;

            if ($hasCash && $hasBank) {
                $mode = 'Split';
            } elseif ($hasBank) {
                $mode = $latest?->bank_transfer_type ?: ($latest?->payment_mode === 'Advance Adjustment' ? 'Advance Adjustment' : 'Bank Transfer');
            } elseif ($hasCash) {
                $mode = 'Cash';
            } else {
                $mode = $latest?->payment_mode ?? 'UPI';
            }

            return (object) [
                'vendor'       => $latest?->vendor,
                'date'         => \Carbon\Carbon::parse($group->date),
                'amount'       => (float) $group->amount,
                'payment_mode' => $mode,
                'notes'        => $group->entry_count > 1 ? "Consolidated ({$group->entry_count} entries)" : ($latest?->notes ?? ''),
            ];
        });
    }
}
