<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payments\StoreCustomerPaymentRequest;
use App\Models\Customer;
use App\Services\CustomerPaymentService;
use App\Services\ExportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerPaymentController extends Controller
{
    public function __construct(
        private CustomerPaymentService $service,
        private ExportService          $exporter,
    ) {}

    public function index(Request $request): View
    {
        $search    = $request->input('search');
        $period    = $request->input('period', 'all');
        $date      = $request->input('date', today()->format('Y-m-d'));
        $payments  = $this->service->paginated($search, 15, $period, $date);
        $customers = Customer::orderBy('name')->get();
        return view('payments.customers', compact('payments', 'customers', 'search', 'period', 'date'));
    }
    public function create(Request $request): View
    {
        $selected_customer_id = $request->input('customer_id');
        $query = Customer::orderBy('name');
        if ($selected_customer_id) {
            $query->where('id', $selected_customer_id);
        }
        $customers = $query->get();
        return view('payments.customers.create', compact('customers', 'selected_customer_id'));
    }

    public function store(StoreCustomerPaymentRequest $request): RedirectResponse
    {
        $this->service->record($request->validated());
        return back()->with('success', 'Payment recorded successfully.');
    }

    public function export(): StreamedResponse
    {
        $rows = $this->service->allForExport()->map(fn($p) => [
            $p->customer->name ?? '—', $p->date->format('Y-m-d'),
            $p->cod_amount, $p->bank_transfer_amount, $p->total_amount, $p->payment_mode, $p->payment_type, $p->balance_after,
        ]);
        return $this->exporter->streamCsv(
            'customer-payments',
            ['Customer','Date','COD Amount','Bank Transfer Amount','Total Amount','Mode','Type','Balance After'],
            $rows
        );
    }
}
