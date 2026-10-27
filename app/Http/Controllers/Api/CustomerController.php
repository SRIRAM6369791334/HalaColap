<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Masters\StoreCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CustomerController extends BaseApiController
{
    public function __construct(private CustomerService $service) {}

    /**
     * GET /api/v1/masters/customers
     * Paginated & searchable customer list.
     * Query params: ?search=, ?per_page=15, ?all=1
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->input('per_page', 15), 100);
        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id ?? 'global';

        // ?all=1 returns full list for dropdowns (cached per tenant)
        if ($request->boolean('all') || $request->input('all') === '1') {
            $customers = Cache::remember("tenant_{$tenantId}.masters.customers.all", now()->addMinutes(30), function () {
                return Customer::with('routeRelation')->orderBy('name')->get();
            });

            return $this->sendResponse(
                CustomerResource::collection($customers),
                'Customers retrieved successfully'
            );
        }

        $search = $request->input('search');

        // Use cache for default paginated list (no search, cached per tenant)
        if (empty($search) && $perPage === 15) {
            $customers = Cache::remember("tenant_{$tenantId}.masters.customers.paginated.default", now()->addMinutes(30), function () {
                return Customer::with('routeRelation')->orderBy('name')->paginate(15);
            });
        } else {
            $customers = Customer::with('routeRelation')
                ->search($search)
                ->orderBy('name')
                ->paginate($perPage);
        }

        // ✅ Fix 1: Standard REST meta+links pagination format
        return $this->sendResponse([
            'data' => CustomerResource::collection($customers),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page'    => $customers->lastPage(),
                'per_page'     => $customers->perPage(),
                'total'        => $customers->total(),
            ],
            'links' => [
                'first' => $customers->url(1),
                'last'  => $customers->url($customers->lastPage()),
                'prev'  => $customers->previousPageUrl(),
                'next'  => $customers->nextPageUrl(),
            ],
        ], 'Customers retrieved successfully');
    }

    /**
     * POST /api/v1/masters/customers
     * Create a new customer. Returns 201 Created.
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = $this->service->create($request->validated());
        return $this->sendResponse(new CustomerResource($customer), 'Customer created successfully', 201);
    }

    /**
     * GET /api/v1/masters/customers/{customer}
     * Customer detail with stats and top products.
     * ✅ Fix 2: Business logic moved to CustomerService::getDetails()
     */
    public function show(Customer $customer): JsonResponse
    {
        $details = $this->service->getDetails($customer);

        return $this->sendResponse(
            array_merge(['customer' => new CustomerResource($customer)], $details),
            'Customer details retrieved successfully'
        );
    }

    /**
     * PUT /api/v1/masters/customers/{customer}
     * Update customer record.
     */
    public function update(StoreCustomerRequest $request, Customer $customer): JsonResponse
    {
        $updated = $this->service->update($customer, $request->validated());
        return $this->sendResponse(new CustomerResource($updated), 'Customer updated successfully');
    }

    /**
     * DELETE /api/v1/masters/customers/{customer}
     * ✅ Fix 4: Returns 204 No Content — proper REST standard
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $this->service->delete($customer);
        return response()->json(null, 204);
    }

    /**
     * GET /api/v1/masters/customers/{customer}/billing-history
     * Paginated billing history (weekly + daily bills).
     * ✅ Fix 2: Logic moved to CustomerService::getBillingHistory()
     */
    public function billingHistory(Customer $customer): JsonResponse
    {
        $history = $this->service->getBillingHistory($customer);

        return $this->sendResponse(
            array_merge(['customer' => new CustomerResource($customer)], $history),
            'Billing history retrieved successfully'
        );
    }

    /**
     * GET /api/v1/masters/customers/{customer}/payment-history
     * Paginated payment history.
     * ✅ Fix 2: Logic moved to CustomerService::getPaymentHistory()
     */
    public function paymentHistory(Customer $customer): JsonResponse
    {
        $history = $this->service->getPaymentHistory($customer);

        return $this->sendResponse(
            array_merge(['customer' => new CustomerResource($customer)], $history),
            'Payment history retrieved successfully'
        );
    }
}
