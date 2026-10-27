<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Expenses\StoreExpenseRequest;
use App\Models\Expense;
use App\Models\Emi;
use App\Services\ExpenseService;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends BaseApiController
{
    public function __construct(private ExpenseService $service) {}

    /**
     * Get paginated list of expenses along with EMIs and totals.
     */
    public function index(): JsonResponse
    {
        $expenses = $this->service->paginatedExpenses(15);
        $emis = $this->service->allEmis();
        $totals = $this->service->totals();

        return $this->sendResponse([
            'expenses' => $expenses->items(),
            'emis'     => $emis,
            'totals'   => $totals,
            'pagination' => [
                'current_page' => $expenses->currentPage(),
                'per_page'     => $expenses->perPage(),
                'total'        => $expenses->total(),
            ]
        ], 'Expenses and liabilities summary retrieved successfully');
    }

    /**
     * Store a new expense.
     */
    public function store(StoreExpenseRequest $request): JsonResponse
    {
        $expense = $this->service->createExpense($request->validated());

        // Audit Logging
        ActivityLogger::log("Created Expense of category: {$expense->category}, Amount: {$expense->amount}", 'Expenses', $expense->id);

        return $this->sendResponse($expense, 'Expense recorded successfully', 201);
    }

    /**
     * Delete an expense.
     */
    public function destroy(Expense $expense): JsonResponse
    {
        $id = $expense->id;
        $category = $expense->category;
        $amount = $expense->amount;

        $this->service->deleteExpense($expense);

        // Audit Logging
        ActivityLogger::log("Deleted Expense of category: {$category}, Amount: {$amount}", 'Expenses', $id);

        return $this->sendResponse([], 'Expense deleted successfully');
    }

    /**
     * Get expenses grouped by category.
     */
    public function categories(): JsonResponse
    {
        $categories = Expense::select('category', DB::raw('count(*) as count'), DB::raw('sum(amount) as total'))
            ->groupBy('category')
            ->get();

        return $this->sendResponse($categories, 'Expenses by category retrieved successfully');
    }

    /**
     * Get paginated EMIs list.
     */
    public function emisIndex(): JsonResponse
    {
        $emis = Emi::orderBy('due_date')->paginate(15);

        return $this->sendPaginatedResponse($emis, 'EMI lists retrieved successfully');
    }

    /**
     * Store a new EMI schedules.
     */
    public function storeEmi(Request $request): JsonResponse
    {
        $data = $request->validate([
            'loan_name' => 'required|string',
            'amount'    => 'required|numeric|min:0.01',
            'due_date'  => 'required|date',
            'status'    => 'required|in:Upcoming,Paid,Overdue',
        ]);

        $emi = Emi::create($data);

        // Audit Logging
        ActivityLogger::log("Recorded EMI: {$emi->loan_name}, Amount: {$emi->amount}", 'Expenses', $emi->id);

        return $this->sendResponse($emi, 'EMI recorded successfully', 201);
    }

    /**
     * Delete an EMI record.
     */
    public function destroyEmi(Emi $emi): JsonResponse
    {
        $id = $emi->id;
        $loanName = $emi->loan_name;
        $amount = $emi->amount;

        $emi->delete();

        // Audit Logging
        ActivityLogger::log("Deleted EMI record: {$loanName}, Amount: {$amount}", 'Expenses', $id);

        return $this->sendResponse([], 'EMI record deleted successfully');
    }

    /**
     * Get upcoming EMIs and overdue EMIs alerts.
     */
    public function emisAlerts(): JsonResponse
    {
        // EMIs due in the NEXT 7 days
        $upcoming = Emi::where('status', 'Upcoming')
            ->whereDate('due_date', '<=', now()->addDays(7))
            ->orderBy('due_date')
            ->get();

        // Overdue EMIs
        $overdue = Emi::where('status', 'Upcoming')
            ->whereDate('due_date', '<', today())
            ->get();

        return $this->sendResponse([
            'upcoming' => $upcoming,
            'overdue'  => $overdue,
        ], 'Liabilities and loan alerts retrieved successfully');
    }
}
