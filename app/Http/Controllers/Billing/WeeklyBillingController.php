<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Dealer;
use App\Models\WeeklyBill;
use App\Models\WeeklyBillItem;
use App\Models\Item;
use App\Models\DealerPurchase;
use App\Models\DayLoadEntry;
use App\Models\DayLoadBatch;
use App\Services\ExportService;
use App\Services\WeeklyBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WeeklyBillingController extends Controller
{
    public function __construct(
        private ExportService $exporter,
        private WeeklyBillingService $billingService
    ) {}

    public function index(Request $request): View
    {
        $search = $request->input('search');

        // Compute true totals from the full dataset (not paginated)
        $pendingBills = WeeklyBill::search($search)->where('status', 'Pending')->get();
        $outstandingDuesTotal = 0;
        foreach ($pendingBills as $bill) {
            $periodPaid = \App\Models\DealerPayment::where('dealer_id', $bill->dealer_id)
                ->where(function($q) use ($bill) {
                    $q->whereBetween('date', [$bill->period_start, $bill->period_end])
                      ->orWhere('invoice_id', $bill->id);
                })->sum('amount');
            $outstandingDuesTotal += max(0, (float)$bill->net_amount - $periodPaid);
        }

        $paidRevenueTotal = WeeklyBill::search($search)
            ->where('status', 'Paid')
            ->selectRaw('SUM(COALESCE(net_amount, amount)) as total')
            ->value('total') ?? 0;

        $bills = WeeklyBill::with(['dealer', 'items'])
            ->search($search)
            ->latest()
            ->paginate(15, ['*'], 'bills_page');

        $purchases = DealerPurchase::with(['dealer', 'items'])
            ->search($search)
            ->latest()
            ->paginate(15, ['*'], 'purchases_page');

        $dealers = Dealer::orderBy('firm_name')->get();
        $items = Item::active()->get();

        return view('billing.weekly.index', compact(
            'bills', 'purchases', 'dealers', 'search', 'items',
            'outstandingDuesTotal', 'paidRevenueTotal'
        ));
    }

    public function bulk(): View
    {
        $dealers = Dealer::orderBy('firm_name')->get();
        return view('billing.weekly.bulk', compact('dealers'));
    }

    /**
     * Store a daily/individual dealer purchase.
     */
    public function storePurchase(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dealer_id'    => 'required|exists:dealers,id',
            'date'         => 'required|date|before_or_equal:today',
            'items'        => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.qty'  => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0.01',
        ]);

        try {
            $this->billingService->createPurchase($validated);
        } catch (\Exception $e) {
            return back()->with('error', 'Could not record purchase: ' . $e->getMessage());
        }

        return back()->with('success', 'Dealer daily purchase recorded successfully.');
    }

    /**
     * Preview weekly billing calculation for a dealer.
     */
    public function calculatePreview(Request $request): JsonResponse
    {
        $request->validate([
            'dealer_id'       => 'required|exists:dealers,id',
            'period_start'    => 'required|date',
            'period_end'      => 'required|date|after_or_equal:period_start',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $dealerId = $request->input('dealer_id');
            $earliestEntry = DayLoadEntry::where('dealer_id', $dealerId)
                ->whereNull('weekly_bill_id')
                ->where('status', '!=', 'Cancelled')
                ->whereIn('dealer_payment_status', ['Pending', 'Partial'])
                ->whereHas('batch')
                ->get()
                ->sortBy(fn($e) => $e->batch->billing_date->timestamp)
                ->first();

            if ($earliestEntry && $earliestEntry->batch) {
                $earliestDate = $earliestEntry->batch->billing_date->format('Y-m-d');
                $requestedDate = \Carbon\Carbon::parse($request->input('period_start'))->format('Y-m-d');
                if ($earliestDate !== $requestedDate) {
                    return response()->json([
                        'success' => false,
                        'message' => "The start date must be the earliest unbilled day-load entry date: " . \Carbon\Carbon::parse($earliestDate)->format('d M Y') . "."
                    ], 400);
                }
            }

            $discountAmount = (float)$request->input('discount_amount', 0.0);
            $totals = $this->billingService->calculateWeeklyTotals(
                $request->input('dealer_id'),
                $request->input('period_start'),
                $request->input('period_end'),
                $discountAmount
            );
            
            // Check for overlapping weekly bills
            $overlappingBill = WeeklyBill::where('dealer_id', $request->input('dealer_id'))
                ->whereDate('period_start', '<=', $request->input('period_end'))
                ->whereDate('period_end', '>=', $request->input('period_start'))
                ->first();

            $exists = false;
            $overlap = false;
            $overlapStart = null;
            $overlapEnd = null;

            if ($overlappingBill) {
                $oldStart = \Carbon\Carbon::parse($overlappingBill->period_start)->format('Y-m-d');
                $oldEnd = \Carbon\Carbon::parse($overlappingBill->period_end)->format('Y-m-d');
                $reqStart = \Carbon\Carbon::parse($request->input('period_start'))->format('Y-m-d');
                $reqEnd = \Carbon\Carbon::parse($request->input('period_end'))->format('Y-m-d');

                if ($oldStart === $reqStart && $oldEnd === $reqEnd) {
                    $exists = true;
                } else {
                    $overlap = true;
                    $overlapStart = \Carbon\Carbon::parse($overlappingBill->period_start)->format('d M Y');
                    $overlapEnd = \Carbon\Carbon::parse($overlappingBill->period_end)->format('d M Y');
                }
            }

            // Format for preview response
            $purchasesCount = $totals['purchases']->count();
            
            return response()->json([
                'success' => true,
                'previous_outstanding' => $totals['previous_outstanding'],
                'total_purchases' => $totals['total_purchases'],
                'total_payments' => $totals['total_payments'],
                'net_invoice_amount' => $totals['net_invoice_amount'],
                'balance_due' => $totals['balance_due'],
                'purchases_count' => $purchasesCount,
                'discount_amount' => $discountAmount,
                'exists' => $exists,
                'overlap' => $overlap,
                'overlap_start' => $overlapStart,
                'overlap_end' => $overlapEnd,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    public function generateWeekly(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dealer_id'        => 'required|exists:dealers,id',
            'period_start'     => 'required|date',
            'period_end'       => 'required|date|after_or_equal:period_start',
            'discount_amount'  => 'nullable|numeric|min:0',
            'replace_existing' => 'nullable|boolean',
        ]);

        $dealerId = $request->input('dealer_id');
        $earliestEntry = DayLoadEntry::where('dealer_id', $dealerId)
            ->whereNull('weekly_bill_id')
            ->where('status', '!=', 'Cancelled')
            ->whereIn('dealer_payment_status', ['Pending', 'Partial'])
            ->whereHas('batch')
            ->get()
            ->sortBy(fn($e) => $e->batch->billing_date->timestamp)
            ->first();

        if ($earliestEntry && $earliestEntry->batch) {
            $earliestDate = $earliestEntry->batch->billing_date->format('Y-m-d');
            $requestedDate = \Carbon\Carbon::parse($request->input('period_start'))->format('Y-m-d');
            if ($earliestDate !== $requestedDate) {
                return back()->with('error', "Could not generate weekly bill: The start date must be the earliest unbilled day-load entry date: " . \Carbon\Carbon::parse($earliestDate)->format('d M Y') . ".");
            }
        }

        try {
            $this->billingService->generateWeeklyBill($validated);
        } catch (\Exception $e) {
            return back()->with('error', 'Could not generate weekly bill: ' . $e->getMessage());
        }

        return back()->with('success', 'Weekly bill generated successfully.');
    }

    /**
     * Get the earliest unpaid day-load entry date for a dealer.
     */
    public function getEarliestUnpaidDate(Request $request): JsonResponse
    {
        $request->validate([
            'dealer_id' => 'required|exists:dealers,id',
        ]);

        $earliestEntry = DayLoadEntry::where('dealer_id', $request->input('dealer_id'))
            ->whereNull('weekly_bill_id')
            ->where('status', '!=', 'Cancelled')
            ->whereIn('dealer_payment_status', ['Pending', 'Partial'])
            ->whereHas('batch')
            ->get()
            ->sortBy(fn($e) => $e->batch->billing_date->timestamp)
            ->first();

        $date = null;
        if ($earliestEntry && $earliestEntry->batch) {
            $date = $earliestEntry->batch->billing_date->format('Y-m-d');
        }

        return response()->json([
            'success' => true,
            'date' => $date,
        ]);
    }

    /**
     * Legacy Manual Store method.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dealer_id'    => 'required|exists:dealers,id',
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
            'status'       => 'required|in:Generated,Pending,Paid',
            'payment_mode' => 'required|in:Cash,UPI,NEFT,Cheque(Bank Transfer),Pay later(EMI)',
            'items'        => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.qty'  => 'required|numeric|min:0.01',
            'items.*.rate' => 'required|numeric|min:0.01',
            'items.*.unit' => 'nullable|string|max:20',
            'emis'           => 'required_if:payment_mode,Pay later(EMI)|array',
            'emis.*.due_date'=> 'required_if:payment_mode,Pay later(EMI)|date',
            'emis.*.amount'  => 'required_if:payment_mode,Pay later(EMI)|numeric|min:0.01',
        ]);

        try {
            $this->billingService->create($validated);
        } catch (\Exception $e) {
            return back()->with('error', 'Could not create bill: ' . $e->getMessage());
        }

        return back()->with('success', 'Weekly bill created successfully.');
    }

    /**
     * Legacy Bulk Store method.
     */
    public function bulkStore(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dealer_ids'     => 'required|array',
            'dealer_ids.*'   => 'exists:dealers,id',
            'period_start'   => 'required|date',
            'period_end'     => 'required|date|after_or_equal:period_start',
            'amount'         => 'required|numeric|min:0.01',
            'status'         => 'required|in:Generated,Pending,Paid',
            'payment_mode'   => 'required|in:Cash,UPI,NEFT,Cheque(Bank Transfer),Pay later(EMI)',
        ]);

        try {
            $count = $this->billingService->bulkCreate($validated['dealer_ids'], $validated);
        } catch (\Exception $e) {
            return back()->with('error', 'Could not create bills: ' . $e->getMessage());
        }

        return back()->with('success', $count . ' bills generated.');
    }

    public function show(WeeklyBill $weekly): View
    {
        $weekly->load(['dealer', 'items']);

        // Day-Load entries for this bill's period (customer_rate based)
        $dayLoadEntries = DayLoadEntry::with(['batch', 'vendor'])
            ->where('dealer_id', $weekly->dealer_id)
            ->where('status', '!=', 'Cancelled')
            ->whereHas('batch', fn($q) => $q
                ->whereDate('billing_date', '>=', $weekly->period_start->format('Y-m-d'))
                ->whereDate('billing_date', '<=', $weekly->period_end->format('Y-m-d')))
            ->orderBy('batch_id')
            ->orderByRaw('CASE WHEN sort_order IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('sort_order', 'asc')
            ->latest('id')
            ->get();

        $entryIds = $dayLoadEntries->pluck('id')->toArray();
        $allPayments = \App\Models\DealerPayment::where('dealer_id', $weekly->dealer_id)
            ->where(function($q) use ($weekly, $entryIds) {
                $q->whereBetween('date', [
                    $weekly->period_start->format('Y-m-d'),
                    $weekly->period_end->format('Y-m-d'),
                ])
                ->orWhereIn('day_load_entry_id', $entryIds)
                ->orWhere('invoice_id', $weekly->id);
            })
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        // Computed totals
        $dayLoadTotal = round($dayLoadEntries->sum(
            fn($e) => (float) $e->bird_weight * (float) $e->customer_rate
        ), 2);
        if ($dayLoadTotal == 0 && (float)$weekly->amount > 0) {
            $dayLoadTotal = (float)$weekly->amount;
        }

        $prevOutstanding = (float)($weekly->previous_outstanding ?? 0);
        $discountAmount = (float)($weekly->discount_amount ?? 0);
        $netInvoiceAmount = max(0, round($dayLoadTotal - $discountAmount, 2));
        $totalPayable = round($prevOutstanding + $netInvoiceAmount, 2);

        $totalPaid   = round($allPayments->sum('amount'), 2);
        $remainingDue = max(0, round($totalPayable - $totalPaid, 2));

        return view('billing.invoice', array_merge(
            compact('weekly', 'dayLoadEntries', 'allPayments', 'dayLoadTotal', 'totalPaid', 'remainingDue', 'prevOutstanding', 'totalPayable', 'netInvoiceAmount'),
            ['bill' => $weekly]
        ));
    }

    public function print(WeeklyBill $weekly): View
    {
        $weekly->load(['dealer', 'items']);
        return view('billing.invoice', ['bill' => $weekly]);
    }

    public function whatsapp(WeeklyBill $weekly): RedirectResponse
    {
        $phone = preg_replace('/[^0-9]/', '', $weekly->dealer->phone ?? '');
        if (!$phone) return back()->with('error', 'Dealer phone missing.');

        $text = urlencode("Hello {$weekly->dealer->firm_name}, your MSR BROILERS bill for period {$weekly->period_start->format('d M')} to {$weekly->period_end->format('d M')} is ₹" . number_format($weekly->net_amount, 2) . ". Thank you!");

        return redirect()->away("https://wa.me/91{$phone}?text={$text}");
    }

    public function export(): StreamedResponse
    {
        $bills = WeeklyBill::with(['dealer', 'items'])->latest()->get();
        $rows  = $bills->map(fn($b) => [
            $b->dealer->firm_name ?? '—',
            $b->period_start->format('Y-m-d'),
            $b->period_end->format('Y-m-d'),
            $b->items_description,
            $b->quantity_kg,
            $b->amount,
            $b->status,
        ]);
        return $this->exporter->streamCsv('weekly-billing', ['Dealer','From','To','Items','Qty(kg)','Amount','Status'], $rows);
    }

    public function downloadPdf(WeeklyBill $weekly)
    {
        $weekly->load(['dealer', 'items']);

        // Day-Load entries for this bill's period (customer_rate based)
        $dayLoadEntries = DayLoadEntry::with(['batch', 'vendor'])
            ->where('dealer_id', $weekly->dealer_id)
            ->where('status', '!=', 'Cancelled')
            ->whereHas('batch', fn($q) => $q
                ->whereDate('billing_date', '>=', $weekly->period_start->format('Y-m-d'))
                ->whereDate('billing_date', '<=', $weekly->period_end->format('Y-m-d')))
            ->orderBy('batch_id')
            ->orderByRaw('CASE WHEN sort_order IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('sort_order', 'asc')
            ->latest('id')
            ->get();

        $entryIds = $dayLoadEntries->pluck('id')->toArray();
        $allPayments = \App\Models\DealerPayment::where('dealer_id', $weekly->dealer_id)
            ->where(function($q) use ($weekly, $entryIds) {
                $q->whereBetween('date', [
                    $weekly->period_start->format('Y-m-d'),
                    $weekly->period_end->format('Y-m-d'),
                ])
                ->orWhereIn('day_load_entry_id', $entryIds)
                ->orWhere('invoice_id', $weekly->id);
            })
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $dayLoadTotal = round($dayLoadEntries->sum(
            fn($e) => (float) $e->bird_weight * (float) $e->customer_rate
        ), 2);
        $totalPaid    = round($allPayments->sum('amount'), 2);
        $remainingDue = max(0, round((float) $weekly->net_amount - $totalPaid, 2));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('billing.weekly.pdf', [
            'bill'           => $weekly,
            'dayLoadEntries' => $dayLoadEntries,
            'allPayments'    => $allPayments,
            'dayLoadTotal'   => $dayLoadTotal,
            'totalPaid'      => $totalPaid,
            'remainingDue'   => $remainingDue,
        ]);
        return $pdf->download("invoice-{$weekly->invoice_no}.pdf");
    }

    public function destroy(WeeklyBill $weekly): RedirectResponse
    {
        try {
            $this->billingService->deleteWeeklyBill($weekly);
        } catch (\Exception $e) {
            return back()->with('error', 'Could not delete weekly bill: ' . $e->getMessage());
        }

        return redirect()->route('billing.weekly.index')->with('success', 'Weekly bill deleted and transactions unlinked.');
    }

    public function dealerInvoice(Request $request): View
    {
        $dealerId = $request->input('dealer_id');
        $periodStart = $request->input('period_start', now()->startOfWeek()->format('Y-m-d'));
        $periodEnd = $request->input('period_end', now()->endOfWeek()->subDay()->format('Y-m-d'));
        $preset = $request->input('preset', '');

        $dealers = Dealer::orderBy('firm_name')->get();
        $entries = collect();
        $dealer = null;
        $currentBillTotal = 0;
        $previousBalance = 0;
        $grandTotal = 0;

        if ($dealerId) {
            $dealer = Dealer::find($dealerId);

            $entries = DayLoadEntry::with(['batch', 'vendor'])
                ->where('dealer_id', $dealerId)
                ->where('status', 'Active')
                ->whereHas('batch', function ($q) use ($periodStart, $periodEnd) {
                    $q->whereDate('billing_date', '>=', $periodStart)
                      ->whereDate('billing_date', '<=', $periodEnd);
                })
                ->orderBy('batch_id')
                ->orderByRaw('CASE WHEN sort_order IS NOT NULL THEN 0 ELSE 1 END')
                ->orderBy('sort_order', 'asc')
                ->latest('id')
                ->get()
                ->map(function ($entry) {
                    $rate = (float) $entry->customer_rate;
                    $kg = (float) $entry->bird_weight;
                    $total = round($kg * $rate, 2);
                    return [
                        'id'         => $entry->id,
                        'sort_order' => $entry->sort_order,
                        'date'       => $entry->batch->billing_date->format('d-m-Y'),
                        'vendor'     => $entry->vendor->firm_name ?? '-',
                        'kg'         => $kg,
                        'rate'       => $rate,
                        'total'      => $total,
                        'bird_weight'=> $kg,
                    ];
                });

            $currentBillTotal = $entries->sum('total');
            $previousBalance = (float) $dealer->pending_amount;
            $grandTotal = $currentBillTotal + $previousBalance;
        }

        return view('billing.weekly.dealer-invoice', compact(
            'dealers', 'dealer', 'entries', 'periodStart', 'periodEnd', 'preset',
            'currentBillTotal', 'previousBalance', 'grandTotal'
        ));
    }

    public function generateInvoice(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dealer_id'    => 'required|exists:dealers,id',
            'period_start' => 'required|date',
            'period_end'   => 'required|date|after_or_equal:period_start',
        ]);

        $dealer = Dealer::findOrFail($validated['dealer_id']);

        $entries = DayLoadEntry::with(['batch', 'vendor'])
            ->where('dealer_id', $validated['dealer_id'])
            ->where('status', 'Active')
            ->whereHas('batch', function ($q) use ($validated) {
                $q->whereDate('billing_date', '>=', $validated['period_start'])
                  ->whereDate('billing_date', '<=', $validated['period_end']);
            })
            ->orderBy('batch_id')
            ->orderByRaw('CASE WHEN sort_order IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('sort_order', 'asc')
            ->latest('id')
            ->get();

        if ($entries->isEmpty()) {
            return back()->with('error', 'No day-load entries found for this dealer in the selected period.');
        }

        // One line item per entry
        $currentBillTotal = 0;
        $lineItems = [];

        foreach ($entries as $entry) {
            $kg = (float) $entry->bird_weight;
            $rate = (float) $entry->customer_rate;
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

        // Create WeeklyBill
        $bill = WeeklyBill::create([
            'dealer_id'           => $validated['dealer_id'],
            'period_start'        => $validated['period_start'],
            'period_end'          => $validated['period_end'],
            'invoice_no'          => 'INV-DL-' . str_pad(WeeklyBill::max('id') + 1, 4, '0', STR_PAD_LEFT),
            'amount'              => $currentBillTotal,
            'gst_percentage'      => 0,
            'gst_amount'          => 0,
            'net_amount'          => $grandTotal,
            'status'              => 'Pending',
            'payment_mode'        => 'Pending',
            'previous_outstanding'=> $previousBalance,
            'payments_during_week'=> 0,
        ]);

        // Create line items
        foreach ($lineItems as $item) {
            WeeklyBillItem::create(array_merge($item, [
                'weekly_bill_id' => $bill->id,
                'tax_amount'     => 0,
            ]));
        }

        // Update dealer pending amount
        $dealer->update(['pending_amount' => $grandTotal]);

        return redirect()->route('billing.weekly.show', $bill->id)
            ->with('success', 'Invoice generated successfully.');
    }
}
