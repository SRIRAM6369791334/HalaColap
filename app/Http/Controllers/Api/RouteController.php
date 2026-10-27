<?php

namespace App\Http\Controllers\Api;

use App\Models\Route;
use App\Models\Vehicle;
use App\Models\Driver;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RouteController extends BaseApiController
{
    /**
     * Display a listing of routes, vehicles, and drivers.
     */
    public function index(): JsonResponse
    {
        $routes = Route::with(['vehicle', 'driver'])->withCount(['customers', 'dealers'])->get();
        $vehicles = Vehicle::all();
        $drivers = Driver::all();

        return $this->sendResponse([
            'routes'   => $routes,
            'vehicles' => $vehicles,
            'drivers'  => $drivers,
        ], 'Route logistics master data retrieved successfully');
    }

    /**
     * Store a newly created route in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id;

        $validated = $request->validate([
            'route_name' => 'required|string|max:255',
            'zone'       => 'nullable|string|max:255',
            'vehicle_id' => [
                'nullable',
                Rule::exists('vehicles', 'id')->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'driver_id'  => [
                'nullable',
                Rule::exists('drivers', 'id')->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
        ]);

        $route = Route::create($validated);

        ActivityLogger::log("Created route: {$route->route_name}", 'Logistics', $route->id);

        return $this->sendResponse($route, 'Route created successfully', 201);
    }

    /**
     * Update the specified route in storage.
     */
    public function update(Request $request, Route $route): JsonResponse
    {
        $tenantId = $route->tenant_id ?? app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id;

        $validated = $request->validate([
            'route_name' => 'required|string|max:255',
            'zone'       => 'nullable|string|max:255',
            'vehicle_id' => [
                'nullable',
                Rule::exists('vehicles', 'id')->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'driver_id'  => [
                'nullable',
                Rule::exists('drivers', 'id')->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
        ]);

        $route->update($validated);

        ActivityLogger::log("Updated route: {$route->route_name}", 'Logistics', $route->id);

        return $this->sendResponse($route, 'Route updated successfully');
    }

    /**
     * Remove the specified route from storage.
     */
    public function destroy(Route $route): JsonResponse
    {
        $id = $route->id;
        $name = $route->route_name;

        $route->delete();

        ActivityLogger::log("Deleted route: {$name}", 'Logistics', $id);

        return $this->sendResponse([], 'Route deleted successfully');
    }

    /**
     * Store a newly created vehicle.
     */
    public function storeVehicle(Request $request): JsonResponse
    {
        $tenantId = app(\App\Services\Tenancy\TenantContext::class)->getTenantId() ?? auth()->user()?->current_tenant_id;

        $validated = $request->validate([
            'vehicle_number' => [
                'required',
                'string',
                Rule::unique('vehicles', 'vehicle_number')->where(fn($q) => $tenantId ? $q->where('tenant_id', $tenantId) : $q),
            ],
            'vehicle_type'   => 'required|string',
            'capacity'       => 'nullable|integer',
        ]);

        $vehicle = Vehicle::create($validated);

        ActivityLogger::log("Added vehicle: {$vehicle->vehicle_number}", 'Logistics', $vehicle->id);

        return $this->sendResponse($vehicle, 'Vehicle added successfully', 201);
    }

    /**
     * Store a newly created driver.
     */
    public function storeDriver(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'driver_name'    => 'required|string',
            'phone'          => 'required|string',
            'license_number' => 'nullable|string',
        ]);

        $driver = Driver::create($validated);

        ActivityLogger::log("Added driver: {$driver->driver_name}", 'Logistics', $driver->id);

        return $this->sendResponse($driver, 'Driver added successfully', 201);
    }
}
