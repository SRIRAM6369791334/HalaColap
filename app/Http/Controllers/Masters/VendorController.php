<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreVendorRequest;
use App\Models\Vendor;
use App\Services\VendorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function __construct(private VendorService $service) {}

    public function index(Request $request): View
    {
        $search  = $request->input('search');
        $routeFilter = $request->input('route');
        
        $vendorsQuery = Vendor::with(['purchases', 'dayLoadEntries.batch', 'vendorPayments', 'advances'])->search($search);
        if ($routeFilter) {
            $vendorsQuery->where('route', $routeFilter);
        }
        
        $vendorsCollection = $vendorsQuery->orderBy('firm_name')->get();

        // Calculate stats on all matching vendors
        $grossPayable = (float) $vendorsCollection->filter(fn($v) => (float) $v->outstanding_balance > 0)->sum(fn($v) => (float) $v->outstanding_balance);
        $totalAdvanceBalance = (float) $vendorsCollection->sum(function($v) {
            return $v->outstanding_balance < 0 ? abs((float) $v->outstanding_balance) : 0;
        });
        $netPayable = $grossPayable - $totalAdvanceBalance;
        $totalPayable = $grossPayable; // Matches the exact sum of positive outstanding dues in the table
        $activeVendorsCount = $vendorsCollection->filter(fn($v) => (float) $v->outstanding_balance > 0)->count();

        // Paginate the collection manually
        $page = (int) $request->input('page', 1);
        $perPage = 15;
        $sliced = $vendorsCollection->slice(($page - 1) * $perPage, $perPage)->values();

        $vendors = new \Illuminate\Pagination\LengthAwarePaginator(
            $sliced,
            $vendorsCollection->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $routes = Vendor::select('route')->distinct()->whereNotNull('route')->where('route', '!=', '')->pluck('route');
        
        $totalVendors  = Vendor::count();
        $activeRoutes  = Vendor::distinct('route')->count('route');
        $gstRegistered = Vendor::whereNotNull('gst_number')->count();
        $unregistered  = Vendor::whereNull('gst_number')->count();

        $ledgerService = app(\App\Services\CashBankLedgerService::class);
        $todayLedger = $ledgerService->getOrCreateForDate(now());
        $currentCashBalance = (float) $todayLedger->closing_cash_balance;
        $currentBankBalance = (float) $todayLedger->closing_bank_balance;
        $currentInvestmentBalance = \App\Models\CapitalTransaction::getCurrentBalance();
        $allVendors = Vendor::orderBy('firm_name')->get(['id', 'firm_name', 'contact_person']);
        
        return view('masters.vendors.index', compact(
            'vendors', 'search', 'routeFilter', 'routes',
            'totalVendors', 'activeRoutes', 'gstRegistered', 'unregistered',
            'totalPayable', 'activeVendorsCount', 'totalAdvanceBalance',
            'currentCashBalance', 'currentBankBalance', 'currentInvestmentBalance',
            'allVendors'
        ));
    }

    public function create(): View
    {
        return view('masters.vendors.create');
    }

    public function store(StoreVendorRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());
        return redirect()->route('masters.vendors.index')->with('success', 'Vendor added successfully.');
    }

    public function update(StoreVendorRequest $request, Vendor $vendor): RedirectResponse
    {
        $this->service->update($vendor, $request->validated());
        return redirect()->route('masters.vendors.index')->with('success', 'Vendor updated.');
    }

    public function show(Vendor $vendor): View
    {
        $totalPurchaseAmount = $vendor->purchases()->sum('total_amount');
        $totalPurchaseCount = $vendor->purchases()->count();
        $lastPurchaseDate = $vendor->purchases()->latest('date')->first()?->date;
        $totalBoxesLoaded = $vendor->dayLoadEntries()->sum('no_of_boxes');
        $totalBirdWeight = $vendor->dayLoadEntries()->sum('bird_weight');
        $totalFarmWeight = $vendor->dayLoadEntries()->sum('farm_weight');
        $totalLossWeight = $vendor->dayLoadEntries()->sum('loss_weight');
        $rateVarianceEntries = $vendor->dayLoadEntries()
            ->where('customer_rate', '>', 0)
            ->get(['customer_rate', 'billing_rate', 'paper_rate']);
        $totalDiff = 0;
        $rateVarCount = 0;
        foreach ($rateVarianceEntries as $rve) {
            $vRate = (float) $rve->billing_rate > 0 ? (float) $rve->billing_rate : (float) $rve->paper_rate;
            if ($vRate > 0) {
                $totalDiff += (float) $rve->customer_rate - $vRate;
                $rateVarCount++;
            }
        }
        $avgRateVariance = $rateVarCount > 0 ? round($totalDiff / $rateVarCount, 2) : 0;
        $loadCount = $vendor->dayLoadEntries()->count();

        // Calculate outstanding balance details
        $totalCreditPurchases = (float) $vendor->purchases()->where('payment_mode', 'Credit')->sum('total_amount');
        $dayLoadEntriesForLiab = $vendor->dayLoadEntries()->where('status', '!=', 'Cancelled')->with('batch')->get();
        $totalDayLoadLiabilities = (float) $dayLoadEntriesForLiab->sum(function ($entry) {
            return $entry->vendor_cost;
        });
        $totalPaymentsPaid = (float) $vendor->vendorPayments()->sum('amount');
        $outstandingBalance = round(($totalCreditPurchases + $totalDayLoadLiabilities + (float) $vendor->pending_amount) - $totalPaymentsPaid, 2);

        // Advance statistics
        $advances = $vendor->advances()->with('adjustments.dayLoadEntry')->latest('date')->get();
        $totalAdvanceGiven = (float) $vendor->advances()->sum('total_amount');
        $totalAdvanceAdjusted = (float) $vendor->advances()->sum('adjusted_amount');
        $totalActiveAdvanceBalance = (float) $vendor->active_advance_balance;
        $netSettlementBalance = round($outstandingBalance - $totalActiveAdvanceBalance, 2);

        // Account balances for advance modal
        $ledgerService = app(\App\Services\CashBankLedgerService::class);
        $todayLedger = $ledgerService->getOrCreateForDate(now());
        $currentCashBalance = (float) $todayLedger->closing_cash_balance;
        $currentBankBalance = (float) $todayLedger->closing_bank_balance;
        $currentInvestmentBalance = \App\Models\CapitalTransaction::getCurrentBalance();

        return view('masters.vendors.show', compact(
            'vendor',
            'totalPurchaseAmount', 'totalPurchaseCount', 'lastPurchaseDate',
            'totalBoxesLoaded', 'totalBirdWeight', 'totalFarmWeight', 'totalLossWeight',
            'avgRateVariance', 'loadCount',
            'totalCreditPurchases', 'totalDayLoadLiabilities', 'totalPaymentsPaid', 'outstandingBalance',
            'advances', 'totalAdvanceGiven', 'totalAdvanceAdjusted', 'totalActiveAdvanceBalance', 'netSettlementBalance',
            'currentCashBalance', 'currentBankBalance', 'currentInvestmentBalance'
        ));
    }

    public function edit(Vendor $vendor): View
    {
        return view('masters.vendors.edit', compact('vendor'));
    }

    public function destroy(Vendor $vendor): RedirectResponse
    {
        $hasTransactions = $vendor->dayLoadEntries()->exists()
            || $vendor->vendorPayments()->exists()
            || $vendor->purchases()->exists()
            || (method_exists($vendor, 'vendorAdvances') && $vendor->vendorAdvances()->exists())
            || (method_exists($vendor, 'advances') && $vendor->advances()->exists())
            || \App\Models\VendorAdvance::where('vendor_id', $vendor->id)->exists();

        if ($hasTransactions) {
            return redirect()->route('masters.vendors.index')
                ->with('error', 'Cannot delete vendor with existing transactions or purchases.');
        }

        $this->service->delete($vendor);
        return redirect()->route('masters.vendors.index')->with('success', 'Vendor deleted.');
    }

    public function purchaseHistory(Vendor $vendor): View
    {
        $dayLoadEntries = $vendor->dayLoadEntries()
            ->with(['dealer', 'batch'])
            ->latest()
            ->paginate(15);

        $totalBoxes = $vendor->dayLoadEntries()->sum('no_of_boxes');
        $totalBirdWeight = $vendor->dayLoadEntries()->sum('bird_weight');
        $totalFarmWeight = $vendor->dayLoadEntries()->sum('farm_weight');
        $totalLossWeight = $vendor->dayLoadEntries()->sum('loss_weight');

        return view('masters.vendors.purchase-history', compact(
            'vendor', 'dayLoadEntries',
            'totalBoxes', 'totalBirdWeight', 'totalFarmWeight', 'totalLossWeight'
        ));
    }

    public function downloadHistoryPdf(Vendor $vendor)
    {
        $purchases = $vendor->purchases()->with('items')->latest()->get();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('masters.vendors.history_pdf', compact('vendor', 'purchases'));
        return $pdf->download("vendor-history-{$vendor->firm_name}.pdf");
    }
}
