<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreDealerRequest;
use App\Models\Dealer;
use App\Models\Route;
use App\Services\DealerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DealerController extends Controller
{
    public function __construct(private DealerService $service) {}

    public function index(Request $request): View
    {
        $search  = $request->input('search');
        $balanceFilter = $request->input('balance');
        
        $dealersQuery = Dealer::with('routeRelation')->search($search);
        $dealersCollection = \App\Services\DealerService::arrangeBySlotSequence($dealersQuery->get());

        // Calculate stats on all matching dealers
        $totalOutstanding = $dealersCollection->filter(fn($d) => $d->displayed_outstanding > 0)->sum(fn($d) => $d->displayed_outstanding);
        $activeDealersCount = $dealersCollection->filter(fn($d) => $d->displayed_outstanding > 0)->count();

        // Apply balance filter in collection
        if ($balanceFilter === 'pending') {
            $dealersCollection = $dealersCollection->filter(fn($d) => $d->displayed_outstanding > 0);
        } elseif ($balanceFilter === 'cleared') {
            $dealersCollection = $dealersCollection->filter(fn($d) => $d->displayed_outstanding <= 0);
        }

        // Paginate the collection manually
        $page = (int) $request->input('page', 1);
        $perPage = 15;
        $sliced = $dealersCollection->slice(($page - 1) * $perPage, $perPage)->values();

        $dealers = new \Illuminate\Pagination\LengthAwarePaginator(
            $sliced,
            $dealersCollection->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('masters.dealers.index', compact(
            'dealers', 'search', 'balanceFilter', 'totalOutstanding', 'activeDealersCount'
        ));
    }

    public function create(): View
    {
        $routes = Route::orderBy('route_name')->get();
        return view('masters.dealers.create', compact('routes'));
    }

    public function store(StoreDealerRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());
        return redirect()->route('masters.dealers.index')->with('success', 'Dealer added successfully.');
    }

    public function update(StoreDealerRequest $request, Dealer $dealer): RedirectResponse
    {
        $this->service->update($dealer, $request->validated());
        return redirect()->route('masters.dealers.index')->with('success', 'Dealer updated.');
    }

    public function show(Dealer $dealer): View
    {
        $recentPurchases = $dealer->purchases()->with('items')->latest()->take(3)->get();
        $recentPayments = $dealer->payments()->latest()->take(3)->get();

        $totalBoxesReceived = $dealer->dayLoadEntries()->sum('no_of_boxes');
        $totalBirdWeight = $dealer->dayLoadEntries()->sum('bird_weight');
        $totalFarmWeight = $dealer->dayLoadEntries()->sum('farm_weight');
        $totalLossWeight = $dealer->dayLoadEntries()->sum('loss_weight');
        $lossRate = $totalBirdWeight > 0 ? round(($totalLossWeight / $totalBirdWeight) * 100, 2) : 0;
        $loadCount = $dealer->dayLoadEntries()->count();

        return view('masters.dealers.show', compact(
            'dealer', 'recentPurchases', 'recentPayments',
            'totalBoxesReceived', 'totalBirdWeight', 'totalFarmWeight',
            'totalLossWeight', 'lossRate', 'loadCount'
        ));
    }

    public function edit(Dealer $dealer): View
    {
        $routes = Route::orderBy('route_name')->get();
        return view('masters.dealers.edit', compact('dealer', 'routes'));
    }

    public function destroy(Dealer $dealer): RedirectResponse
    {
        $hasTransactions = $dealer->dayLoadEntries()->exists()
            || $dealer->payments()->exists()
            || (method_exists($dealer, 'dealerPayments') && $dealer->dealerPayments()->exists())
            || \App\Models\DealerPayment::where('dealer_id', $dealer->id)->exists()
            || (method_exists($dealer, 'dailyBills') && $dealer->dailyBills()->exists())
            || \App\Models\DailyBill::where('dealer_id', $dealer->id)->exists()
            || $dealer->dealerPurchases()->exists()
            || $dealer->purchases()->exists();

        if ($hasTransactions) {
            return redirect()->route('masters.dealers.index')
                ->with('error', 'Cannot delete dealer with existing transactions or billing records.');
        }

        $this->service->delete($dealer);
        return redirect()->route('masters.dealers.index')->with('success', 'Dealer deleted.');
    }

    public function purchaseHistory(Dealer $dealer): View
    {
        $purchases = $dealer->purchases()->with('items')->latest()->paginate(15);

        $dayLoadEntries = $dealer->dayLoadEntries()
            ->with(['vendor', 'batch'])
            ->latest()
            ->paginate(15);

        $totalBoxes = $dealer->dayLoadEntries()->sum('no_of_boxes');
        $totalBirdWeight = $dealer->dayLoadEntries()->sum('bird_weight');
        $totalLossWeight = $dealer->dayLoadEntries()->sum('loss_weight');

        return view('masters.dealers.purchase-history', compact(
            'dealer', 'purchases', 'dayLoadEntries',
            'totalBoxes', 'totalBirdWeight', 'totalLossWeight'
        ));
    }

    public function outstandingReport(Dealer $dealer): View
    {
        $today = now()->toDateString();

        $purchases = $dealer->purchases()->where('total_amount', '>', 0)->get();
        $payments = $dealer->payments()->get();

        $dayLoadEntries = $dealer->dayLoadEntries()
            ->where('status', '!=', 'Cancelled')
            ->with(['dealerPayments', 'batch'])
            ->get();

        $totalPurchased = (float) $purchases->sum('total_amount') + (float) $dayLoadEntries->sum('amount');
        $totalPaid = (float) $payments->sum('amount');
        $outstanding = $dealer->displayed_outstanding;

        $buckets = ['0_30' => 0, '31_60' => 0, '60_plus' => 0];

        // 1. Aging for feed/regular purchases
        foreach ($purchases as $purchase) {
            $days = \Carbon\Carbon::parse($purchase->date)->diffInDays(now());
            $amount = (float) $purchase->total_amount;
            if ($days <= 30) {
                $buckets['0_30'] += $amount;
            } elseif ($days <= 60) {
                $buckets['31_60'] += $amount;
            } else {
                $buckets['60_plus'] += $amount;
            }
        }

        // 2. Aging for Day-Loads
        foreach ($dayLoadEntries as $entry) {
            $entryOutstanding = (float) $entry->amount - (float) $entry->dealerPayments->sum('amount');
            if ($entryOutstanding <= 0) continue;

            $date = $entry->batch ? $entry->batch->billing_date : $entry->created_at;
            $days = \Carbon\Carbon::parse($date)->diffInDays(now());
            
            if ($days <= 30) {
                $buckets['0_30'] += $entryOutstanding;
            } elseif ($days <= 60) {
                $buckets['31_60'] += $entryOutstanding;
            } else {
                $buckets['60_plus'] += $entryOutstanding;
            }
        }

        // 3. Add base pending amount to 60+ days bucket
        if ($dealer->pending_amount > 0) {
            $buckets['60_plus'] += (float) $dealer->pending_amount;
        }

        $avgPaymentDays = null;
        if ($payments->isNotEmpty()) {
            $totalDays = 0;
            $matchedPayments = 0;
            foreach ($payments as $payment) {
                $relatedPurchase = $purchases->where('date', '<=', $payment->date)->last();
                if ($relatedPurchase) {
                    $totalDays += \Carbon\Carbon::parse($relatedPurchase->date)->diffInDays($payment->date);
                    $matchedPayments++;
                }
            }
            $avgPaymentDays = $matchedPayments > 0 ? round($totalDays / $matchedPayments) : null;
        }

        return view('masters.dealers.outstanding-report', compact(
            'dealer', 'totalPurchased', 'totalPaid', 'outstanding',
            'buckets', 'avgPaymentDays', 'payments'
        ));
    }

    public function downloadLedgerPdf(Dealer $dealer)
    {
        $purchases = $dealer->purchases()->get()->map(fn($p) => [
            'date' => $p->date,
            'desc' => "Purchase #{$p->id} ({$p->item})",
            'debit' => $p->total_amount,
            'credit' => 0,
        ]);

        // Old-style payments only (no day-load invoice/entry link)
        $payments = $dealer->payments()
            ->whereNull('invoice_id')
            ->whereNull('day_load_entry_id')
            ->get()->map(fn($p) => [
                'date' => $p->date,
                'desc' => "Payment Sent ({$p->payment_mode})",
                'debit' => 0,
                'credit' => $p->amount,
            ]);

        $ledger = $purchases->concat($payments)->sortBy('date');

        // Day-load entries (non-cancelled, date from batch.billing_date)
        $dayLoadEntries = $dealer->dayLoadEntries()
            ->where('status', '!=', 'Cancelled')
            ->with('batch')
            ->get()->map(fn($e) => [
                'date' => optional($e->batch)->billing_date ?? $e->created_at->format('Y-m-d'),
                'desc' => "Day-Load #{$e->id} ({$e->bird_weight} kg × Rs {$e->customer_rate}/kg)",
                'debit' => (float) $e->amount,
                'credit' => 0,
            ]);

        // Day-load payments (linked via invoice_id or day_load_entry_id)
        $dayLoadPayments = $dealer->payments()
            ->where(fn($q) => $q->whereNotNull('invoice_id')
                ->orWhereNotNull('day_load_entry_id'))
            ->get()->map(fn($p) => [
                'date' => $p->date->format('Y-m-d'),
                'desc' => "Payment — {$p->payment_mode}"
                    . ($p->reference_number ? " ({$p->reference_number})" : ''),
                'debit' => 0,
                'credit' => (float) $p->amount,
            ]);

        $dayLoadLedger = $dayLoadEntries->concat($dayLoadPayments)->sortBy('date');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('masters.dealers.ledger_pdf', [
            'dealer' => $dealer,
            'ledger' => $ledger,
            'dayLoadLedger' => $dayLoadLedger,
        ]);

        return $pdf->download("ledger-{$dealer->firm_name}.pdf");
    }
}
