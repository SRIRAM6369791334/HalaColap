<?php

namespace App\Http\Controllers\Api;

use App\Models\Customer;
use App\Models\DailyBill;
use App\Models\Item;
use App\Services\DailyBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DailyBillingController extends BaseApiController
{
    public function __construct(
        private DailyBillingService $billingService
    ) {}
    /**
     * Get a paginated list of daily bills.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $perPage = min((int)$request->input('per_page', 15), 100);

        $bills = DailyBill::with(['customer', 'items'])
            ->search($search)
            ->latest()
            ->paginate($perPage);

        return $this->sendResponse([
            'bills'      => $bills->items(),
            'pagination' => [
                'current_page' => $bills->currentPage(),
                'last_page'    => $bills->lastPage(),
                'per_page'     => $bills->perPage(),
                'total'        => $bills->total(),
            ]
        ], 'Daily bills retrieved successfully');
    }

    /**
     * Store a new daily bill.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id;

        $validator = Validator::make($request->all(), [
            'customer_id'    => [
                'required',
                \Illuminate\Validation\Rule::exists('customers', 'id')->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'date'           => 'required|date|before_or_equal:today',
            'status'         => 'required|in:Generated,Pending,Paid',
            'payment_mode'   => 'sometimes|required|in:Cash,UPI,NEFT,Cheque(Bank Transfer),Pay later(EMI)',
            'gst_percentage' => 'required|numeric|min:0|max:28',
            'items'          => 'required|array|min:1',
            'items.*.name'   => 'required|string|max:255',
            'items.*.qty'    => 'required|numeric|min:0.01',
            'items.*.rate'   => 'required|numeric|min:0.01',
            'items.*.unit'   => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation error', $validator->errors()->toArray(), 422);
        }

        try {
            $bill = $this->billingService->create($validator->validated());
            $bill->load(['customer', 'items']);
            return $this->sendResponse($bill, 'Daily bill created successfully', 201);
        } catch (\Exception $e) {
            return $this->sendError('Could not create bill', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Get specific daily bill details.
     */
    public function show(DailyBill $dailyBill): JsonResponse
    {
        $dailyBill->load(['customer', 'items']);
        return $this->sendResponse($dailyBill, 'Daily bill retrieved successfully');
    }

    /**
     * Delete a daily bill.
     */
    public function destroy(DailyBill $dailyBill): JsonResponse
    {
        try {
            $this->billingService->delete($dailyBill);
            return $this->sendResponse([], 'Daily bill and associated stock records deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError('Could not delete bill', ['exception' => $e->getMessage()], 500);
        }
    }
}
