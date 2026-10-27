<?php

namespace App\Http\Controllers;

use App\Models\CapitalTransaction;
use App\Models\CashBankLedger;
use App\Services\CashBankLedgerService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CapitalTransactionController extends Controller
{
    public function __construct(
        private CashBankLedgerService $ledgerService
    ) {}

    public function index(Request $request): View
    {
        $typeFilter = $request->input('type');
        $dateFrom   = $request->input('date_from');
        $dateTo     = $request->input('date_to');
        $search     = $request->input('search');

        $query = CapitalTransaction::with('creator')
            ->when($typeFilter, fn($q) => $q->where('type', $typeFilter))
            ->when($dateFrom, fn($q) => $q->whereDate('date', '>=', $dateFrom))
            ->when($dateTo, fn($q) => $q->whereDate('date', '<=', $dateTo))
            ->when($search, function($q) use ($search) {
                $q->where('person_name', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            })
            ->latest('date')
            ->latest('id');

        $transactions = (clone $query)->paginate(15)->withQueryString();

        // Calculate Overall Metrics
        $totalInvested = (float) CapitalTransaction::where('type', 'Investment')->sum('amount');
        $totalTransferredToBusiness = (float) CapitalTransaction::whereIn('type', ['Transfer to Cash', 'Transfer to Bank'])->sum('amount');
        $totalTransferredFromBusiness = (float) CapitalTransaction::whereIn('type', ['Transfer from Cash', 'Transfer from Bank'])->sum('amount');
        $totalWithdrawn = (float) CapitalTransaction::where('type', 'Withdrawal')->sum('amount');
        $totalVendorAdvanceFunded = (float) CapitalTransaction::where('type', 'Vendor Advance Outflow')->sum('amount');

        $currentInvestmentBalance = round(
            $totalInvested + $totalTransferredFromBusiness - $totalTransferredToBusiness - $totalWithdrawn - $totalVendorAdvanceFunded,
            2
        );

        // Get latest Cash & Bank ledger balances for quick reference
        $todayLedger = $this->ledgerService->getOrCreateForDate(now());
        $currentCashBalance = (float) $todayLedger->closing_cash_balance;
        $currentBankBalance = (float) $todayLedger->closing_bank_balance;

        return view('investments.index', compact(
            'transactions', 'totalInvested', 'totalTransferredToBusiness', 'totalTransferredFromBusiness',
            'totalWithdrawn', 'totalVendorAdvanceFunded', 'currentInvestmentBalance',
            'currentCashBalance', 'currentBankBalance',
            'typeFilter', 'dateFrom', 'dateTo', 'search'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type'               => 'required|in:Investment,Transfer to Cash,Transfer to Bank,Transfer from Cash,Transfer from Bank,Withdrawal',
            'date'               => 'required|date',
            'cash_amount'        => 'nullable|numeric|min:0',
            'bank_amount'        => 'nullable|numeric|min:0',
            'amount'             => 'nullable|numeric|min:0',
            'payment_mode'       => 'nullable|string',
            'bank_transfer_type' => 'nullable|string',
            'person_name'        => 'nullable|string|max:150',
            'reference_number'   => 'nullable|string|max:100',
            'notes'              => 'nullable|string|max:500',
        ]);

        $cashAmount = (float) ($request->input('cash_amount', 0));
        $bankAmount = (float) ($request->input('bank_amount', 0));
        $singleAmount = (float) ($request->input('amount', 0));

        $totalAmount = $cashAmount + $bankAmount;
        if ($totalAmount <= 0 && $singleAmount > 0) {
            $mode = $request->input('payment_mode', 'Cash');
            if ($mode === 'Cash') {
                $cashAmount = $singleAmount;
            } else {
                $bankAmount = $singleAmount;
            }
            $totalAmount = $singleAmount;
        }

        if ($totalAmount <= 0) {
            return back()->with('error', 'Please enter a valid amount greater than 0.');
        }

        // Handle Cash Transaction if cash_amount > 0
        if ($cashAmount > 0) {
            $cashType = $validated['type'];
            if ($validated['type'] === 'Transfer to Bank') {
                $cashType = 'Transfer to Cash';
            } elseif ($validated['type'] === 'Transfer from Bank') {
                $cashType = 'Transfer from Cash';
            }

            CapitalTransaction::create([
                'type'               => $cashType,
                'date'               => $validated['date'],
                'amount'             => $cashAmount,
                'payment_mode'       => 'Cash',
                'bank_transfer_type' => null,
                'person_name'        => $validated['person_name'] ?? null,
                'reference_number'   => $validated['reference_number'] ?? null,
                'notes'              => $validated['notes'] ?? null,
                'created_by'         => auth()->id(),
            ]);
        }

        // Handle Bank Transaction if bank_amount > 0
        if ($bankAmount > 0) {
            $bankType = $validated['type'];
            if ($validated['type'] === 'Transfer to Cash') {
                $bankType = 'Transfer to Bank';
            } elseif ($validated['type'] === 'Transfer from Cash') {
                $bankType = 'Transfer from Bank';
            }

            CapitalTransaction::create([
                'type'               => $bankType,
                'date'               => $validated['date'],
                'amount'             => $bankAmount,
                'payment_mode'       => 'Bank Transfer',
                'bank_transfer_type' => $validated['bank_transfer_type'] ?? 'UPI',
                'person_name'        => $validated['person_name'] ?? null,
                'reference_number'   => $validated['reference_number'] ?? null,
                'notes'              => $validated['notes'] ?? null,
                'created_by'         => auth()->id(),
            ]);
        }

        // Recalculate Cash/Bank ledger for that date
        $this->ledgerService->recalculateForDate(Carbon::parse($validated['date']));

        return redirect()->route('billing.investments.index')->with('success', 'Capital transaction recorded successfully.');
    }

    public function destroy(CapitalTransaction $investment): RedirectResponse
    {
        $txDate = $investment->date;
        $investment->delete();

        $this->ledgerService->recalculateForDate(Carbon::parse($txDate));

        return redirect()->route('billing.investments.index')->with('success', 'Transaction deleted and ledger balances updated.');
    }
}
