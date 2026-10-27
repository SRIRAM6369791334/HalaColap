<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\DayLoadEntry;
use App\Models\Vendor;
use App\Models\VendorPayment;
use App\Services\VendorPaymentService;
use App\Services\ExportService;
use App\Services\CashBankLedgerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorPaymentController extends Controller
{
    public function __construct(
        private VendorPaymentService $service,
        private ExportService        $exporter,
    ) {}

    public function index(Request $request): View
    {
        $search       = $request->input('search');
        $vendorFilter = $request->input('vendor_id');
        $dateFrom     = $request->input('date_from');
        $dateTo       = $request->input('date_to');
        $modeFilter   = $request->input('payment_mode');
        $tab          = $request->input('tab', 'payouts');

        $paymentsQuery = VendorPayment::query()
            ->when($search, function ($q) use ($search) {
                $q->where(function ($nested) use ($search) {
                    $nested->whereHas('vendor', fn($v) => $v->where('firm_name', 'like', "%{$search}%"))
                           ->orWhere('reference_number', 'like', "%{$search}%");
                });
            })
            ->when($vendorFilter, fn($q) => $q->where('vendor_id', $vendorFilter))
            ->when($dateFrom, fn($q) => $q->whereDate('date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('date', '<=', $dateTo))
            ->when($modeFilter, fn($q) => $q->where('payment_mode', $modeFilter));

        $totalPaidOut = (clone $paymentsQuery)->sum('amount');

        $payments = $this->service->paginatedDailyGrouped(
            $search, $vendorFilter, $dateFrom, $dateTo, $modeFilter, 15
        )->withQueryString();
        $vendors = Vendor::orderBy('firm_name')->get();

        // Vendor Advances Query
        $advancesQuery = \App\Models\VendorAdvance::with(['vendor', 'adjustments.dayLoadEntry'])
            ->when($search, function ($q) use ($search) {
                $q->where(function ($nested) use ($search) {
                    $nested->whereHas('vendor', fn($v) => $v->where('firm_name', 'like', "%{$search}%"))
                           ->orWhere('reference_number', 'like', "%{$search}%");
                });
            })
            ->when($vendorFilter, fn($q) => $q->where('vendor_id', $vendorFilter))
            ->when($dateFrom, fn($q) => $q->whereDate('date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('date', '<=', $dateTo));

        $totalAdvancesGiven = (clone $advancesQuery)->sum('total_amount');
        $totalAdvancesRemaining = (clone $advancesQuery)->where('status', '!=', 'Fully Adjusted')->get()->sum(fn($a) => $a->remaining_amount);
        $advances = (clone $advancesQuery)->latest('date')->paginate(15, ['*'], 'advances_page');

        // Current Balances for Multi-Source Advance Payment
        $todayLedger = app(\App\Services\CashBankLedgerService::class)->getOrCreateForDate(now());
        $currentCashBalance = (float) $todayLedger->closing_cash_balance;
        $currentBankBalance = (float) $todayLedger->closing_bank_balance;

        $totalInvested = (float) \App\Models\CapitalTransaction::where('type', 'Investment')->sum('amount');
        $totalTransferredToBusiness = (float) \App\Models\CapitalTransaction::whereIn('type', ['Transfer to Cash', 'Transfer to Bank'])->sum('amount');
        $totalTransferredFromBusiness = (float) \App\Models\CapitalTransaction::whereIn('type', ['Transfer from Cash', 'Transfer from Bank'])->sum('amount');
        $totalWithdrawn = (float) \App\Models\CapitalTransaction::where('type', 'Withdrawal')->sum('amount');
        $totalVendorAdvanceFunded = (float) \App\Models\CapitalTransaction::where('type', 'Vendor Advance Outflow')->sum('amount');

        $currentInvestmentBalance = round(
            $totalInvested + $totalTransferredFromBusiness - $totalTransferredToBusiness - $totalWithdrawn - $totalVendorAdvanceFunded,
            2
        );

        return view('payments.vendors', compact(
            'payments', 'advances', 'vendors', 'search',
            'vendorFilter', 'dateFrom', 'dateTo', 'modeFilter', 'tab',
            'totalPaidOut', 'totalAdvancesGiven', 'totalAdvancesRemaining',
            'currentCashBalance', 'currentBankBalance', 'currentInvestmentBalance'
        ));
    }

    public function storeAdvance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'vendor_id'          => 'required|exists:vendors,id',
            'date'               => 'required|date',
            'cash_amount'        => 'nullable|numeric|min:0',
            'bank_amount'        => 'nullable|numeric|min:0',
            'investment_amount'  => 'nullable|numeric|min:0',
            'payment_mode'       => 'nullable|string',
            'bank_transfer_type' => 'nullable|string',
            'reference_number'   => 'nullable|string|max:100',
            'notes'              => 'nullable|string|max:500',
        ]);

        $cashAmount = (float) ($validated['cash_amount'] ?? 0);
        $bankAmount = (float) ($validated['bank_amount'] ?? 0);
        $investmentAmount = (float) ($validated['investment_amount'] ?? 0);
        $totalAmount = round($cashAmount + $bankAmount + $investmentAmount, 2);

        if ($totalAmount <= 0) {
            return back()->with('error', 'Total advance amount must be greater than zero.');
        }

        // Determine payment mode automatically from funding source split
        $sources = [];
        if ($cashAmount > 0) $sources[] = 'Cash';
        if ($bankAmount > 0) $sources[] = 'Bank (' . ($validated['bank_transfer_type'] ?? 'UPI') . ')';
        if ($investmentAmount > 0) $sources[] = 'Pool';
        $computedMode = count($sources) > 1 ? implode(' + ', $sources) : ($sources[0] ?? 'Cash');
        $paymentMode = !empty($validated['payment_mode']) ? $validated['payment_mode'] : $computedMode;

        $vendor = Vendor::findOrFail($validated['vendor_id']);

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $vendor, $cashAmount, $bankAmount, $investmentAmount, $totalAmount, $paymentMode) {
            // 1. If funded from investment pool, log CapitalTransaction
            if ($investmentAmount > 0) {
                \App\Models\CapitalTransaction::create([
                    'type'               => 'Vendor Advance Outflow',
                    'date'               => $validated['date'],
                    'amount'             => $investmentAmount,
                    'payment_mode'       => 'Investment Pool',
                    'person_name'        => $vendor->firm_name,
                    'reference_number'   => $validated['reference_number'] ?? null,
                    'notes'              => "Advance payment for {$vendor->firm_name} (funded from investment)",
                    'created_by'         => auth()->id(),
                ]);
            }

            // 2. Create Vendor Advance
            \App\Models\VendorAdvance::create([
                'vendor_id'          => $vendor->id,
                'date'               => $validated['date'],
                'total_amount'       => $totalAmount,
                'cash_amount'        => $cashAmount,
                'bank_amount'        => $bankAmount,
                'investment_amount'  => $investmentAmount,
                'adjusted_amount'    => 0.00,
                'payment_mode'       => $paymentMode,
                'bank_transfer_type' => $validated['bank_transfer_type'] ?? null,
                'status'             => 'Pending',
                'reference_number'   => $validated['reference_number'] ?? null,
                'notes'              => $validated['notes'] ?? 'Vendor advance',
                'created_by'         => auth()->id(),
            ]);

            // 3. Recalculate Cash/Bank ledger for that date
            if ($cashAmount > 0 || $bankAmount > 0) {
                app(\App\Services\CashBankLedgerService::class)->recalculateForDate(\Carbon\Carbon::parse($validated['date']));
            }
        });

        return back()->with('success', "Advance of Rs " . number_format($totalAmount, 2) . " recorded for {$vendor->firm_name}.");
    }

    public function destroyAdvance(\App\Models\VendorAdvance $advance): RedirectResponse
    {
        if ((float) $advance->adjusted_amount > 0) {
            return back()->with('error', 'Cannot delete an advance that has already been partially or fully adjusted against Day-Load entries.');
        }

        $advDate = $advance->date;
        $invAmount = (float) $advance->investment_amount;
        $vendorName = $advance->vendor->firm_name ?? 'Vendor';

        \Illuminate\Support\Facades\DB::transaction(function () use ($advance, $advDate, $invAmount, $vendorName) {
            if ($invAmount > 0) {
                \App\Models\CapitalTransaction::where('type', 'Vendor Advance Outflow')
                    ->whereDate('date', $advDate)
                    ->where('amount', $invAmount)
                    ->where('person_name', $vendorName)
                    ->latest()
                    ->first()?->delete();
            }

            $advance->delete();

            app(\App\Services\CashBankLedgerService::class)->recalculateForDate(\Carbon\Carbon::parse($advDate));
        });

        return back()->with('success', 'Vendor advance deleted and ledger balances updated.');
    }

    public function create(Request $request): View
    {
        $selected_vendor_id = $request->input('vendor_id');
        $query = Vendor::orderBy('firm_name');
        if ($selected_vendor_id) {
            $query->where('id', $selected_vendor_id);
        }
        $vendors = $query->get();

        $pendingDayLoadCount = 0;
        $dayLoadEntries = collect();
        $pendingEmis = collect();

        $unallocatedPayments = 0;
        $dayLoadEntriesTotal = 0;
        $pendingEmisTotal = 0;

        if ($selected_vendor_id) {
            $unallocatedPayments = (float) VendorPayment::where('vendor_id', $selected_vendor_id)
                ->whereNull('day_load_entry_id')
                ->sum('amount');

            $pendingDayLoadCount = DayLoadEntry::where('vendor_id', $selected_vendor_id)
                ->whereIn('vendor_payment_status', ['Pending', 'Partial'])
                ->where('status', '!=', 'Cancelled')
                ->count();

            $dayLoadEntries = DayLoadEntry::where('vendor_id', $selected_vendor_id)
                ->whereIn('vendor_payment_status', ['Pending', 'Partial'])
                ->where('status', '!=', 'Cancelled')
                ->with(['dealer', 'batch'])
                ->get()
                ->sort(function($a, $b) {
                    $dateA = $a->batch ? $a->batch->billing_date->timestamp : $a->created_at->timestamp;
                    $dateB = $b->batch ? $b->batch->billing_date->timestamp : $b->created_at->timestamp;
                    if ($dateA !== $dateB) return $dateA <=> $dateB;
                    $orderA = $a->sort_order !== null ? (int)$a->sort_order : 999999;
                    $orderB = $b->sort_order !== null ? (int)$b->sort_order : 999999;
                    if ($orderA !== $orderB) return $orderA <=> $orderB;
                    return $b->id <=> $a->id;
                })->values();

            $dayLoadEntriesTotal = $dayLoadEntries->sum(function($e) {
                return (float)$e->vendor_cost - (float)$e->vendor_paid;
            });

            // Fetch pending EMIs for this vendor
            $pendingEmis = \App\Models\Emi::where('emi_type', 'Vendor')
                ->where('entity_id', $selected_vendor_id)
                ->where('status', '!=', 'Paid')
                ->orderBy('due_date')
                ->get();

            $pendingEmisTotal = $pendingEmis->sum('remaining_amount');
        }

        return view('payments.vendors.create', compact(
            'vendors', 'selected_vendor_id', 'pendingDayLoadCount', 'dayLoadEntries',
            'unallocatedPayments', 'dayLoadEntriesTotal', 'pendingEmis', 'pendingEmisTotal'
        ));
    }

    public function storeGeneralPayment(Request $request): RedirectResponse
    {
        $paymentMode = $request->input('payment_mode');
        if (!$paymentMode || $paymentMode === 'Bank Transfer') {
            $paymentMode = ((float) ($request->input('bank_amount') ?? 0) > 0)
                ? ($request->input('bank_transfer_type') ?: 'Bank Transfer')
                : 'Cash';
            $request->merge(['payment_mode' => $paymentMode]);
        }

        $validated = $request->validate([
            'vendor_id'          => 'required|exists:vendors,id',
            'date'               => 'required|date',
            'cash_amount'        => 'required|numeric|min:0',
            'bank_amount'        => 'required|numeric|min:0',
            'payment_mode'       => 'required|in:Cash,Bank Transfer,UPI,NEFT,RTGS,IMPS,Cheque,Other',
            'bank_transfer_type' => 'nullable|required_if:bank_amount,>0|in:UPI,Bank Transfer,NEFT,RTGS,IMPS,Cheque,Other',
            'notes'              => 'nullable|string',
            'selected_entry_ids' => 'nullable|array',
            'selected_entry_ids.*' => 'exists:day_load_entries,id',
            'selected_emi_ids'   => 'nullable|array',
            'selected_emi_ids.*' => 'exists:emis,id',
        ]);

        $cashAmount = (float) $validated['cash_amount'];
        $bankAmount = (float) $validated['bank_amount'];
        $amount = round($cashAmount + $bankAmount, 2);

        if ($cashAmount + $bankAmount <= 0) {
            return back()->with('error', 'Total payment amount must be greater than zero.');
        }

        if ($amount > 0) {
            $this->service->record($validated);
        }

        return redirect()->route('payments.vendors.index')->with('success', 'Vendor payment recorded and balance updated.');
    }

    public function ledger(Vendor $vendor): View
    {
        $purchases = $vendor->purchases()->orderBy('date', 'desc')->get();
        $payments = $vendor->vendorPayments()->orderBy('date', 'desc')->get();
        $dayLoads = $vendor->dayLoadEntries()->with('batch')->get();
        
        $transactions = collect();
        
        foreach ($purchases as $p) {
            $transactions->push((object)[
                'type' => 'Purchase',
                'date' => $p->date,
                'reference' => 'Invoice: ' . $p->id,
                'amount' => $p->total_amount,
                'mode' => $p->payment_mode,
                'is_credit' => $p->payment_mode === 'Credit',
            ]);
        }

        foreach ($dayLoads as $e) {
            $transactions->push((object)[
                'type' => 'Day-Load',
                'date' => $e->batch->billing_date,
                'reference' => 'DL-' . $e->id . ' (' . $e->no_of_boxes . ' boxes)',
                'amount' => round((float) $e->bird_weight, 2),
                'mode' => 'Load',
                'is_credit' => true,
            ]);
        }
        
        $groupedPayments = $payments->groupBy(fn($p) => $p->date->format('Y-m-d'));
        foreach ($groupedPayments as $dateStr => $dayPayments) {
            $dayTotal = (float) $dayPayments->sum('amount');
            $modes = $dayPayments->pluck('payment_mode')->unique()->filter()->values();
            $modeStr = $modes->count() > 1 ? 'Split' : ($modes->first() ?? 'UPI');
            $refNotes = $dayPayments->pluck('reference_number')->filter()->unique()->implode(', ');
            $refStr = 'Paid via ' . $modeStr . ($refNotes ? " ({$refNotes})" : '');
            if ($dayPayments->count() > 1) {
                $refStr .= " ({$dayPayments->count()} bills settled)";
            }

            $transactions->push((object)[
                'type' => 'Payment',
                'date' => \Carbon\Carbon::parse($dateStr),
                'reference' => $refStr,
                'amount' => $dayTotal,
                'mode' => $modeStr,
                'is_credit' => false,
                'id' => $dayPayments->first()->id,
            ]);
        }
        
        $transactions = $transactions->sortByDesc('date');
        
        return view('masters.vendors.ledger', compact('vendor', 'transactions'));
    }

    public function store(Request $request, Vendor $vendor): RedirectResponse
    {
        $paymentMode = $request->input('payment_mode');
        if (!$paymentMode || $paymentMode === 'Bank Transfer') {
            $paymentMode = ((float) ($request->input('bank_amount') ?? 0) > 0)
                ? ($request->input('bank_transfer_type') ?: 'Bank Transfer')
                : 'Cash';
            $request->merge(['payment_mode' => $paymentMode]);
        }

        $validated = $request->validate([
            'date'               => 'required|date',
            'cash_amount'        => 'required|numeric|min:0',
            'bank_amount'        => 'required|numeric|min:0',
            'payment_mode'       => 'required|in:Cash,Bank Transfer,UPI,NEFT,RTGS,IMPS,Cheque,Other',
            'bank_transfer_type' => 'nullable|required_if:bank_amount,>0|in:UPI,Bank Transfer,NEFT,RTGS,IMPS,Cheque,Other',
            'notes'              => 'nullable|string'
        ]);
        
        $cashAmount = (float) $validated['cash_amount'];
        $bankAmount = (float) $validated['bank_amount'];
        $amount = round($cashAmount + $bankAmount, 2);
        
        if ($cashAmount + $bankAmount <= 0) {
            return back()->with('error', 'Total payment amount must be greater than zero.');
        }

        if ($amount > $vendor->outstanding_balance) {
            return back()->withErrors(['amount' => "The payment amount cannot exceed the vendor's outstanding balance of Rs " . number_format($vendor->outstanding_balance, 2) . "."])->withInput();
        }

        $validated['vendor_id'] = $vendor->id;
        $validated['amount'] = $amount;
        
        $this->service->record($validated);
        
        return back()->with('success', 'Payment recorded successfully.');
    }

    public function destroy(Vendor $vendor, VendorPayment $payment): RedirectResponse
    {
        $paymentDate = $payment->date;
        $dateStr = $paymentDate->format('Y-m-d');

        // Delete all payment records for this vendor on this date (reversing day-load allocation)
        $dayPayments = VendorPayment::where('vendor_id', $vendor->id)
            ->whereDate('date', $dateStr)
            ->get();

        $dayLoadService = app(\App\Services\DayLoadPaymentService::class);
        foreach ($dayPayments as $p) {
            if ($p->day_load_entry_id && $p->dayLoadEntry) {
                $p->dayLoadEntry->decrement('vendor_paid', $p->amount);
                $dayLoadService->refreshVendorPaymentStatus($p->dayLoadEntry);
                if ($p->dayLoadEntry->batch) {
                    $dayLoadService->refreshBatchFinancials($p->dayLoadEntry->batch);
                }
            }
            $p->delete();
        }

        app(CashBankLedgerService::class)->recalculateForDate(\Carbon\Carbon::parse($paymentDate));
        
        return back()->with('success', 'Payment deleted and ledger updated successfully.');
    }

    public function export(): StreamedResponse
    {
        $rows = $this->service->allForExportDailyGrouped()->map(fn($p) => [
            $p->vendor->firm_name ?? '—', $p->date->format('Y-m-d'), $p->amount, $p->payment_mode, $p->notes,
        ]);
        return $this->exporter->streamCsv(
            'vendor-payments',
            ['Vendor','Date','Amount','Mode','Notes'],
            $rows
        );
    }
}
