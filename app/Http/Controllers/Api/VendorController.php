<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Masters\StoreVendorRequest;
use App\Http\Resources\VendorResource;
use App\Models\Vendor;
use App\Services\VendorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends BaseApiController
{
    public function __construct(private VendorService $service) {}

    /**
     * Get a paginated list of vendors.
     */
    public function index(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $perPage = min((int)$request->input('per_page', 15), 100);

        $vendors = $this->service->search($search, $perPage);

        return $this->sendResponse([
            'vendors'    => VendorResource::collection($vendors),
            'pagination' => [
                'current_page' => $vendors->currentPage(),
                'last_page'    => $vendors->lastPage(),
                'per_page'     => $vendors->perPage(),
                'total'        => $vendors->total(),
            ]
        ], 'Vendors retrieved successfully');
    }

    /**
     * Store a new vendor.
     */
    public function store(StoreVendorRequest $request): JsonResponse
    {
        $vendor = $this->service->create($request->validated());
        return $this->sendResponse(new VendorResource($vendor), 'Vendor added successfully', 201);
    }

    /**
     * Display the specified vendor.
     */
    public function show(Vendor $vendor): JsonResponse
    {
        return $this->sendResponse(new VendorResource($vendor), 'Vendor details retrieved successfully');
    }

    /**
     * Update the specified vendor.
     */
    public function update(StoreVendorRequest $request, Vendor $vendor): JsonResponse
    {
        $updatedVendor = $this->service->update($vendor, $request->validated());
        return $this->sendResponse(new VendorResource($updatedVendor), 'Vendor updated successfully');
    }

    /**
     * Delete the specified vendor.
     */
    public function destroy(Vendor $vendor): JsonResponse
    {
        $this->service->delete($vendor);
        return $this->sendResponse([], 'Vendor deleted successfully');
    }

    /**
     * Get the purchase history of a vendor.
     */
    public function purchaseHistory(Vendor $vendor): JsonResponse
    {
        $purchases = $vendor->purchases()->with('items')->latest()->paginate(15);
        return $this->sendResponse([
            'vendor'    => new VendorResource($vendor),
            'purchases' => $purchases,
        ], 'Vendor purchase history retrieved successfully');
    }
}
