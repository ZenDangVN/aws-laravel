<?php

namespace App\Http\Controllers\Api;

use App\Events\PackageStatusUpdated;
use App\Events\RfidScanReceived;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\RfidScan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RfidScanController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'rfid_tag' => ['required', 'string'],
            'scanner_id' => ['required', 'string'],
            'scanned_at' => ['nullable', 'date'],
            'location_type' => ['required', 'in:warehouse,vehicle,checkpoint'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
        ]);

        $package = Package::where('rfid_tag', $validated['rfid_tag'])->firstOrFail();

        $scan = RfidScan::create([
            'package_id' => $package->id,
            'scanner_id' => $validated['scanner_id'],
            'location_type' => $validated['location_type'],
            'warehouse_id' => $validated['warehouse_id'] ?? null,
            'vehicle_id' => $validated['vehicle_id'] ?? null,
            'scanned_at' => $validated['scanned_at'] ?? now(),
        ]);

        $previousStatus = $package->status;

        $package->update([
            'status' => $this->resolveStatus($validated['location_type']),
            'current_warehouse_id' => $validated['warehouse_id'] ?? null,
            'current_vehicle_id' => $validated['vehicle_id'] ?? null,
        ]);

        $scan->load(['package', 'warehouse', 'vehicle']);

        broadcast(new RfidScanReceived($scan));

        if ($previousStatus !== $package->status) {
            broadcast(new PackageStatusUpdated($package->fresh(['currentWarehouse', 'currentVehicle'])));
        }

        return response()->json(['scan_id' => $scan->id, 'status' => $package->status], 201);
    }

    private function resolveStatus(string $locationType): string
    {
        return match ($locationType) {
            'warehouse' => 'at_warehouse',
            'vehicle' => 'in_transit',
            default => 'in_transit',
        };
    }
}
