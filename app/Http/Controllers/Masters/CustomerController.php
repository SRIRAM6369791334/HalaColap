<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Http\Requests\Masters\StoreCustomerRequest;
use App\Models\Customer;
use App\Models\Route;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private CustomerService $service) {}

    public function index(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type');
        $balanceFilter = $request->input('balance');

        $query = Customer::with('routeRelation')->search($search);

        if ($type) {
            $query->where('type', $type);
        }

        if ($balanceFilter === 'pending') {
            $query->where('balance', '>', 0);
        } elseif ($balanceFilter === 'cleared') {
            $query->where('balance', '<=', 0);
        }

        if ($request->input('export') === 'pdf') {
            $customers = $query->orderBy('name')->get();
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('masters.customers.pdf', compact('customers'));
            return $pdf->download('customer-directory.pdf');
        }

        $customers = $query->orderBy('name')->paginate(15);
        return view('masters.customers.index', compact('customers', 'search', 'type', 'balanceFilter'));
    }

    public function create(): View
    {
        $routes = Route::orderBy('route_name')->get();
        return view('masters.customers.create', compact('routes'));
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());
        return redirect()->route('masters.customers.index')->with('success', 'Customer added successfully.');
    }

    public function update(StoreCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->service->update($customer, $request->validated());
        return redirect()->route('masters.customers.index')->with('success', 'Customer updated successfully.');
    }

    public function show(Customer $customer): View
    {
        $details = $this->service->getDetails($customer);
        
        return view('masters.customers.show', [
            'customer'             => $customer,
            'latestBill'           => $details['latest_bill'],
            'latestWeeklyBill'     => $details['latest_weekly_bill'],
            'latestDailyBill'      => $details['latest_daily_bill'],
            'latestPayment'        => $details['latest_payment'],
            'topRetailProducts'    => $details['top_retail_products'],
            'topWholesaleProducts' => $details['top_wholesale_products'],
            'upcomingEmis'         => $details['upcoming_emis'],
            'overdueEmis'          => $details['overdue_emis'],
        ]);
    }

    public function edit(Customer $customer): View
    {
        $routes = Route::orderBy('route_name')->get();
        return view('masters.customers.edit', compact('customer', 'routes'));
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->service->delete($customer);
        return redirect()->route('masters.customers.index')->with('success', 'Customer deleted.');
    }

    public function billingHistory(Customer $customer): View
    {
        $history = $this->service->getBillingHistory($customer);

        return view('masters.customers.billing-history', [
            'customer'          => $customer,
            'bills'             => $history['weekly_bills'],
            'weeklyBills'       => $history['weekly_bills'],
            'dailyBills'        => $history['daily_bills'],
            'totalBilled'       => $history['total_billed'],
            'totalWeeklyBilled' => $history['total_weekly_billed'],
            'totalDailyBilled'  => $history['total_daily_billed'],
        ]);
    }

    public function paymentHistory(Customer $customer): View
    {
        $history = $this->service->getPaymentHistory($customer);
        return view('masters.customers.payment-history', [
            'customer'  => $customer,
            'payments'  => $history['payments'],
            'totalPaid' => $history['total_paid'],
        ]);
    }

    public function emiHistory(Customer $customer): View
    {
        $emis = $this->service->getEmiHistory($customer);
        return view('masters.customers.emi-history', compact('customer', 'emis'));
    }

    public function downloadLedgerPdf(Customer $customer)
    {
        $bills = collect([]);

        $dailyBills = $customer->dailyBills()->get()->map(fn($b) => [
            'date' => $b->date,
            'desc' => "Daily Invoice #{$b->invoice_no}",
            'debit' => $b->net_amount,
            'credit' => 0,
            'type' => 'bill'
        ]);

        $payments = $customer->payments()->get()->map(fn($p) => [
            'date' => $p->date,
            'desc' => "Payment Recv ({$p->payment_mode})",
            'debit' => 0,
            'credit' => $p->amount,
            'type' => 'payment'
        ]);

        $ledger = $bills->concat($dailyBills)->concat($payments)->sortBy('date');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('masters.customers.ledger_pdf', [
            'customer' => $customer,
            'ledger' => $ledger
        ]);

        return $pdf->download("ledger-{$customer->name}.pdf");
    }
}
