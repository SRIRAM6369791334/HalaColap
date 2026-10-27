<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Purchases\StorePurchaseRequest;
use App\Models\Purchase;
use App\Services\PurchaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends BaseApiController
{
    public function __construct(private PurchaseService $service) {}

    /**
     * Get a paginated list of purchases.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $perPage = min((int)$request->input('per_page', 15), 100);

        $purchases = $this->service->paginated($search, $perPage);

        return $this->sendResponse([
            'purchases'  => $purchases->items(),
            'pagination' => [
                'current_page' => $purchases->currentPage(),
                'last_page'    => $purchases->lastPage(),
                'per_page'     => $purchases->perPage(),
                'total'        => $purchases->total(),
            ]
        ], 'Purchases retrieved successfully');
    }

    /**
     * Store a new purchase entry.
     */
    public function store(StorePurchaseRequest $request): JsonResponse
    {
        try {
            $purchase = $this->service->create($request->validated());
            return $this->sendResponse($purchase->load(['vendor', 'items']), 'Purchase recorded successfully', 201);
        } catch (\Exception $e) {
            return $this->sendError('Could not record purchase', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Display purchase details.
     */
    public function show($id): JsonResponse
    {
        try {
            $purchase = $this->service->find($id)->load(['vendor', 'items']);
            return $this->sendResponse($purchase, 'Purchase details retrieved successfully');
        } catch (\Exception $e) {
            return $this->sendError('Purchase not found', ['exception' => $e->getMessage()], 404);
        }
    }

    /**
     * Update a purchase entry.
     */
    public function update(StorePurchaseRequest $request, $id): JsonResponse
    {
        try {
            $purchase = $this->service->find($id);
            $updatedPurchase = $this->service->update($purchase, $request->validated());
            return $this->sendResponse($updatedPurchase->load(['vendor', 'items']), 'Purchase updated successfully');
        } catch (\Exception $e) {
            return $this->sendError('Could not update purchase', ['exception' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete a purchase entry.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $purchase = $this->service->find($id);
            $this->service->delete($purchase);
            return $this->sendResponse([], 'Purchase deleted successfully');
        } catch (\Exception $e) {
            return $this->sendError('Could not delete purchase', ['exception' => $e->getMessage()], 500);
        }
    }
}
