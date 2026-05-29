<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShipmentController extends Controller
{
    public function index(Request $request): Response
    {
        $shipments = Shipment::with([
            'originWarehouse:id,name,code',
            'destinationWarehouse:id,name,code',
            'vehicle:id,plate_number',
        ])
            ->withCount('packages')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('logistics/shipments/Index', [
            'shipments' => $shipments,
            'filters' => $request->only(['status']),
        ]);
    }

    public function show(Shipment $shipment): Response
    {
        $shipment->load([
            'originWarehouse:id,name,code,city',
            'destinationWarehouse:id,name,code,city',
            'vehicle:id,plate_number,driver_name,driver_phone',
            'packages:id,shipment_id,rfid_tag,tracking_number,description,weight,status',
        ]);

        return Inertia::render('logistics/shipments/Show', [
            'shipment' => $shipment,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reference_number' => ['required', 'string', 'unique:shipments'],
            'origin_warehouse_id' => ['required', 'exists:warehouses,id'],
            'destination_warehouse_id' => ['required', 'exists:warehouses,id', 'different:origin_warehouse_id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        Shipment::create($validated);

        return redirect()->route('logistics.shipments.index');
    }

    public function update(Request $request, Shipment $shipment): RedirectResponse
    {
        $validated = $request->validate([
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'status' => ['required', 'in:pending,loading,in_transit,arrived,completed'],
            'scheduled_at' => ['nullable', 'date'],
            'departed_at' => ['nullable', 'date'],
            'arrived_at' => ['nullable', 'date'],
        ]);

        $shipment->update($validated);

        return redirect()->route('logistics.shipments.show', $shipment);
    }

    public function destroy(Shipment $shipment): RedirectResponse
    {
        $shipment->delete();

        return redirect()->route('logistics.shipments.index');
    }
}
