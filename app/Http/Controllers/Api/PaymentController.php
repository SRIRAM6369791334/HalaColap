<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Payments\StoreCustomerPaymentRequest;
use App\Http\Requests\Payments\StoreDealerPaymentRequest;
use App\Models\Dealer;
use App\Services\CustomerPaymentService;
use App\Services\DealerPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends BaseApiController
{
    public function __construct(
        private CustomerPaymentService $customerService,
        private DealerPaymentService $dealerService
    ) {}

    /**
     * Get paginated customer payments.
     */
    public function indexCustomers(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $perPage = min((int)$request->input('per_page', 15), 100);

        $payments = $this->customerService->paginated($search, $perPage);

        return $this->sendResponse([
            'payments'   => $payments->items(),
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
                'per_page'     => $payments->perPage(),
                'total'        => $payments->total(),
            ]
        ], 'Customer payments retrieved successfully');
    }

    /**
     * Record a customer payment.
     */
    public function storeCustomerPayment(StoreCustomerPaymentRequest $request): JsonResponse
    {
        try {
            $payment = $this->customerService->record($request->validated());
            return $this->sendResponse($payment, 'Customer payment recorded successfully', 201);
        } catch (\Exception $e) {
            return $this->sendError('Could not record payment', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Get paginated dealer payments.
     */
    public function indexDealers(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $perPage = min((int)$request->input('per_page', 15), 100);

        $payments = $this->dealerService->paginated($search, $perPage);

        return $this->sendResponse([
            'payments'   => $payments->items(),
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
                'per_page'     => $payments->perPage(),
                'total'        => $payments->total(),
            ]
        ], 'Dealer payments retrieved successfully');
    }

    /**
     * Record a dealer payment.
     */
    public function storeDealerPayment(StoreDealerPaymentRequest $request): JsonResponse
    {
        try {
            $payment = $this->dealerService->record($request->validated());
            return $this->sendResponse($payment, 'Dealer payment recorded successfully', 201);
        } catch (\Exception $e) {
            return $this->sendError('Could not record payment', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Get ledger payments for a specific dealer.
     */
    public function dealerLedger(Dealer $dealer): JsonResponse
    {
        $payments = $dealer->payments()->latest('date')->paginate(20);

        return $this->sendResponse([
            'dealer'     => $dealer,
            'payments'   => $payments->items(),
            'pagination' => [
                'current_page' => $payments->currentPage(),
                'last_page'    => $payments->lastPage(),
                'per_page'     => $payments->perPage(),
                'total'        => $payments->total(),
            ]
        ], 'Dealer ledger retrieved successfully');
    }
}
