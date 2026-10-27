<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Emi;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExpenseService
{
    public function paginatedExpenses(int $perPage = 15): LengthAwarePaginator
    {
        return Expense::latest('date')->paginate($perPage);
    }

    public function allEmis(): \Illuminate\Database\Eloquent\Collection
    {
        return Emi::whereNotIn('emi_type', ['Customer', 'Dealer'])->orderBy('due_date')->get();
    }

    public function createExpense(array $data): Expense
    {
        $expense = Expense::create($data);
        app(CashBankLedgerService::class)->recalculateForDate(\Carbon\Carbon::parse($expense->date));
        return $expense;
    }

    public function deleteExpense(Expense $expense): void
    {
        $date = $expense->date;
        $expense->delete();
        app(CashBankLedgerService::class)->recalculateForDate($date);
    }

    public function totals(): array
    {
        return [
            'total_expenses' => Expense::whereMonth('date', now()->month)->sum('amount'),
            'total_emis'     => Emi::whereNotIn('emi_type', ['Customer', 'Dealer'])->sum('amount'),
        ];
    }

    public function allExpensesForExport(): \Illuminate\Database\Eloquent\Collection
    {
        return Expense::orderByDesc('date')->get();
    }
}
