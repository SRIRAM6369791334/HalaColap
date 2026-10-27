<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Masters\StoreDealerRequest;
use App\Http\Resources\DealerResource;
use App\Models\Dealer;
use App\Services\DealerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DealerController extends BaseApiController
{
    public function __construct(private DealerService $service) {}

    /**
     * Get a paginated list of dealers.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $perPage = min((int)$request->input('per_page', 15), 100);

        $dealers = Dealer::with('routeRelation')
            ->search($search)
            ->orderBy('firm_name')
            ->paginate($perPage);

        return $this->sendResponse([
            'dealers'    => DealerResource::collection($dealers),
            'pagination' => [
                'current_page' => $dealers->currentPage(),
                'last_page'    => $dealers->lastPage(),
                'per_page'     => $dealers->perPage(),
                'total'        => $dealers->total(),
            ]
        ], 'Dealers retrieved successfully');
    }

    /**
     * Store a new dealer.
     */
    public function store(StoreDealerRequest $request): JsonResponse
    {
        $dealer = $this->service->create($request->validated());
        return $this->sendResponse(new DealerResource($dealer), 'Dealer added successfully', 201);
    }

    /**
     * Display the specified dealer details.
     */
    public function show(Dealer $dealer): JsonResponse
    {
        return $this->sendResponse(new DealerResource($dealer), 'Dealer details retrieved successfully');
    }

    /**
     * Update the specified dealer.
     */
    public function update(StoreDealerRequest $request, Dealer $dealer): JsonResponse
    {
        $updatedDealer = $this->service->update($dealer, $request->validated());
        return $this->sendResponse(new DealerResource($updatedDealer), 'Dealer updated successfully');
    }

    /**
     * Delete the specified dealer.
     */
    public function destroy(Dealer $dealer): JsonResponse
    {
        $this->service->delete($dealer);
        return $this->sendResponse([], 'Dealer deleted successfully');
    }

    /**
     * Get the purchase history of a dealer.
     */
    public function purchaseHistory(Dealer $dealer): JsonResponse
    {
        $purchases = $dealer->purchases()->with('items')->latest()->paginate(15);
        return $this->sendResponse([
            'dealer'    => new DealerResource($dealer),
            'purchases' => $purchases,
        ], 'Dealer purchase history retrieved successfully');
    }
}
