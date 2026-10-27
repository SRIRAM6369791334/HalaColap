<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\DayLoadBatch;
use App\Models\DayLoadEntry;
use App\Models\Dealer;
use App\Models\EntryAdjustmentLog;
use App\Models\Vendor;
use App\Services\DayLoadBillingService;
use App\Services\DayLoadPaymentService;
use App\Services\ExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DayLoadBillingController extends Controller
{
    public function __construct(
        private DayLoadBillingService $dayLoadBillingService,
        private DayLoadPaymentService $dayLoadPaymentService,
        private ExportService $exporter,
    ) {}

    private function vendorExistsRule(): \Illuminate\Validation\Rules\Exists
    {
        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId();
        $rule = Rule::exists('vendors', 'id');
        return $tenantId ? $rule->where('tenant_id', $tenantId) : $rule;
    }

    private function dealerExistsRule(): \Illuminate\Validation\Rules\Exists
    {
        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId();
        $rule = Rule::exists('dealers', 'id');
        return $tenantId ? $rule->where('tenant_id', $tenantId) : $rule;
    }

    private function vendorAdvanceExistsRule(): \Illuminate\Validation\Rules\Exists
    {
        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId();
        $rule = Rule::exists('vendor_advances', 'id');
        return $tenantId ? $rule->where('tenant_id', $tenantId) : $rule;
    }

    public function index(Request $request): View
    {
        $date = $request->input('date', today()->format('Y-m-d'));
        $search = $request->input('search');

        $entriesQuery = DayLoadEntry::with(['batch', 'vendor', 'dealer', 'parentEntry', 'childEntries', 'adjustmentLogs'])
            ->whereHas('batch', fn ($query) => $query->whereDate('billing_date', $date))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($nested) use ($search) {
                    $nested
                        ->whereHas('vendor', fn ($q) => $q->where('firm_name', 'like', "%{$search}%"))
                        ->orWhereHas('dealer', fn ($q) => $q->where('firm_name', 'like', "%{$search}%"));
                });
            });

        $allDateEntries = (clone $entriesQuery)
            ->orderByRaw('CASE WHEN sort_order IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('sort_order', 'asc')
            ->latest('id')
            ->get();

        $transferLogs = EntryAdjustmentLog::whereIn('action_type', ['Transfer', 'Split'])->get();
        $transferIdsFromLogs = $transferLogs->pluck('entry_id')
            ->concat($transferLogs->pluck('resulting_entry_id'))
            ->filter()
            ->unique()
            ->toArray();

        $transferredEntries = $allDateEntries->filter(function ($e) use ($transferIdsFromLogs) {
            return $e->parent_entry_id !== null
                || $e->status === 'Adjusted'
                || in_array($e->id, $transferIdsFromLogs, true);
        });

        $normalEntries = $allDateEntries->reject(function ($e) use ($transferredEntries) {
            return $transferredEntries->contains('id', $e->id);
        });

        $entries = $normalEntries; // for backward compatibility

        $batches = DayLoadBatch::whereDate('billing_date', $date)->get();
        foreach ($batches as $b) {
            $this->dayLoadBillingService->refreshBatchTotals($b);
            $b->refresh();
        }
        $vendors = Vendor::orderBy('firm_name')->get();
        $dealers = \App\Services\DealerService::arrangeBySlotSequence(Dealer::with('routeRelation')->get());

        $allEntries = DayLoadEntry::with(['batch', 'vendor', 'dealer'])
            ->whereHas('batch', fn ($q) => $q->whereDate('billing_date', $date))
            ->where('status', '!=', 'Cancelled')
            ->get();

        $totalDealerIncome    = $allEntries->sum(fn($e) => $e->dealer_income);
        $totalVendorCost      = $allEntries->sum(fn($e) => $e->vendor_cost);
        $grossMargin          = round($totalDealerIncome - $totalVendorCost, 2);
        $totalDealerCollected = $allEntries->sum(fn($e) => (float) $e->dealer_collected);
        $totalVendorPaid      = $allEntries->sum(fn($e) => (float) $e->vendor_paid);
        $totalDealerDue       = round($totalDealerIncome - $totalDealerCollected, 2);
        $totalVendorDue       = round($totalVendorCost - $totalVendorPaid, 2);
        $collectionPct        = $totalDealerIncome > 0 ? round(($totalDealerCollected / $totalDealerIncome) * 100, 1) : 0;

        $lsEntriesByDealer = $allEntries->groupBy('dealer_id')->map(fn($entries) => $entries->map(fn($e) => [
            'id'               => $e->id,
            'vendor'           => $e->vendor->firm_name ?? '-',
            'dealer_income'    => (float) $e->dealer_income,
            'dealer_collected' => (float) $e->dealer_collected,
            'due'              => max(0, round($e->dealer_income - (float) $e->dealer_collected, 2)),
        ]));

        $activeAdvances = \App\Models\VendorAdvance::where('status', '!=', 'Fully Adjusted')
            ->with('vendor')
            ->get();
        $activeAdvancesByVendor = $activeAdvances->groupBy('vendor_id')->map(function($advs) {
            return [
                'total_remaining' => (float) $advs->sum(fn($a) => $a->remaining_amount),
                'advances' => $advs->map(fn($a) => [
                    'id' => $a->id,
                    'date' => $a->date->format('d M Y'),
                    'total' => (float) $a->total_amount,
                    'remaining' => (float) $a->remaining_amount,
                ])->values(),
            ];
        });

        $existingRatesByVendor = $allEntries->groupBy('vendor_id')->map(function ($entries) {
            $first = $entries->first();
            return [
                'paper_rate'   => (float) $first->paper_rate,
                'billing_rate' => $first->billing_rate !== null ? (float) $first->billing_rate : null,
            ];
        });

        return view('billing.day-load.index', compact(
            'entries', 'normalEntries', 'transferredEntries', 'batches', 'vendors', 'dealers', 'date', 'search',
            'totalDealerIncome', 'totalVendorCost', 'grossMargin',
            'totalDealerCollected', 'totalVendorPaid',
            'totalDealerDue', 'totalVendorDue', 'collectionPct',
            'lsEntriesByDealer', 'allEntries',
            'activeAdvances', 'activeAdvancesByVendor', 'existingRatesByVendor'
        ));
    }

    public function getRatesForVendorAndDate(Request $request): JsonResponse
    {
        $vendorId = $request->input('vendor_id');
        $date = $request->input('date');

        if (!$vendorId || !$date) {
            return response()->json(['paper_rate' => null, 'billing_rate' => null, 'dealer_rates' => (object)[], 'found' => false]);
        }

        $entries = DayLoadEntry::where('vendor_id', $vendorId)
            ->where('status', '!=', 'Cancelled')
            ->whereHas('batch', fn ($q) => $q->whereDate('billing_date', $date))
            ->get();

        if ($entries->isNotEmpty()) {
            $first = $entries->first();
            $dealerRates = [];
            foreach ($entries as $e) {
                if ($e->customer_rate > 0) {
                    $dealerRates[(string) $e->dealer_id] = (float) $e->customer_rate;
                }
            }

            return response()->json([
                'paper_rate'   => (float) $first->paper_rate,
                'billing_rate' => $first->billing_rate !== null ? (float) $first->billing_rate : null,
                'dealer_rates' => (object) $dealerRates,
                'found'        => true,
            ]);
        }

        return response()->json(['paper_rate' => null, 'billing_rate' => null, 'dealer_rates' => (object)[], 'found' => false]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'batch_id'             => 'nullable|exists:day_load_batches,id',
            'billing_date'         => 'required|date',
            'vendor_id'            => ['required', $this->vendorExistsRule()],
            'dealer_id'            => ['required', $this->dealerExistsRule()],
            'sort_order'           => 'nullable|integer|min:1',
            'paper_rate'           => 'required|numeric|min:0',
            'billing_rate'         => 'nullable|numeric|min:0',
            'customer_rate'        => 'required|numeric|min:0',
            'no_of_boxes'          => 'required|integer|min:1',
            'box_weight'           => 'required|numeric|gt:empty_weight',
            'empty_weight'         => 'required|numeric|min:0',
            'farm_weight'          => 'nullable|numeric|min:0',
            'remarks'              => 'nullable|string|max:255',
            'vendor_advance_id'    => ['nullable', $this->vendorAdvanceExistsRule()],
            'apply_advance_amount' => 'nullable|numeric|min:0',
        ]);

        $entry = $this->dayLoadBillingService->createEntry($validated);

        // Apply vendor advance if selected
        $advanceId = $request->input('vendor_advance_id');
        $applyAmount = (float) $request->input('apply_advance_amount', 0);

        if ($advanceId && $applyAmount > 0) {
            $effectiveWeight = (float) ($entry->effective_farm_weight ?? $entry->bird_weight);
            $vendorRate = (float) $entry->billing_rate > 0 ? (float) $entry->billing_rate : (float) $entry->paper_rate;
            $entryVendorCost = (float) $entry->vendor_cost > 0 ? (float) $entry->vendor_cost : round($effectiveWeight * $vendorRate, 2);
            $maxApplicable = max(0, $entryVendorCost > 0 ? $entryVendorCost : $applyAmount);
            $actualApply = min($applyAmount, $advance->remaining_amount, $maxApplicable);

            if ($actualApply > 0) {
                \Illuminate\Support\Facades\DB::transaction(function () use ($advance, $entry, $actualApply) {
                    $entry->increment('vendor_paid', $actualApply);

                    $newAdjusted = round((float) $advance->adjusted_amount + $actualApply, 2);
                    $advance->update([
                        'adjusted_amount' => $newAdjusted,
                        'status'          => $newAdjusted >= (float) $advance->total_amount ? 'Fully Adjusted' : 'Partially Adjusted',
                    ]);

                    $paymentDate = $entry->batch ? $entry->batch->billing_date : now();

                    \App\Models\VendorAdvanceAdjustment::create([
                        'vendor_advance_id' => $advance->id,
                        'day_load_entry_id' => $entry->id,
                        'amount'            => $actualApply,
                        'date'              => $paymentDate,
                        'notes'             => "Adjusted against Day-Load Entry #{$entry->id}",
                    ]);

                    \App\Models\VendorPayment::create([
                        'vendor_id'             => $entry->vendor_id,
                        'day_load_entry_id'     => $entry->id,
                        'date'                  => $paymentDate,
                        'amount'                => $actualApply,
                        'payment_mode'          => 'Advance Adjustment',
                        'cash_amount'           => 0.00,
                        'bank_amount'           => 0.00,
                        'notes'                 => "Adjusted from Advance #{$advance->id} (Day-Load Entry #{$entry->id})",
                        'pending_balance_after' => $entry->vendor->fresh()->outstanding_balance,
                    ]);

                    $this->dayLoadPaymentService->refreshVendorPaymentStatus($entry);
                    if ($entry->batch) {
                        $this->dayLoadPaymentService->refreshBatchFinancials($entry->batch);
                    }
                });
            }
        }

        return back()->with('success', 'Daily load entry recorded successfully.');
    }

    public function bulkStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'batch_id'             => 'nullable|exists:day_load_batches,id',
            'billing_date'         => 'required|date',
            'vendor_id'            => ['required', $this->vendorExistsRule()],
            'paper_rate'           => 'required|numeric|min:0',
            'billing_rate'         => 'nullable|numeric|min:0',
            'vendor_advance_id'    => ['nullable', $this->vendorAdvanceExistsRule()],
            'apply_advance_amount' => 'nullable|numeric|min:0',
            'dealers'              => 'required|array',
            'dealers.*.dealer_id'    => ['required', $this->dealerExistsRule()],
            'dealers.*.sort_order'   => 'nullable|integer|min:1',
            'dealers.*.customer_rate'=> 'nullable|numeric|min:0',
            'dealers.*.no_of_boxes'  => 'nullable|integer|min:0',
            'dealers.*.box_weight'   => 'nullable|numeric|min:0',
            'dealers.*.empty_weight' => 'nullable|numeric|min:0',
            'dealers.*.farm_weight'  => 'nullable|numeric|min:0',
            'dealers.*.remarks'      => 'nullable|string|max:255',
        ]);

        // Filter valid dealer rows: must have no_of_boxes > 0, box_weight > 0 and customer_rate >= 0
        $activeDealerEntries = collect($validated['dealers'])->filter(function ($d) {
            $boxes = isset($d['no_of_boxes']) && $d['no_of_boxes'] !== '' ? (int) $d['no_of_boxes'] : 0;
            $boxWeight = isset($d['box_weight']) && $d['box_weight'] !== '' ? (float) $d['box_weight'] : 0;
            $customerRate = isset($d['customer_rate']) && $d['customer_rate'] !== '' ? (float) $d['customer_rate'] : null;
            return $boxes > 0 && $boxWeight > 0 && $customerRate !== null;
        })->sortBy(function ($d) {
            return isset($d['sort_order']) && is_numeric($d['sort_order']) && (int) $d['sort_order'] > 0
                ? (int) $d['sort_order']
                : 999999;
        })->values()->all();

        if (empty($activeDealerEntries)) {
            return back()->with('error', 'No dealer entries filled. Please enter Boxes, Box Weight, and Customer Rate for at least one dealer.')->withInput();
        }

        $masterData = [
            'billing_date' => $validated['billing_date'],
            'vendor_id'    => $validated['vendor_id'],
            'paper_rate'   => $validated['paper_rate'],
            'billing_rate' => $validated['billing_rate'] ?? null,
        ];

        $advanceId = $request->input('vendor_advance_id') ? (int) $request->input('vendor_advance_id') : null;
        $applyAmount = (float) $request->input('apply_advance_amount', 0);
        $batchId = !empty($validated['batch_id']) ? (int) $validated['batch_id'] : null;

        try {
            $createdEntries = $this->dayLoadBillingService->createBulkEntries(
                $masterData,
                $activeDealerEntries,
                $advanceId,
                $applyAmount,
                $batchId
            );

            $count = count($createdEntries);
            $vendor = \App\Models\Vendor::find($validated['vendor_id']);
            $vendorName = $vendor->firm_name ?? 'Vendor';

            return back()->with('success', "Successfully recorded {$count} load " . ($count > 1 ? 'entries' : 'entry') . " for {$vendorName}.");
        } catch (\App\Exceptions\BatchLockedException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to save load entries: ' . $e->getMessage())->withInput();
        }
    }

    public function applyAdvance(Request $request, DayLoadEntry $entry): RedirectResponse
    {
        $validated = $request->validate([
            'vendor_advance_id' => ['required', $this->vendorAdvanceExistsRule()],
            'amount'            => 'required|numeric|min:0.01',
        ]);

        $advance = \App\Models\VendorAdvance::findOrFail($validated['vendor_advance_id']);
        $requestedAmount = (float) $validated['amount'];
        $remainingDue = max(0, (float) $entry->vendor_cost - (float) $entry->vendor_paid);
        $actualApply = min($requestedAmount, $advance->remaining_amount, $remainingDue);

        if ($actualApply <= 0) {
            return back()->with('error', 'Cannot apply advance. Either no remaining advance or entry is already fully paid.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($advance, $entry, $actualApply) {
            $entry->increment('vendor_paid', $actualApply);

            $newAdjusted = round((float) $advance->adjusted_amount + $actualApply, 2);
            $advance->update([
                'adjusted_amount' => $newAdjusted,
                'status'          => $newAdjusted >= (float) $advance->total_amount ? 'Fully Adjusted' : 'Partially Adjusted',
            ]);

            $paymentDate = $entry->batch ? $entry->batch->billing_date : now();

            \App\Models\VendorAdvanceAdjustment::create([
                'vendor_advance_id' => $advance->id,
                'day_load_entry_id' => $entry->id,
                'amount'            => $actualApply,
                'date'              => $paymentDate,
                'notes'             => "Manual advance adjustment against Entry #{$entry->id}",
            ]);

            \App\Models\VendorPayment::create([
                'vendor_id'             => $entry->vendor_id,
                'day_load_entry_id'     => $entry->id,
                'date'                  => $paymentDate,
                'amount'                => $actualApply,
                'payment_mode'          => 'Advance Adjustment',
                'cash_amount'           => 0.00,
                'bank_amount'           => 0.00,
                'notes'                 => "Adjusted from Advance #{$advance->id} (Day-Load Entry #{$entry->id})",
                'pending_balance_after' => $entry->vendor->fresh()->outstanding_balance,
            ]);

            $this->dayLoadPaymentService->refreshVendorPaymentStatus($entry);
            if ($entry->batch) {
                $this->dayLoadPaymentService->refreshBatchFinancials($entry->batch);
            }
        });

        return back()->with('success', "Advance of Rs " . number_format($actualApply, 2) . " applied to Entry #{$entry->id}.");
    }

    public function removeAdvanceAdjustment(\App\Models\VendorAdvanceAdjustment $adjustment): RedirectResponse
    {
        $advance = $adjustment->advance;
        $entry   = $adjustment->dayLoadEntry;
        $amount  = (float) $adjustment->amount;

        \Illuminate\Support\Facades\DB::transaction(function () use ($adjustment, $advance, $entry, $amount) {
            if ($entry) {
                $entry->decrement('vendor_paid', min($amount, (float) $entry->vendor_paid));
                $this->dayLoadPaymentService->refreshVendorPaymentStatus($entry);
                if ($entry->batch) {
                    $this->dayLoadPaymentService->refreshBatchFinancials($entry->batch);
                }

                \App\Models\VendorPayment::where('day_load_entry_id', $entry->id)
                    ->where('payment_mode', 'Advance Adjustment')
                    ->where(function ($q) use ($advance) {
                        if ($advance) {
                            $q->where('notes', 'like', "%Advance #{$advance->id}%");
                        }
                    })
                    ->limit(1)
                    ->delete();
            }

            if ($advance) {
                $newAdjusted = max(0, round((float) $advance->adjusted_amount - $amount, 2));
                $advance->update([
                    'adjusted_amount' => $newAdjusted,
                    'status'          => $newAdjusted <= 0 ? 'Pending' : 'Partially Adjusted',
                ]);
            }

            $adjustment->delete();
        });

        return back()->with('success', 'Advance adjustment reverted.');
    }

    public function transfer(Request $request, DayLoadEntry $entry): RedirectResponse
    {
        $validated = $request->validate([
            'transfer_weight'      => 'required|numeric|min:0.01',
            'target_dealer_id'     => ['required', $this->dealerExistsRule()],
            'target_vendor_id'     => ['required', $this->vendorExistsRule()],
            'target_customer_rate' => 'nullable|numeric|min:0',
            'reason'               => 'required|string|max:255',
        ]);

        $validated['target_batch_id'] = $entry->batch_id;

        $this->dayLoadBillingService->transferWeight($entry, $validated, $validated['reason']);

        return back()->with('success', "{$validated['transfer_weight']} kg transferred successfully.");
    }

    public function update(Request $request, DayLoadEntry $entry): RedirectResponse
    {
        $validated = $request->validate([
            'vendor_id'     => ['required', $this->vendorExistsRule()],
            'dealer_id'     => ['required', $this->dealerExistsRule()],
            'sort_order'    => 'nullable|integer|min:1',
            'paper_rate'    => 'required|numeric|min:0',
            'billing_rate'  => 'required|numeric|min:0',
            'customer_rate' => 'required|numeric|min:0',
            'no_of_boxes'   => 'required|integer|min:1',
            'box_weight'    => 'required|numeric|gt:empty_weight',
            'empty_weight'  => 'required|numeric|min:0',
            'farm_weight'   => 'nullable|numeric|min:0',
            'remarks'       => 'nullable|string|max:255',
            'reason'        => 'required|string|max:255',
        ]);

        $this->dayLoadBillingService->updateEntry($entry, $validated);

        return back()->with('success', 'Entry updated successfully.');
    }

    public function bulkUpdate(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'entries'   => 'required|array',
            'entries.*.id' => 'required|exists:day_load_entries,id',
            'entries.*.farm_weight' => 'nullable|numeric|min:0',
            'entries.*.remarks' => 'nullable|string|max:255',
            'reason'    => 'required|string|max:255',
        ]);

        foreach ($validated['entries'] as $entryData) {
            $entry = DayLoadEntry::find($entryData['id']);

            if ($entry && $entry->status === 'Active') {
                $oldValues = $entry->toArray();

                $entry->update([
                    'farm_weight' => $entryData['farm_weight'] ?? null,
                    'remarks'     => $entryData['remarks'] ?? null,
                ]);

                EntryAdjustmentLog::create([
                    'entry_id'            => $entry->id,
                    'action_type'         => 'Edit',
                    'old_values'          => $oldValues,
                    'new_values'          => $entry->fresh()->toArray(),
                    'resulting_entry_id'  => null,
                    'reason'              => $validated['reason'],
                    'adjusted_by'         => auth()->id(),
                ]);

                $this->dayLoadBillingService->refreshBatchTotals($entry->batch);
            }
        }

        return back()->with('success', 'All entries updated successfully.');
    }

    public function setFarmWeight(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'batch_id'          => 'required|exists:day_load_batches,id',
            'total_farm_weight' => 'required|numeric|min:0',
            'reason'            => 'required|string|max:255',
        ]);

        $batch = DayLoadBatch::findOrFail($validated['batch_id']);
        $totalFarmWeight = (float) $validated['total_farm_weight'];

        $entries = $batch->entries()->where('status', '!=', 'Cancelled')->get();
        $totalBirdWeight = (float) $entries->sum('bird_weight');

        if ($totalBirdWeight <= 0) {
            return back()->with('error', 'No active entries with bird weight found.');
        }

        // Distribute proportional farm weight and loss to each entry
        $remainingFarmWeight = $totalFarmWeight;
        $entriesCount = $entries->count();
        $i = 0;

        foreach ($entries as $entry) {
            $i++;
            $oldValues = $entry->toArray();

            if ($i === $entriesCount) {
                $entryFw = round($remainingFarmWeight, 2);
            } else {
                $ratio = (float) $entry->bird_weight / $totalBirdWeight;
                $entryFw = round($ratio * $totalFarmWeight, 2);
                $remainingFarmWeight -= $entryFw;
            }

            $entryLoss = round($entryFw - (float) $entry->bird_weight, 2);

            $entry->updateQuietly([
                'farm_weight'  => $entryFw,
                'loss_weight'  => $entryLoss,
                'total_weight' => (float) $entry->bird_weight,
            ]);

            EntryAdjustmentLog::create([
                'entry_id'           => $entry->id,
                'action_type'        => 'Edit',
                'old_values'         => $oldValues,
                'new_values'         => $entry->fresh()->toArray(),
                'resulting_entry_id' => null,
                'reason'             => $validated['reason'] . " (Overall Farm Weight Distributed: {$entryFw} kg)",
                'adjusted_by'        => auth()->id(),
            ]);
        }

        // Set batch-level fields
        $batch->update([
            'total_farm_weight' => $totalFarmWeight,
            'total_loss_weight' => round($totalFarmWeight - $totalBirdWeight, 2),
            'total_weight'      => $totalBirdWeight,
        ]);

        $this->dayLoadBillingService->refreshBatchTotals($batch);

        return back()->with('success', 'Overall Farm Weight distributed and calculated across all entries successfully.');
    }

    public function approveWeightLoss(DayLoadBatch $batch): RedirectResponse
    {
        try {
            $this->dayLoadBillingService->approveWeightLoss($batch, auth()->id());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Weight loss expense of Rs ' . number_format((float) $batch->weight_loss_amount, 2) . ' approved and added to Cash & Bank Ledger.');
    }

    public function recordDealerPayment(Request $request, DayLoadEntry $entry): RedirectResponse
    {
        $validated = $request->validate([
            'date'              => 'required|date|before_or_equal:today',
            'payment_mode'      => 'required|in:' . implode(',', config('payments.modes')),
            'cash_amount'       => 'required|numeric|min:0',
            'bank_amount'       => 'required|numeric|min:0',
                'bank_transfer_type' => [
                    function ($attribute, $value, $fail) {
                        $bankAmount = (float) (request()->input('bank_amount') ?? 0);
                        if ($bankAmount > 0) {
                            if (blank($value)) {
                                $fail('The bank transfer type field is required when bank amount is greater than 0.');
                            } elseif (!in_array($value, ['UPI', 'Bank Transfer', 'NEFT', 'RTGS', 'IMPS', 'Cheque', 'Other'], true)) {
                                $fail('The selected bank transfer type is invalid.');
                            }
                        }
                    },
                ],
            'amount'            => 'nullable|numeric|min:0',
            'reference_number'  => 'nullable|string|max:100',
            'notes'             => 'nullable|string|max:500',
        ]);

        if ((float) $validated['cash_amount'] + (float) $validated['bank_amount'] <= 0) {
            return back()->with('error', 'Total payment amount must be greater than zero.');
        }

        try {
            $this->dayLoadPaymentService->recordDealerPayment($entry, $validated);
        } catch (\Exception $e) {
            return back()->with('error', 'Could not record payment: ' . $e->getMessage());
        }

        return back()->with('success', 'Dealer payment recorded successfully.');
    }

    public function recordVendorPayment(Request $request, DayLoadEntry $entry): RedirectResponse
    {
        $validated = $request->validate([
            'date'               => 'required|date|before_or_equal:today',
            'cash_amount'        => 'required|numeric|min:0',
            'bank_amount'        => 'required|numeric|min:0',
            'payment_mode'       => 'required|in:' . implode(',', config('payments.modes')),
            'bank_transfer_type' => 'nullable|required_if:bank_amount,>0|in:UPI,Bank Transfer,NEFT,RTGS,IMPS,Cheque,Other',
            'reference_number'   => 'nullable|string|max:100',
            'notes'              => 'nullable|string|max:500',
        ]);

        if ((float) $validated['cash_amount'] + (float) $validated['bank_amount'] <= 0) {
            return back()->with('error', 'Total payment amount must be greater than zero.');
        }

        try {
            $this->dayLoadPaymentService->recordVendorPayment($entry, $validated);
        } catch (\Exception $e) {
            return back()->with('error', 'Could not record payment: ' . $e->getMessage());
        }

        return back()->with('success', 'Vendor payment recorded successfully.');
    }

    public function recordLumpSumDealerPayment(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dealer_id'          => ['required', $this->dealerExistsRule()],
            'date'               => 'required|date|before_or_equal:today',
            'cash_amount'        => 'required|numeric|min:0',
            'bank_amount'        => 'required|numeric|min:0',
            'payment_mode'       => 'required|in:' . implode(',', config('payments.modes')),
            'bank_transfer_type' => [
                'nullable',
                function ($attribute, $value, $fail) {
                    $bankAmount = (float) (request()->input('bank_amount') ?? 0);
                    if ($bankAmount > 0) {
                        if (blank($value)) {
                            $fail('The bank transfer type field is required when bank amount is greater than 0.');
                        } elseif (!in_array($value, ['UPI', 'Bank Transfer', 'NEFT', 'RTGS', 'IMPS', 'Cheque', 'Other'], true)) {
                            $fail('The selected bank transfer type is invalid.');
                        }
                    }
                },
            ],
            'allocations'        => 'required|array|min:1',
            'allocations.*'      => 'required|numeric|min:0',
            'reference_number'   => 'nullable|string|max:100',
            'notes'              => 'nullable|string|max:500',
        ]);

        $totalAmount = round((float) $validated['cash_amount'] + (float) $validated['bank_amount'], 2);
        if ($totalAmount <= 0) {
            return back()->with('error', 'Total payment amount must be greater than zero.');
        }

        $allocSum = round(collect($validated['allocations'])->sum(fn ($v) => (float) $v), 2);
        if ($allocSum > $totalAmount) {
            return back()->with('error',
                'Total allocation (Rs ' . number_format($allocSum, 2) . ') exceeds lump-sum amount (Rs ' . number_format($totalAmount, 2) . ').'
            );
        }

        try {
            $this->dayLoadPaymentService->recordLumpSumDealerPayment($validated);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            return back()->with('error', 'Could not record lump-sum payment: ' . $e->getMessage());
        }

        return back()->with('success', 'Lump-sum dealer payment recorded successfully.');
    }

    public function export(Request $request): StreamedResponse
    {
        $date = $request->input('date', today()->format('Y-m-d'));

        $entries = DayLoadEntry::with(['vendor', 'dealer', 'batch'])
            ->whereHas('batch', fn ($q) => $q->whereDate('billing_date', $date))
            ->where('status', '!=', 'Cancelled')
            ->get();

        $rows = $entries->map(fn ($e) => [
            $e->batch->billing_date->format('Y-m-d'),
            $e->vendor->firm_name ?? '-',
            $e->dealer->firm_name ?? '-',
            $e->no_of_boxes,
            number_format((float) $e->bird_weight, 2),
            number_format((float) ($e->farm_weight ?? 0), 2),
            number_format((float) ($e->loss_weight ?? 0), 2),
            number_format((float) $e->total_weight, 2),
            $e->paper_rate,
            $e->billing_rate,
            $e->customer_rate,
            $e->status,
        ]);

        $filename = 'day-load-' . $date;
        return $this->exporter->streamCsv($filename, [
            'Date', 'Vendor', 'Dealer', 'Boxes', 'Bird Wt', 'Farm Wt', 'Loss Wt', 'Total Wt',
            'Paper Rate', 'Billing Rate', 'Customer Rate', 'Status',
        ], $rows);
    }

    public function destroyDate(string $date): \Illuminate\Http\RedirectResponse
    {
        $dateObj = Carbon::parse($date);
        
        $entries = DayLoadEntry::with(['dealerPayments', 'vendorPayments', 'dailyBill', 'weeklyBill'])
            ->whereHas('batch', fn ($q) => $q->whereDate('billing_date', $dateObj))->get();
            
        if ($entries->isEmpty()) {
            return back()->with('error', 'No entries found for this date.');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($entries, $dateObj) {
            $dealerService = app(\App\Services\DealerPaymentService::class);
            $ledgerDates = [];

            foreach ($entries as $entry) {
                // Delete dealer payments safely to update ledgers and statuses
                foreach ($entry->dealerPayments as $dp) {
                    $dealerService->deletePayment($dp);
                }
                
                // Delete vendor payments and track dates for ledger sync
                foreach ($entry->vendorPayments as $vp) {
                    if ($vp->date) $ledgerDates[] = $vp->date->format('Y-m-d');
                    $vp->delete();
                }
                
                // Delete associated bills
                if ($entry->daily_bill_id) {
                    \App\Models\DailyBill::where('id', $entry->daily_bill_id)->delete();
                }
                if ($entry->weekly_bill_id) {
                    \App\Models\WeeklyBill::where('id', $entry->weekly_bill_id)->delete();
                }

                // Delete adjustments
                $entry->advanceAdjustments()->delete();
                $entry->adjustmentLogs()->delete();
                \App\Models\EntryAdjustmentLog::where('resulting_entry_id', $entry->id)->delete();
                
                $entry->delete();
            }

            // Sync vendor payment ledgers
            foreach (array_unique($ledgerDates) as $dStr) {
                app(\App\Services\CashBankLedgerService::class)->recalculateForDate(\Carbon\Carbon::parse($dStr));
            }

            // Delete the batches
            DayLoadBatch::whereDate('billing_date', $dateObj)->delete();
        });

        return back()->with('success', "All Day Load data, Bills, and Payments for {$dateObj->format('d M Y')} have been successfully deleted.");
    }

    public function destroyEntry(DayLoadEntry $entry): \Illuminate\Http\RedirectResponse
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($entry) {
            $dealerService = app(\App\Services\DealerPaymentService::class);
            $ledgerDates = [];
            $batchId = $entry->batch_id;
            
            // 1. Cascade Dealer Payments
            foreach ($entry->dealerPayments as $dp) {
                $dealerService->deletePayment($dp);
            }
            
            // 2. Cascade Vendor Payments
            foreach ($entry->vendorPayments as $vp) {
                if ($vp->date) $ledgerDates[] = $vp->date->format('Y-m-d');
                $vp->delete();
            }
            
            // 3. Cascade Bills
            if ($entry->daily_bill_id) {
                \App\Models\DailyBill::where('id', $entry->daily_bill_id)->delete();
            }
            if ($entry->weekly_bill_id) {
                \App\Models\WeeklyBill::where('id', $entry->weekly_bill_id)->delete();
            }

            // 4. Cascade Adjustments
            $entry->advanceAdjustments()->delete();
            $entry->adjustmentLogs()->delete();
            \App\Models\EntryAdjustmentLog::where('resulting_entry_id', $entry->id)->delete();
            
            // 5. Delete the entry itself
            $entry->delete();

            // 6. Recalculate Ledgers for vendor payments
            foreach (array_unique($ledgerDates) as $dStr) {
                app(\App\Services\CashBankLedgerService::class)->recalculateForDate(\Carbon\Carbon::parse($dStr));
            }

            // 7. If this was the last entry in the batch, delete the batch
            if ($batchId) {
                if (!DayLoadEntry::where('batch_id', $batchId)->exists()) {
                    DayLoadBatch::where('id', $batchId)->delete();
                } else {
                    $batch = DayLoadBatch::find($batchId);
                    if ($batch) {
                        app(\App\Services\DayLoadPaymentService::class)->refreshBatchFinancials($batch);
                    }
                }
            }
        });

        return back()->with('success', 'Single Day Load Entry (and any associated payments/bills) successfully deleted.');
    }

    public function invoice(string $date): View
    {
        $dateObj = Carbon::parse($date);
        $batch = DayLoadBatch::whereDate('billing_date', $date)->first();
        $entries = DayLoadEntry::with(['vendor', 'dealer'])
            ->whereHas('batch', fn ($q) => $q->whereDate('billing_date', $date))
            ->where('status', '!=', 'Cancelled')
            ->orderByRaw('CASE WHEN sort_order IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('sort_order', 'asc')
            ->latest('id')
            ->get();

        return view('billing.day-load.invoice', compact('dateObj', 'batch', 'entries'));
    }

    public function downloadPdf(string $date)
    {
        $dateObj = Carbon::parse($date);
        $batch = DayLoadBatch::whereDate('billing_date', $date)->first();
        $entries = DayLoadEntry::with(['vendor', 'dealer'])
            ->whereHas('batch', fn ($q) => $q->whereDate('billing_date', $date))
            ->where('status', '!=', 'Cancelled')
            ->orderByRaw('CASE WHEN sort_order IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('sort_order', 'asc')
            ->latest('id')
            ->get();

        $pdf = Pdf::loadView('billing.day-load.pdf', compact('dateObj', 'batch', 'entries'));
        return $pdf->download("day-load-{$date}.pdf");
    }

    public function vendorRatesForm(Request $request): View
    {
        $vendors = Vendor::orderBy('firm_name')->get();
        $groupedEntries = collect();
        $financialSummary = null;
        $selectedVendorId = null;

        if ($request->filled('vendor_id')) {
            $selectedVendorId = (int) $request->vendor_id;

            $entries = DayLoadEntry::with(['batch', 'vendor'])
                ->where('vendor_id', $selectedVendorId)
                ->where('status', '!=', 'Cancelled')
                ->whereHas('batch', fn($q) => $q->where('status', '!=', 'Locked'))
                ->get();

            $groupedEntries = $entries
                ->groupBy(fn($e) => $e->batch->billing_date->format('Y-m-d'))
                ->map(fn($items, $date) => [
                    'date'               => $date,
                    'count'              => $items->count(),
                    'total_weight'       => round($items->sum('bird_weight'), 2),
                    'total_farm_weight'  => round($items->sum('farm_weight'), 2),
                    'has_all_farm_weight' => $items->every(fn($e) => $e->farm_weight !== null),
                    'paper_rate'         => (float) $items->first()->paper_rate,
                    'current_rate'       => (float) ($items->first()->billing_rate ?: 0),
                    'entry_ids'          => $items->pluck('id')->toArray(),
                    'batch_ids'          => $items->pluck('batch_id')->unique()->values()->toArray(),
                ])->sortKeys();

            $currentVendorCost = $entries->sum(fn($e) => $e->vendor_cost);
            $financialSummary = [
                'current_vendor_cost' => $currentVendorCost,
                'total_entries'       => $entries->count(),
                'total_weight'        => round($entries->sum('bird_weight'), 2),
                'total_farm_weight'   => round($entries->sum('farm_weight'), 2),
                'entries_without_farm_weight' => $entries->filter(fn($e) => $e->farm_weight === null)->count(),
            ];
        }

        return view('billing.day-load.vendor-rates', compact(
            'vendors', 'groupedEntries', 'financialSummary', 'selectedVendorId'
        ));
    }

    public function setVendorRates(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vendor_id' => ['required', $this->vendorExistsRule()],
            'rates'     => 'required|array',
            'rates.*'   => 'required|numeric|min:0',
            'reason'    => 'required|string|max:255',
        ]);

        $allEntries = DayLoadEntry::with('batch')
            ->where('vendor_id', $validated['vendor_id'])
            ->where('status', '!=', 'Cancelled')
            ->whereHas('batch', fn($q) => $q->where('status', '!=', 'Locked'))
            ->get()
            ->groupBy(fn($e) => $e->batch->billing_date->format('Y-m-d'));

        foreach ($validated['rates'] as $date => $rate) {
            if (!isset($allEntries[$date]) || $allEntries[$date]->isEmpty()) {
                return back()->with('error', "No active entries found for date: {$date}.");
            }
        }

        $updatedCount = 0;
        $costBefore = 0;
        $costAfter = 0;
        $statusChanges = ['Overpaid' => 0, 'Pending' => 0, 'Partial' => 0, 'Unchanged' => 0];
        $skippedLocked = 0;

        DB::transaction(function () use ($allEntries, $validated, &$updatedCount, &$costBefore, &$costAfter, &$statusChanges, &$skippedLocked) {
            $refreshedBatches = collect();

            foreach ($validated['rates'] as $date => $newRate) {
                foreach ($allEntries[$date] as $entry) {
                    $costBefore += $entry->vendor_cost;

                    $oldBillingRate = $entry->billing_rate;
                    $entry->updateQuietly(['billing_rate' => $newRate]);

                    $costAfter += $entry->vendor_cost;

                    EntryAdjustmentLog::create([
                        'entry_id'    => $entry->id,
                        'action_type' => 'Edit',
                        'old_values'  => ['billing_rate' => (float) ($oldBillingRate ?: 0)],
                        'new_values'  => ['billing_rate' => $newRate],
                        'reason'      => $validated['reason'],
                        'adjusted_by' => auth()->id(),
                    ]);

                    $oldStatus = $entry->getOriginal('vendor_payment_status');
                    $this->dayLoadPaymentService->refreshVendorPaymentStatus($entry);
                    $entry->refresh();
                    $newStatus = $entry->vendor_payment_status;
                    $statusChanges[$oldStatus === $newStatus ? 'Unchanged' : $newStatus]++;

                    if (!$refreshedBatches->has($entry->batch_id)) {
                        $refreshedBatches->put($entry->batch_id, true);
                        $this->dayLoadBillingService->refreshBatchTotals($entry->batch);
                        $this->dayLoadPaymentService->refreshBatchFinancials($entry->batch);
                    }

                    $updatedCount++;
                }
            }
        });

        $difference = round($costBefore - $costAfter, 2);

        return back()->with('success', 'Vendor final rates updated successfully.')
            ->with('update_summary', [
                'dates_updated'  => count($validated['rates']),
                'entries_updated'=> $updatedCount,
                'cost_before'    => round($costBefore, 2),
                'cost_after'     => round($costAfter, 2),
                'difference'     => $difference,
                'status_changes' => $statusChanges,
            ]);
    }
}
