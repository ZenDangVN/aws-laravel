<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PackageController extends Controller
{
    public function index(Request $request): Response
    {
        $packages = Package::with(['currentWarehouse:id,name', 'currentVehicle:id,plate_number', 'shipment:id,reference_number'])
            ->when($request->status, fn ($q) => $q->byStatus($request->status))
            ->when($request->search, fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('tracking_number', 'like', "%{$request->search}%")
                    ->orWhere('rfid_tag', 'like', "%{$request->search}%");
            }))
            ->orderByDesc('updated_at')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('logistics/packages/Index', [
            'packages' => $packages,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    public function show(Package $package): Response
    {
        $package->load([
            'shipment.originWarehouse:id,name',
            'shipment.destinationWarehouse:id,name',
            'currentWarehouse:id,name,code',
            'currentVehicle:id,plate_number,driver_name',
            'rfidScans' => fn ($q) => $q->with(['warehouse:id,name', 'vehicle:id,plate_number'])->orderByDesc('scanned_at'),
        ]);

        return Inertia::render('logistics/packages/Show', [
            'package' => $package,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rfid_tag' => ['required', 'string', 'unique:packages'],
            'tracking_number' => ['required', 'string', 'unique:packages'],
            'description' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'shipment_id' => ['nullable', 'exists:shipments,id'],
        ]);

        Package::create($validated);

        return redirect()->route('logistics.packages.index');
    }

    public function update(Request $request, Package $package): RedirectResponse
    {
        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:255'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'shipment_id' => ['nullable', 'exists:shipments,id'],
            'status' => ['required', 'in:pending,in_transit,at_warehouse,out_for_delivery,delivered'],
        ]);

        $package->update($validated);

        return redirect()->route('logistics.packages.show', $package);
    }

    public function destroy(Package $package): RedirectResponse
    {
        $package->delete();

        return redirect()->route('logistics.packages.index');
    }
}
