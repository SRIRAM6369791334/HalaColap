<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\StoreDealerPaymentRequest;
use App\Models\DayLoadEntry;
use App\Models\Dealer;
use App\Models\DealerPayment;
use App\Services\DealerPaymentService;
use App\Services\ExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DealerPaymentController extends Controller
{
    public function __construct(
        private DealerPaymentService $service,
        private ExportService        $exporter,
    ) {}

    public function index(Request $request): View
    {
        $search       = $request->input('search');
        $dealerFilter = $request->input('dealer_id');
        $dateFrom     = $request->input('date_from');
        $dateTo       = $request->input('date_to');
        $modeFilter   = $request->input('payment_mode');

        $paymentsQuery = DealerPayment::with('dealer')
            ->search($search)
            ->when($dealerFilter, fn($q) => $q->where('dealer_id', $dealerFilter))
            ->when($dateFrom, fn($q) => $q->whereDate('date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('date', '<=', $dateTo))
            ->when($modeFilter, fn($q) => $q->where('payment_mode', $modeFilter));

        $totalCollected = (clone $paymentsQuery)->sum('amount');

        $payments = $paymentsQuery->orderByDesc('date')->orderByDesc('id')->paginate(15);

        $dealers = Dealer::orderBy('firm_name')->get();
        return view('payments.dealers', compact(
            'payments', 'dealers', 'search',
            'dealerFilter', 'dateFrom', 'dateTo', 'modeFilter', 'totalCollected'
        ));
    }

    public function create(Request $request): View
    {
        $selected_dealer_id = $request->input('dealer_id');
        $query = Dealer::orderBy('firm_name');
        if ($selected_dealer_id) {
            $query->where('id', $selected_dealer_id);
        }
        $dealers = $query->get();

        $pendingDayLoadCount = 0;
        $weeklyBills = collect();
        $dayLoadEntries = collect();
        $pendingEmis = collect();

        $unallocatedPayments = 0;
        $dayLoadEntriesTotal = 0;
        $pendingEmisTotal = 0;

        if ($selected_dealer_id) {
            $unallocatedPayments = (float) \App\Models\DealerPayment::where('dealer_id', $selected_dealer_id)
                ->whereNull('day_load_entry_id')
                ->whereNull('invoice_id')
                ->sum('amount');

            $pendingDayLoadCount = DayLoadEntry::where('dealer_id', $selected_dealer_id)
                ->whereIn('dealer_payment_status', ['Pending', 'Partial'])
                ->where('status', '!=', 'Cancelled')
                ->count();

            // Fetch pending/partial weekly bills
            $weeklyBills = \App\Models\WeeklyBill::where('dealer_id', $selected_dealer_id)
                ->whereIn('status', ['Generated', 'Pending'])
                ->orderBy('period_start')
                ->get();

            // Fetch unpaid/partial day load entries
            $dayLoadEntries = DayLoadEntry::where('dealer_id', $selected_dealer_id)
                ->whereIn('dealer_payment_status', ['Pending', 'Partial'])
                ->where('status', '!=', 'Cancelled')
                ->with('batch')
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
                return (float)$e->amount - (float)$e->dealer_collected;
            });

            // Fetch pending EMIs for this dealer
            $pendingEmis = \App\Models\Emi::where('emi_type', 'Dealer')
                ->where('entity_id', $selected_dealer_id)
                ->where('status', '!=', 'Paid')
                ->orderBy('due_date')
                ->get();

            $pendingEmisTotal = $pendingEmis->sum('remaining_amount');
        }

        return view('payments.dealers.create', compact(
            'dealers', 'selected_dealer_id', 'pendingDayLoadCount', 'weeklyBills', 'dayLoadEntries',
            'unallocatedPayments', 'dayLoadEntriesTotal', 'pendingEmis', 'pendingEmisTotal'
        ));
    }

    public function store(StoreDealerPaymentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        
        if ((float) $validated['cash_amount'] + (float) $validated['bank_amount'] <= 0) {
            return back()->with('error', 'Total payout amount must be greater than zero.');
        }
        
        // 1. Record payment (Service handles deduction)
        $payment = $this->service->record($validated);

        return back()->with('success', 'Dealer payment recorded and balance updated.');
    }

    public function ledger(Dealer $dealer): View
    {
        $dayLoads = $dealer->dayLoadEntries()
            ->with(['vendor', 'batch'])
            ->get();

        $payments = $dealer->payments()->with('dayLoadEntry.vendor')->get();

        $rows = [];

        foreach ($dayLoads as $e) {
            $rows[] = [
                'date' => $e->batch->billing_date,
                'type' => 'load',
                'desc' => 'Day-Load: ' . $e->no_of_boxes . ' boxes (' . ($e->vendor->firm_name ?? '-') . ')',
                'ref' => 'DL-' . $e->id,
                'debit' => round((float) $e->bird_weight, 2),
                'credit' => 0,
                'group_id' => null,
                'sub_items' => [],
            ];
        }

        foreach ($payments as $p) {
            $desc = 'Payment (' . $p->payment_mode . ')';
            if ($p->notes) {
                $desc .= ' - ' . $p->notes;
            } elseif ($p->day_load_entry_id) {
                $desc .= ' [Day-Load #' . $p->day_load_entry_id . ']';
            }

            $rows[] = [
                'date' => $p->date,
                'type' => 'payment',
                'desc' => $desc,
                'ref' => 'PAY-' . str_pad($p->id, 4, '0', STR_PAD_LEFT),
                'debit' => 0,
                'credit' => round((float) $p->amount, 2),
                'group_id' => $p->payment_group_id,
                'sub_items' => [],
            ];

            if ((float) $p->discount_amount > 0) {
                $rows[] = [
                    'date' => $p->date,
                    'type' => 'payment',
                    'desc' => 'Discount Credit',
                    'ref' => 'DSC-' . str_pad($p->id, 4, '0', STR_PAD_LEFT),
                    'debit' => 0,
                    'credit' => round((float) $p->discount_amount, 2),
                    'group_id' => $p->payment_group_id,
                    'sub_items' => [],
                ];
            }
        }

        usort($rows, fn($a, $b) => $a['date'] <=> $b['date']);

        $totalDebit = array_sum(array_column($rows, 'debit'));
        $totalCredit = array_sum(array_column($rows, 'credit'));

        $page = request()->input('page', 1);
        $perPage = 20;
        $offset = ($page - 1) * $perPage;
        $sliced = array_slice($rows, $offset, $perPage);

        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            $sliced,
            count($rows),
            $perPage,
            $page,
            ['path' => request()->url()]
        );

        return view('payments.dealers.ledger', compact(
            'dealer', 'paginated', 'totalDebit', 'totalCredit'
        ));
    }

    public function export(): StreamedResponse
    {
        $rows = $this->service->allForExport()->map(fn($p) => [
            $p->dealer->firm_name ?? '—', $p->date->format('Y-m-d'), $p->amount, $p->payment_mode, $p->notes,
        ]);
        return $this->exporter->streamCsv(
            'dealer-payments',
            ['Dealer','Date','Amount','Mode','Notes'],
            $rows
        );
    }

    public function destroy(DealerPayment $payment): RedirectResponse
    {
        $this->service->deletePayment($payment);
        return back()->with('success', 'Dealer payment removed and balances updated.');
    }
}
