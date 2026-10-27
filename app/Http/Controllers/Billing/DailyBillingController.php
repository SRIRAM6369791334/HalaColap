<?php

namespace App\Services;

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Dealer;
use App\Models\DailyBill;
use App\Models\DailyBillItem;
use App\Models\Item;
use App\Models\DayLoadEntry;
use App\Services\ExportService;
use App\Services\DailyBillingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DailyBillingController extends Controller
{
    public function __construct(
        private ExportService $exporter,
        private DailyBillingService $billingService
    ) {}

    public function index(Request $request): View
    {
        $search = $request->input('search');

        $pendingBills = DailyBill::whereNotNull('dealer_id')->search($search)->where('status', 'Pending')->get();
        $outstandingDuesTotal = 0;
        foreach ($pendingBills as $bill) {
            if ($bill->dealer_id) {
                $entryIds = \App\Models\DayLoadEntry::where('daily_bill_id', $bill->id)->pluck('id')->toArray();
                $periodPaid = \App\Models\DealerPayment::where('dealer_id', $bill->dealer_id)
                    ->where(function($q) use ($bill, $entryIds) {
                        $q->whereDate('date', $bill->date->format('Y-m-d'))
                          ->orWhereIn('day_load_entry_id', $entryIds)
                          ->orWhere('invoice_id', $bill->id);
                    })->sum('amount');
            } else {
                $periodPaid = 0;
            }
            $outstandingDuesTotal += max(0, (float)$bill->net_amount - $periodPaid);
        }

        $paidRevenueTotal = DailyBill::whereNotNull('dealer_id')->search($search)
            ->where('status', 'Paid')
            ->selectRaw('SUM(COALESCE(net_amount, amount)) as total')
            ->value('total') ?? 0;

        $bills = DailyBill::whereNotNull('dealer_id')
            ->with(['dealer', 'customer', 'items'])
            ->search($search)
            ->latest()
            ->paginate(15, ['*'], 'bills_page');

        $dealerDayLoads = DayLoadEntry::with(['dealer', 'batch', 'vendor'])
            ->where('status', '!=', 'Cancelled')
            ->orderByRaw('CASE WHEN sort_order IS NOT NULL THEN 0 ELSE 1 END')
            ->orderBy('sort_order', 'asc')
            ->latest('id')
            ->paginate(15, ['*'], 'dealer_dayload_page');

        $dealers = Dealer::orderBy('firm_name')->get();
        $items = Item::active()->get();

        return view('billing.daily.index', compact(
            'bills', 'dealerDayLoads', 'dealers', 'search', 'items',
            'outstandingDuesTotal', 'paidRevenueTotal'
        ));
    }

    /**
     * Show form to manually create a daily invoice for a customer.
     */
    public function create(): \Illuminate\View\View
    {
        $customers = \App\Models\Customer::orderBy('name')->get();
        return view('billing.daily.create', compact('customers'));
    }

    /**
     * Get dealer's available stock for a specific date.
     */
    public function getDealerStock(Request $request): JsonResponse
    {
        $request->validate([
            'dealer_id' => 'required|exists:dealers,id',
            'date'      => 'required|date',
        ]);

        $dealerId = $request->input('dealer_id');
        $date = $request->input('date');

        // Calculate total stock received by dealer on this date
        $totalStock = \App\Models\DayLoadEntry::where('dealer_id', $dealerId)
            ->where('status', '!=', 'Cancelled')
            ->whereHas('batch', function($q) use ($date) {
                $q->whereDate('billing_date', $date);
            })->sum('bird_weight');

        // Calculate total stock already billed to customers on this date
        $billedStock = \App\Models\DailyBillItem::whereHas('dailyBill', function($q) use ($dealerId, $date) {
            $q->where('dealer_id', $dealerId)
              ->whereDate('date', $date)
              ->where('status', '!=', 'Cancelled')
              ->whereDoesntHave('dayLoadEntries');
        })->sum('quantity_kg');

        $availableStock = max(0, $totalStock - $billedStock);

        return response()->json([
            'success' => true,
            'total_stock' => $totalStock,
            'billed_stock' => $billedStock,
            'available_stock' => $availableStock
        ]);
    }

    /**
     * Preview daily billing calculation for a dealer.
     */
    public function calculatePreview(Request $request): JsonResponse
    {
        $request->validate([
            'dealer_id'       => 'required|exists:dealers,id',
            'date_from'       => 'nullable|date',
            'date_to'         => 'nullable|date|after_or_equal:date_from',
            'date'            => 'nullable|date',
            'discount_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            $dateFrom = $request->input('date_from') ?: $request->input('date', today()->format('Y-m-d'));
            $dateTo   = $request->input('date_to')   ?: $dateFrom;
            $discountAmount = (float)$request->input('discount_amount', 0.0);

            $totals = $this->billingService->calculateDailyTotals(
                (int) $request->input('dealer_id'),
                $dateFrom,
                $dateTo,
                $discountAmount
            );

            return response()->json([
                'success'              => true,
                'date_from'            => $dateFrom,
                'date_to'              => $dateTo,
                'previous_outstanding' => $totals['previous_outstanding'],
                'total_purchases'      => $totals['total_purchases'],
                'total_payments'       => $totals['total_payments'],
                'net_invoice_amount'   => $totals['net_invoice_amount'],
                'balance_due'          => $totals['balance_due'],
                'purchases_count'      => $totals['purchases']->count(),
                'discount_amount'      => $discountAmount,
                'exists'               => $totals['existing_bills_count'] > 0,
                'existing_bills_count' => $totals['existing_bills_count'],
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Generate daily bill for a dealer.
     */
    public function generateDaily(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'dealer_id'        => 'required|exists:dealers,id',
            'date_from'        => 'nullable|date',
            'date_to'          => 'nullable|date|after_or_equal:date_from',
            'date'             => 'nullable|date|before_or_equal:today',
            'discount_amount'  => 'nullable|numeric|min:0',
            'replace_existing' => 'nullable|boolean',
        ]);

        if (empty($validated['date_from'])) {
            $validated['date_from'] = $validated['date'] ?? today()->format('Y-m-d');
        }
        if (empty($validated['date_to'])) {
            $validated['date_to'] = $validated['date_from'];
        }
        $validated['date'] = $validated['date_to'];

        try {
            $this->billingService->generateDailyBill($validated);
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Daily bill generated successfully.');
    }

    public function show(DailyBill $daily): View
    {
        $daily->load(['dealer', 'customer', 'items']);

        if ($daily->dealer_id) {
            $dayLoadEntries = DayLoadEntry::with(['batch', 'vendor', 'dailyBill'])
                ->where('dealer_id', $daily->dealer_id)
                ->where('status', '!=', 'Cancelled')
                ->whereHas('batch', fn($q) => $q->whereDate('billing_date', $daily->date->format('Y-m-d')))
                ->orderBy('batch_id')
                ->orderByRaw('CASE WHEN sort_order IS NOT NULL THEN 0 ELSE 1 END')
                ->orderBy('sort_order', 'asc')
                ->latest('id')
                ->get();

            $entryIds = $dayLoadEntries->pluck('id')->toArray();
            $allPayments = \App\Models\DealerPayment::where('dealer_id', $daily->dealer_id)
                ->where(function($q) use ($daily, $entryIds) {
                    $q->whereDate('date', $daily->date->format('Y-m-d'))
                      ->orWhereIn('day_load_entry_id', $entryIds)
                      ->orWhere('invoice_id', $daily->id);
                })
                ->orderBy('date')
                ->orderBy('id')
                ->get();
        } elseif ($daily->customer_id) {
            $dayLoadEntries = collect();
            $allPayments = \App\Models\CustomerPayment::where('customer_id', $daily->customer_id)
                ->where(function($q) use ($daily) {
                    $q->whereDate('date', $daily->date->format('Y-m-d'))
                      ->orWhere('notes', 'like', "%{$daily->invoice_no}%");
                })
                ->orderBy('date')
                ->orderBy('id')
                ->get()
                ->map(function($cp) {
                    $cp->cash_amount = $cp->cod_amount;
                    $cp->bank_amount = $cp->bank_transfer_amount;
                    $cp->bank_transfer_type = null;
                    return $cp;
                });
        } else {
            $dayLoadEntries = collect();
            $allPayments = collect();
        }

        $dayLoadTotal = round($dayLoadEntries->sum(
            fn($e) => (float) $e->bird_weight * (float) $e->customer_rate
        ), 2);
        if ($dayLoadTotal == 0 && (float)$daily->amount > 0) {
            $dayLoadTotal = (float)$daily->amount;
        }

        $prevOutstanding = (float)($daily->previous_outstanding ?? 0);
        $discountAmount = (float)($daily->discount_amount ?? 0);
        $netInvoiceAmount = max(0, round($dayLoadTotal - $discountAmount, 2));
        $totalPayable = round($prevOutstanding + $netInvoiceAmount, 2);

        $totalPaid = round($allPayments->sum('amount'), 2);
        $remainingDue = max(0, round($totalPayable - $totalPaid, 2));
        $transfersOut = \App\Models\EntryAdjustmentLog::with(['resultingEntry.dealer'])
            ->whereIn('action_type', ['Transfer', 'Split'])
            ->whereHas('entry', function($q) use ($daily) {
                $q->where('dealer_id', $daily->dealer_id)
                  ->whereHas('batch', fn($q2) => $q2->whereDate('billing_date', $daily->date->format('Y-m-d')));
            })
            ->get();

        $transfersIn = \App\Models\EntryAdjustmentLog::with(['entry.dealer'])
            ->whereIn('action_type', ['Transfer', 'Split'])
            ->whereHas('resultingEntry', function($q) use ($daily) {
                $q->where('dealer_id', $daily->dealer_id)
                  ->whereHas('batch', fn($q2) => $q2->whereDate('billing_date', $daily->date->format('Y-m-d')));
            })
            ->get();

        $bill = $daily;

        return view('billing.invoice', array_merge(
            compact('daily', 'bill', 'dayLoadEntries', 'allPayments', 'dayLoadTotal', 'totalPaid', 'remainingDue', 'prevOutstanding', 'totalPayable', 'netInvoiceAmount', 'transfersOut', 'transfersIn'),
            ['weekly' => $daily]
        ));
    }

    public function invoice(DailyBill $bill): View
    {
        return $this->show($bill);
    }

    public function whatsapp(DailyBill $daily): RedirectResponse
    {
        $phone = preg_replace('/[^0-9]/', '', $daily->dealer->phone ?? $daily->customer->phone ?? '');
        if (!$phone) return back()->with('error', 'Phone number missing.');

        $name = $daily->dealer->firm_name ?? $daily->customer->name ?? 'Valued Client';
        $text = urlencode("Hello {$name}, your daily MSR BROILERS bill for {$daily->date->format('d M Y')} is ₹" . number_format($daily->net_amount, 2) . ". Thank you!");

        return redirect()->away("https://wa.me/91{$phone}?text={$text}");
    }

    public function export(): StreamedResponse
    {
        $bills = DailyBill::whereNotNull('dealer_id')->with(['dealer', 'customer', 'items'])->latest()->get();
        $rows  = $bills->map(fn($b) => [
            $b->dealer->firm_name ?? $b->customer->name ?? '—',
            $b->date->format('Y-m-d'),
            $b->items_description,
            $b->quantity_kg,
            $b->amount,
            $b->status,
        ]);
        return $this->exporter->streamCsv('daily-billing', ['Client', 'Date', 'Items', 'Qty(kg)', 'Amount', 'Status'], $rows);
    }

    public function downloadPdf(DailyBill $bill)
    {
        $bill->load(['dealer', 'customer', 'items']);

        if ($bill->dealer_id) {
            $dayLoadEntries = DayLoadEntry::with(['batch', 'vendor', 'dailyBill'])
                ->where('dealer_id', $bill->dealer_id)
                ->where('status', '!=', 'Cancelled')
                ->whereHas('batch', fn($q) => $q->whereDate('billing_date', $bill->date->format('Y-m-d')))
                ->orderBy('batch_id')
                ->orderByRaw('CASE WHEN sort_order IS NOT NULL THEN 0 ELSE 1 END')
                ->orderBy('sort_order', 'asc')
                ->latest('id')
                ->get();

            $entryIds = $dayLoadEntries->pluck('id')->toArray();
            $allPayments = \App\Models\DealerPayment::where('dealer_id', $bill->dealer_id)
                ->where(function($q) use ($bill, $entryIds) {
                    $q->whereDate('date', $bill->date->format('Y-m-d'))
                      ->orWhereIn('day_load_entry_id', $entryIds)
                      ->orWhere('invoice_id', $bill->id);
                })
                ->orderBy('date')
                ->orderBy('id')
                ->get();
        } elseif ($bill->customer_id) {
            $dayLoadEntries = collect();
            $allPayments = \App\Models\CustomerPayment::where('customer_id', $bill->customer_id)
                ->where(function($q) use ($bill) {
                    $q->whereDate('date', $bill->date->format('Y-m-d'))
                      ->orWhere('notes', 'like', "%{$bill->invoice_no}%");
                })
                ->orderBy('date')
                ->orderBy('id')
                ->get()
                ->map(function($cp) {
                    $cp->cash_amount = $cp->cod_amount;
                    $cp->bank_amount = $cp->bank_transfer_amount;
                    $cp->bank_transfer_type = null;
                    return $cp;
                });
        } else {
            $dayLoadEntries = collect();
            $allPayments = collect();
        }

        $dayLoadTotal = round($dayLoadEntries->sum(
            fn($e) => (float) $e->bird_weight * (float) $e->customer_rate
        ), 2);
        if ($dayLoadTotal == 0 && (float)$bill->amount > 0) {
            $dayLoadTotal = (float)$bill->amount;
        }

        $prevOutstanding = (float)($bill->previous_outstanding ?? 0);
        $discountAmount = (float)($bill->discount_amount ?? 0);
        $netInvoiceAmount = max(0, round($dayLoadTotal - $discountAmount, 2));
        $totalPayable = round($prevOutstanding + $netInvoiceAmount, 2);

        $totalPaid = round($allPayments->sum('amount'), 2);
        $remainingDue = max(0, round($totalPayable - $totalPaid, 2));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('billing.daily.pdf', [
            'bill'           => $bill,
            'dayLoadEntries' => $dayLoadEntries,
            'allPayments'    => $allPayments,
            'dayLoadTotal'   => $dayLoadTotal,
            'totalPaid'      => $totalPaid,
            'remainingDue'   => $remainingDue,
        ]);
        return $pdf->download("invoice-{$bill->invoice_number}.pdf");
    }

    public function destroy(DailyBill $daily): RedirectResponse
    {
        try {
            $this->billingService->deleteDailyBill($daily);
        } catch (\Exception $e) {
            return back()->with('error', 'Could not delete daily bill: ' . $e->getMessage());
        }

        return redirect()->route('billing.daily.index')->with('success', 'Daily bill deleted successfully.');
    }

    public function gst(): View
    {
        $bills = DailyBill::whereNotNull('dealer_id')->with(['dealer', 'customer', 'items'])->paginate(15);
        return view('billing.daily.gst', compact('bills'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id'    => 'required|exists:customers,id',
            'dealer_id'      => 'nullable|exists:dealers,id',
            'date'           => 'required|date|before_or_equal:today',
            'status'         => 'required|in:Pending,Paid,COD,Bank',
            'payment_mode'   => 'required|in:Cash,UPI,NEFT,Cheque(Bank Transfer),Pay later(EMI)',
            'gst_percentage' => 'nullable|numeric|min:0|max:28',
            'items'          => 'required|array|min:1',
            'items.*.name'   => 'required|string|max:255',
            'items.*.qty'    => 'required|numeric|min:0.01',
            'items.*.rate'   => 'required|numeric|min:0.01',
            'items.*.unit'   => 'nullable|string|max:20',
        ]);

        try {
            $bill = $this->billingService->create($validated);
        } catch (\Exception $e) {
            return back()->with('error', 'Could not create bill: ' . $e->getMessage());
        }

        return redirect()->route('billing.daily.invoice', $bill->id)->with('success', 'Customer daily invoice generated successfully.');
    }
}
