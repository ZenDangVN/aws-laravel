<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function index(): Response
    {
        $vehicles = Vehicle::withCount('packages')
            ->with('shipments:id,vehicle_id,reference_number,status')
            ->orderBy('plate_number')
            ->get();

        return Inertia::render('logistics/vehicles/Index', [
            'vehicles' => $vehicles,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plate_number' => ['required', 'string', 'max:20', 'unique:vehicles'],
            'type' => ['required', 'in:truck,van,motorcycle'],
            'driver_name' => ['required', 'string', 'max:100'],
            'driver_phone' => ['nullable', 'string', 'max:20'],
        ]);

        Vehicle::create($validated);

        return redirect()->route('logistics.vehicles.index');
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $validated = $request->validate([
            'driver_name' => ['required', 'string', 'max:100'],
            'driver_phone' => ['nullable', 'string', 'max:20'],
            'status' => ['required', 'in:available,on_route,maintenance'],
        ]);

        $vehicle->update($validated);

        return redirect()->route('logistics.vehicles.index');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return redirect()->route('logistics.vehicles.index');
    }
}
