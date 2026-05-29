<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\RfidScan;
use App\Models\Warehouse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WarehouseController extends Controller
{
    public function index(): Response
    {
        $warehouses = Warehouse::withCount(['packages', 'rfidScans'])
            ->orderBy('name')
            ->get()
            ->map(function (Warehouse $warehouse) {
                $warehouse->scans_today = RfidScan::where('warehouse_id', $warehouse->id)
                    ->whereDate('scanned_at', today())
                    ->count();

                return $warehouse;
            });

        return Inertia::render('logistics/warehouses/Index', [
            'warehouses' => $warehouses,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:20', 'unique:warehouses'],
            'address' => ['required', 'string'],
            'city' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        Warehouse::create($validated);

        return redirect()->route('logistics.warehouses.index');
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string'],
            'city' => ['required', 'string'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $warehouse->update($validated);

        return redirect()->route('logistics.warehouses.index');
    }

    public function destroy(Warehouse $warehouse): RedirectResponse
    {
        $warehouse->delete();

        return redirect()->route('logistics.warehouses.index');
    }
}
