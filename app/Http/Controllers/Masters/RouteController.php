<?php

namespace App\Http\Controllers\Masters;

use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\Vehicle;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class RouteController extends Controller
{
    public function index(): View
    {
        $routes = Route::with(['vehicle', 'driver'])->withCount(['customers', 'dealers'])->get();
        $vehicles = Vehicle::all();
        $drivers = Driver::all();
        return view('routes.index', compact('routes', 'vehicles', 'drivers'));
    }

    public function store(Request $request): RedirectResponse
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

        Route::create($validated);

        return redirect()->route('routes.index')->with('success', 'Route created successfully.');
    }

    public function update(Request $request, Route $route): RedirectResponse
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

        return redirect()->route('routes.index')->with('success', 'Route updated successfully.');
    }

    public function destroy(Route $route): RedirectResponse
    {
        $route->delete();
        return redirect()->route('routes.index')->with('success', 'Route deleted successfully.');
    }

    // Vehicle Management
    public function storeVehicle(Request $request): RedirectResponse
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

        Vehicle::create($validated);
        return back()->with('success', 'Vehicle added.');
    }

    // Driver Management
    public function storeDriver(Request $request): RedirectResponse
    {
        $request->validate([
            'driver_name'    => 'required|string',
            'phone'          => 'required|string',
            'license_number' => 'nullable|string',
        ]);

        Driver::create($request->all());
        return back()->with('success', 'Driver added.');
    }
}
