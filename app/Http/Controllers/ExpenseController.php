<?php

namespace App\Http\Controllers;

use App\Http\Requests\Expenses\StoreExpenseRequest;
use App\Models\Expense;
use App\Services\ExportService;
use App\Services\ExpenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Models\Emi;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseController extends Controller
{
    public function __construct(
        private ExpenseService $service,
        private ExportService  $exporter,
    ) {}

    public function index(): View
    {
        $expenses = $this->service->paginatedExpenses(15);
        $emis     = $this->service->allEmis();
        $totals   = $this->service->totals();

        $pendingWeightLossBatches = \App\Models\DayLoadBatch::where('total_loss_weight', '>', 0)
            ->where('weight_loss_amount', '>', 0)
            ->where('is_weight_loss_approved', false)
            ->orderBy('billing_date', 'desc')
            ->get();

        return view('expenses.index', compact('expenses', 'emis', 'totals', 'pendingWeightLossBatches'));
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['payment_method'] = $data['payment_method'] ?? 'Cash';
        $this->service->createExpense($data);
        return back()->with('success', 'Expense added successfully.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->service->deleteExpense($expense);
        return back()->with('success', 'Expense deleted.');
    }

    public function export(): StreamedResponse
    {
        $rows = $this->service->allExpensesForExport()->map(fn($e) => [
            $e->date->format('Y-m-d'), $e->category, $e->description, $e->amount,
        ]);
        return $this->exporter->streamCsv(
            'expenses',
            ['Date', 'Category', 'Description', 'Amount'],
            $rows
        );
    }

    public function categories(): View
    {
        $categories = Expense::select('category', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('category')
            ->get();
        return view('expenses.categories.index', compact('categories'));
    }

    public function show(Expense $expense): View
    {
        return view('expenses.show', compact('expense'));
    }

    public function edit(Expense $expense): View
    {
        return view('expenses.edit', compact('expense'));
    }

    public function create(): View
    {
        return view('expenses.create');
    }

    public function emisIndex(): View
    {
        // Auto-repair old Purchase EMIs previously saved with emi_type = 'Dealer'
        Emi::where('emi_type', 'Dealer')
            ->where('loan_name', 'like', 'Purchase%')
            ->update(['emi_type' => 'Vendor']);

        $emis = Emi::with(['customer', 'dealer', 'vendor'])->orderBy('due_date')->paginate(15);
        
        $allEmis = Emi::with(['customer', 'dealer', 'vendor'])->orderBy('due_date')->get();
        
        $toReceiveEmis = [];
        $toPayEmis = [];
        
        foreach ($allEmis as $emi) {
            $entityType = $emi->emi_type ?? 'Bank Loan';
            $entityId = $emi->entity_id ?? 0;
            
            if ($entityType === 'Customer') {
                $entityName = $emi->customer ? $emi->customer->name : 'Unknown Customer';
                $entityKey = "customer_{$entityId}";
            } elseif ($entityType === 'Dealer') {
                if ($emi->dealer) {
                    $entityName = $emi->dealer->firm_name ?? $emi->dealer->name;
                    $entityKey = "dealer_{$entityId}";
                } elseif ($emi->vendor) {
                    $entityName = $emi->vendor->firm_name;
                    $entityKey = "vendor_{$entityId}";
                    $entityType = 'Vendor';
                } else {
                    $entityName = 'Unknown Dealer/Vendor';
                    $entityKey = "dealer_{$entityId}";
                }
            } elseif ($entityType === 'Vendor') {
                $entityName = $emi->vendor ? ($emi->vendor->firm_name) : 'Unknown Vendor';
                $entityKey = "vendor_{$entityId}";
            } else {
                $entityName = $emi->bank_name ?? 'Bank Loan';
                $entityKey = 'bank_' . md5($entityName);
            }
            
            // Group by direction: To Pay vs To Receive
            $isToReceive = in_array($entityType, ['Customer', 'Dealer']);
            
            if ($isToReceive) {
                if (!isset($toReceiveEmis[$entityKey])) {
                    $toReceiveEmis[$entityKey] = [
                        'name' => $entityName,
                        'type' => $entityType,
                        'total_amount' => 0,
                        'pending_amount' => 0,
                        'total_installments' => 0,
                        'pending_installments' => 0,
                        'invoices' => []
                    ];
                }
                $targetGroup = &$toReceiveEmis;
            } else {
                if (!isset($toPayEmis[$entityKey])) {
                    $toPayEmis[$entityKey] = [
                        'name' => $entityName,
                        'type' => $entityType,
                        'total_amount' => 0,
                        'pending_amount' => 0,
                        'total_installments' => 0,
                        'pending_installments' => 0,
                        'invoices' => []
                    ];
                }
                $targetGroup = &$toPayEmis;
            }
            
            $invoiceKey = $emi->loan_name ?? 'General';
            if (!isset($targetGroup[$entityKey]['invoices'][$invoiceKey])) {
                $targetGroup[$entityKey]['invoices'][$invoiceKey] = [
                    'name' => $invoiceKey,
                    'total_amount' => 0,
                    'pending_amount' => 0,
                    'installments' => []
                ];
            }
            
            $targetGroup[$entityKey]['invoices'][$invoiceKey]['installments'][] = $emi;
            
            $targetGroup[$entityKey]['total_amount'] += $emi->amount;
            $targetGroup[$entityKey]['total_installments']++;
            $targetGroup[$entityKey]['invoices'][$invoiceKey]['total_amount'] += $emi->amount;
            
            if ($emi->status !== 'Paid') {
                $targetGroup[$entityKey]['pending_amount'] += $emi->amount;
                $targetGroup[$entityKey]['pending_installments']++;
                $targetGroup[$entityKey]['invoices'][$invoiceKey]['pending_amount'] += $emi->amount;
            }
        }
        
        $groupedEmis = array_merge($toReceiveEmis, $toPayEmis);

        // Alerts: ALL upcoming EMIs (no 7-day limit)
        $alertUpcoming = Emi::with(['customer', 'dealer', 'vendor'])
            ->where('status', 'Upcoming')
            ->orderBy('due_date')
            ->get();

        // Alerts: Overdue EMIs
        $alertOverdue = Emi::with(['customer', 'dealer', 'vendor'])
            ->where('status', 'Upcoming')
            ->whereDate('due_date', '<', today())
            ->get();
            
        $upcomingToReceive = $alertUpcoming->filter(fn($emi) => in_array($emi->emi_type, ['Customer', 'Dealer']));
        $upcomingToPay     = $alertUpcoming->filter(fn($emi) => !in_array($emi->emi_type, ['Customer', 'Dealer']));
        
        $overdueToReceive  = $alertOverdue->filter(fn($emi) => in_array($emi->emi_type, ['Customer', 'Dealer']));
        $overdueToPay      = $alertOverdue->filter(fn($emi) => !in_array($emi->emi_type, ['Customer', 'Dealer']));

        $expenses = $this->service->paginatedExpenses(15);
        $totals = $this->service->totals();
        
        return view('expenses.emis.index', compact(
            'emis', 
            'toReceiveEmis', 
            'toPayEmis', 
            'groupedEmis',
            'upcomingToReceive',
            'upcomingToPay',
            'overdueToReceive',
            'overdueToPay',
            'expenses',
            'totals'
        ));
    }

    public function emisCreate(): View
    {
        $customers = \App\Models\Customer::all();
        $dealers = \App\Models\Dealer::all();
        $vendors = \App\Models\Vendor::all();
        return view('expenses.emis.create', compact('customers', 'dealers', 'vendors'));
    }

    public function storeEmi(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'emi_type' => 'required|in:Bank Loan,Customer,Dealer,Vendor',
            'entity_id' => 'nullable|integer',
            'loan_name' => 'nullable|string',
            'bank_name' => 'nullable|string',
            'amount' => 'required|numeric',
            'due_date' => 'required|date',
            'status' => 'nullable|string',
        ]);

        $data['paid_amount'] = 0;
        if (now()->startOfDay()->greaterThan(\Carbon\Carbon::parse($data['due_date'])->startOfDay())) {
            $data['status'] = 'Overdue';
        } else {
            $data['status'] = 'Upcoming';
        }

        if (empty($data['loan_name'])) {
            if ($data['emi_type'] === 'Customer') {
                $customer = \App\Models\Customer::find($data['entity_id'] ?? 0);
                $data['loan_name'] = 'Customer EMI' . ($customer ? " - {$customer->name}" : '');
            } elseif ($data['emi_type'] === 'Dealer') {
                $dealer = \App\Models\Dealer::find($data['entity_id'] ?? 0);
                $data['loan_name'] = 'Dealer EMI' . ($dealer ? " - {$dealer->firm_name}" : '');
            } elseif ($data['emi_type'] === 'Vendor') {
                $vendor = \App\Models\Vendor::find($data['entity_id'] ?? 0);
                $data['loan_name'] = 'Vendor EMI' . ($vendor ? " - {$vendor->firm_name}" : '');
            } else {
                $data['loan_name'] = 'Bank Loan EMI';
            }
        }

        Emi::create($data);
        return redirect()->route('expenses.emis.index')->with('success', 'EMI recorded successfully.');
    }

    public function destroyEmi(Emi $emi): RedirectResponse
    {
        $emi->delete();
        return back()->with('success', 'EMI deleted.');
    }

    public function emisAlerts(): View
    {
        // EMIs due in the NEXT 7 days
        $emis = Emi::with(['customer', 'dealer', 'vendor'])
            ->where('status', 'Upcoming')
            ->whereDate('due_date', '<=', now()->addDays(7))
            ->orderBy('due_date')
            ->get();

        // Overdue EMIs
        $overdue = Emi::with(['customer', 'dealer', 'vendor'])
            ->where('status', 'Upcoming')
            ->whereDate('due_date', '<', today())
            ->get();
            
        $upcomingToReceive = $emis->filter(fn($emi) => in_array($emi->emi_type, ['Customer', 'Dealer']));
        $upcomingToPay     = $emis->filter(fn($emi) => !in_array($emi->emi_type, ['Customer', 'Dealer']));
        
        $overdueToReceive  = $overdue->filter(fn($emi) => in_array($emi->emi_type, ['Customer', 'Dealer']));
        $overdueToPay      = $overdue->filter(fn($emi) => !in_array($emi->emi_type, ['Customer', 'Dealer']));

        return view('expenses.emis.alerts', [
            'upcomingEmis' => $emis,
            'overdue' => $overdue,
            'upcomingToReceive' => $upcomingToReceive,
            'upcomingToPay' => $upcomingToPay,
            'overdueToReceive' => $overdueToReceive,
            'overdueToPay' => $overdueToPay,
        ]);
    }

    public function emisEdit(Emi $emi): View
    {
        $customers = \App\Models\Customer::all();
        $dealers = \App\Models\Dealer::all();
        $vendors = \App\Models\Vendor::all();
        return view('expenses.emis.edit', compact('emi', 'customers', 'dealers', 'vendors'));
    }

    public function updateEmi(Request $request, Emi $emi): RedirectResponse
    {
        $data = $request->validate([
            'amount' => 'required|numeric',
            'due_date' => 'required|date',
            'status' => 'nullable|string',
        ]);
        
        $emi->amount = $data['amount'];
        $emi->due_date = $data['due_date'];
        
        // Auto-calculate status to prevent breaking ledger consistency
        if ($emi->paid_amount >= $emi->amount) {
            $emi->status = 'Paid';
        } elseif ($emi->paid_amount > 0) {
            $emi->status = 'Partial';
        } elseif (now()->startOfDay()->greaterThan(\Carbon\Carbon::parse($emi->due_date)->startOfDay())) {
            $emi->status = 'Overdue';
        } else {
            $emi->status = 'Upcoming';
        }
        
        $emi->save();
        return back()->with('success', 'EMI updated successfully. (Status is auto-calculated based on payments)');
    }

    public function payEmi(Request $request, Emi $emi): RedirectResponse
    {
        $data = $request->validate([
            'cash_amount' => 'required|numeric|min:0',
            'bank_amount' => 'required|numeric|min:0',
            'payment_mode' => 'nullable|string'
        ]);
        
        $totalPaying = $data['cash_amount'] + $data['bank_amount'];
        if ($totalPaying <= 0) {
            return back()->with('error', 'Payment amount must be greater than zero.');
        }
        
        $this->processEmiPayment($emi, $data['cash_amount'], $data['bank_amount'], $data['payment_mode'] ?? 'Cash');

        return back()->with('success', 'EMI payment recorded successfully.');
    }

    public function closeFullEmi(Request $request, Emi $emi): RedirectResponse
    {
        $data = $request->validate([
            'cash_amount' => 'required|numeric|min:0',
            'bank_amount' => 'required|numeric|min:0',
            'payment_mode' => 'nullable|string'
        ]);
        
        $totalPaying = $data['cash_amount'] + $data['bank_amount'];
        if ($totalPaying <= 0) {
            return back()->with('error', 'Payment amount must be greater than zero.');
        }

        // Fetch all pending EMIs for this loan group
        $pendingEmis = Emi::where('loan_name', $emi->loan_name)
            ->where('status', '!=', 'Paid')
            ->orderBy('due_date')
            ->get();
            
        $remainingPayment = $totalPaying;
        $remainingCash = (float) $data['cash_amount'];
        $remainingBank = (float) $data['bank_amount'];

        foreach ($pendingEmis as $pendingEmi) {
            $due = max(0, $pendingEmi->amount - $pendingEmi->paid_amount);
            if ($due <= 0) continue;
            
            $allocTotal = min($remainingPayment, $due);
            if ($allocTotal <= 0) break;
            
            $allocCash = $totalPaying > 0 ? round($allocTotal * ($data['cash_amount'] / $totalPaying), 2) : 0;
            $allocBank = round($allocTotal - $allocCash, 2);
            
            // Adjust if cash/bank runs out
            if ($allocCash > $remainingCash) {
                $allocCash = $remainingCash;
                $allocBank = $allocTotal - $allocCash;
            }
            if ($allocBank > $remainingBank) {
                $allocBank = $remainingBank;
                $allocCash = $allocTotal - $allocBank;
            }
            
            $this->processEmiPayment($pendingEmi, $allocCash, $allocBank, $data['payment_mode'] ?? 'Cash');
            
            $remainingPayment -= $allocTotal;
            $remainingCash -= $allocCash;
            $remainingBank -= $allocBank;
        }

        return back()->with('success', 'EMI loan closed successfully.');
    }
    
    private function processEmiPayment(Emi $emi, float $cashAmount, float $bankAmount, string $paymentMode)
    {
        $totalAmount = $cashAmount + $bankAmount;
        if ($totalAmount <= 0) return;
        
        // If it's a Vendor EMI, we route it through the service so it acts like a normal payment
        if ($emi->emi_type === 'Vendor' && $emi->entity_id) {
            $vendor = \App\Models\Vendor::find($emi->entity_id);
            if ($vendor) {
                app(\App\Services\VendorPaymentService::class)->record([
                    'vendor_id' => $vendor->id,
                    'amount' => $totalAmount,
                    'cash_amount' => $cashAmount,
                    'bank_amount' => $bankAmount,
                    'payment_mode' => $paymentMode,
                    'date' => today()->toDateString(),
                    'selected_emi_ids' => [$emi->id],
                    'selected_entry_ids' => [],
                ]);
                return;
            }
        }
        
        // If it's a Dealer EMI, route through DealerPaymentService
        if ($emi->emi_type === 'Dealer' && $emi->entity_id) {
            $dealer = \App\Models\Dealer::find($emi->entity_id);
            if ($dealer) {
                app(\App\Services\DealerPaymentService::class)->record([
                    'dealer_id' => $dealer->id,
                    'amount' => $totalAmount,
                    'cash_amount' => $cashAmount,
                    'bank_amount' => $bankAmount,
                    'discount_amount' => 0,
                    'payment_mode' => $paymentMode,
                    'date' => today()->toDateString(),
                    'selected_emi_ids' => [$emi->id],
                    'selected_entry_ids' => [],
                ]);
                return;
            }
        }
        
        if ($emi->emi_type === 'Customer' && $emi->entity_id) {
            $customer = \App\Models\Customer::find($emi->entity_id);
            if ($customer) {
                app(\App\Services\CustomerPaymentService::class)->record([
                    'customer_id' => $customer->id,
                    'payment_type' => 'Advance',
                    'amount' => $totalAmount,
                    'cod_amount' => $cashAmount,
                    'bank_transfer_amount' => $bankAmount,
                    'payment_mode' => $paymentMode,
                    'date' => today()->toDateString(),
                ]);
                $emi->paid_amount += $totalAmount;
                $emi->status = ($emi->paid_amount >= $emi->amount) ? 'Paid' : 'Partial';
                $emi->save();
                return;
            }
        }
        
        // Otherwise (Bank Loan), we manually update the EMI and create an Expense/Income
        $emi->paid_amount += $totalAmount;
        if ($emi->paid_amount >= $emi->amount) {
            $emi->status = 'Paid';
        } else {
            $emi->status = 'Partial';
        }
        $emi->save();
        
        if ($emi->emi_type === 'Bank Loan') {
            // Bank Loan repayment is an expense
            if ($cashAmount > 0) {
                \App\Models\Expense::create([
                    'date' => today(),
                    'category' => 'Misc',
                    'description' => 'EMI Payment: ' . $emi->loan_name,
                    'amount' => $cashAmount,
                    'payment_method' => 'Cash'
                ]);
            }
            if ($bankAmount > 0) {
                \App\Models\Expense::create([
                    'date' => today(),
                    'category' => 'Misc',
                    'description' => 'EMI Payment: ' . $emi->loan_name,
                    'amount' => $bankAmount,
                    'payment_method' => 'Bank Transfer'
                ]);
            }
            app(\App\Services\CashBankLedgerService::class)->recalculateForDate(today());
        }
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $data = $request->validate([
            'category'    => 'required|in:Fuel,Salary,Transport,Utility,Misc,Purchase',
            'description' => 'required|string|max:500',
            'amount'      => 'required|numeric|min:0.01',
            'date'        => 'required|date',
            'payment_method' => 'nullable|in:Cash,Bank Transfer',
        ]);
        
        $oldDate = $expense->date;
        $data['payment_method'] = $data['payment_method'] ?? 'Cash';
        $expense->update($data);
        
        app(\App\Services\CashBankLedgerService::class)->recalculateForDate(\Carbon\Carbon::parse($oldDate));
        app(\App\Services\CashBankLedgerService::class)->recalculateForDate(\Carbon\Carbon::parse($expense->date));
        
        return back()->with('success', 'Expense updated successfully.');
    }
}
