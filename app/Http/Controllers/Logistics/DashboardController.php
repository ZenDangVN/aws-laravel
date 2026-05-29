<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\RfidScan;
use App\Models\Shipment;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $packagesByStatus = Package::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $recentScans = RfidScan::with([
            'package:id,rfid_tag,tracking_number,status',
            'warehouse:id,name,code',
            'vehicle:id,plate_number',
        ])
            ->orderByDesc('scanned_at')
            ->limit(20)
            ->get();

        $scansPerHour = RfidScan::query()
            ->where('scanned_at', '>=', now()->subHours(24))
            ->selectRaw("LPAD(EXTRACT(HOUR FROM scanned_at)::text, 2, '0') as hour, COUNT(*) as count")
            ->groupByRaw('EXTRACT(HOUR FROM scanned_at)')
            ->orderBy('hour')
            ->pluck('count', 'hour');

        return Inertia::render('logistics/Dashboard', [
            'stats' => [
                'total_packages' => Package::count(),
                'pending' => $packagesByStatus['pending'] ?? 0,
                'in_transit' => $packagesByStatus['in_transit'] ?? 0,
                'at_warehouse' => $packagesByStatus['at_warehouse'] ?? 0,
                'out_for_delivery' => $packagesByStatus['out_for_delivery'] ?? 0,
                'delivered' => $packagesByStatus['delivered'] ?? 0,
                'active_shipments' => Shipment::whereIn('status', ['loading', 'in_transit'])->count(),
                'warehouses' => Warehouse::count(),
                'vehicles_on_route' => Vehicle::where('status', 'on_route')->count(),
                'scans_today' => RfidScan::whereDate('scanned_at', today())->count(),
            ],
            'recent_scans' => $recentScans,
            'scans_per_hour' => $scansPerHour,
        ]);
    }
}
