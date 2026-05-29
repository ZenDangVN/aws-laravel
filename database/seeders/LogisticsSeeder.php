<?php

namespace Database\Seeders;

use App\Models\Package;
use App\Models\RfidScan;
use App\Models\Shipment;
use App\Models\Vehicle;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class LogisticsSeeder extends Seeder
{
    public function run(): void
    {
        // 5 kho hàng cố định ở các tỉnh thành VN
        $warehouses = collect([
            ['name' => 'Kho Hà Nội', 'code' => 'HN-01', 'address' => '123 Giải Phóng', 'city' => 'Hà Nội', 'latitude' => 21.0245, 'longitude' => 105.8412],
            ['name' => 'Kho TP.HCM', 'code' => 'HCM-01', 'address' => '456 Nguyễn Văn Linh', 'city' => 'TP.HCM', 'latitude' => 10.7769, 'longitude' => 106.7009],
            ['name' => 'Kho Đà Nẵng', 'code' => 'DN-01', 'address' => '789 Điện Biên Phủ', 'city' => 'Đà Nẵng', 'latitude' => 16.0544, 'longitude' => 108.2022],
            ['name' => 'Kho Cần Thơ', 'code' => 'CT-01', 'address' => '321 Trần Hưng Đạo', 'city' => 'Cần Thơ', 'latitude' => 10.0341, 'longitude' => 105.7880],
            ['name' => 'Kho Hải Phòng', 'code' => 'HP-01', 'address' => '654 Lạch Tray', 'city' => 'Hải Phòng', 'latitude' => 20.8449, 'longitude' => 106.6881],
        ])->map(fn ($data) => Warehouse::create($data));

        // 8 xe vận chuyển
        $vehicles = collect([
            ['plate_number' => '29C-12345', 'type' => 'truck', 'driver_name' => 'Nguyễn Văn An', 'driver_phone' => '0901234567', 'status' => 'on_route'],
            ['plate_number' => '51G-54321', 'type' => 'truck', 'driver_name' => 'Trần Thị Bình', 'driver_phone' => '0912345678', 'status' => 'available'],
            ['plate_number' => '43A-11111', 'type' => 'van', 'driver_name' => 'Lê Văn Cường', 'driver_phone' => '0923456789', 'status' => 'on_route'],
            ['plate_number' => '65B-22222', 'type' => 'van', 'driver_name' => 'Phạm Thị Dung', 'driver_phone' => '0934567890', 'status' => 'available'],
            ['plate_number' => '15C-33333', 'type' => 'motorcycle', 'driver_name' => 'Hoàng Văn Em', 'driver_phone' => '0945678901', 'status' => 'on_route'],
            ['plate_number' => '29D-44444', 'type' => 'truck', 'driver_name' => 'Vũ Thị Phương', 'driver_phone' => '0956789012', 'status' => 'maintenance'],
            ['plate_number' => '51H-55555', 'type' => 'van', 'driver_name' => 'Đỗ Văn Giang', 'driver_phone' => '0967890123', 'status' => 'available'],
            ['plate_number' => '43C-66666', 'type' => 'motorcycle', 'driver_name' => 'Bùi Thị Hoa', 'driver_phone' => '0978901234', 'status' => 'on_route'],
        ])->map(fn ($data) => Vehicle::create($data));

        // 6 lô hàng với các trạng thái khác nhau
        $shipmentData = [
            ['origin' => 0, 'dest' => 1, 'vehicle' => 0, 'status' => 'in_transit', 'ref' => 'SHP-20260001', 'departed' => now()->subHours(5)],
            ['origin' => 1, 'dest' => 2, 'vehicle' => 2, 'status' => 'in_transit', 'ref' => 'SHP-20260002', 'departed' => now()->subHours(3)],
            ['origin' => 0, 'dest' => 3, 'vehicle' => null, 'status' => 'loading', 'ref' => 'SHP-20260003', 'departed' => null],
            ['origin' => 2, 'dest' => 0, 'vehicle' => null, 'status' => 'pending', 'ref' => 'SHP-20260004', 'departed' => null],
            ['origin' => 1, 'dest' => 4, 'vehicle' => 4, 'status' => 'arrived', 'ref' => 'SHP-20260005', 'departed' => now()->subDays(1), 'arrived' => now()->subHours(2)],
            ['origin' => 3, 'dest' => 1, 'vehicle' => null, 'status' => 'completed', 'ref' => 'SHP-20260006', 'departed' => now()->subDays(2), 'arrived' => now()->subDays(1)],
        ];

        $shipments = collect($shipmentData)->map(fn ($d) => Shipment::create([
            'reference_number' => $d['ref'],
            'origin_warehouse_id' => $warehouses[$d['origin']]->id,
            'destination_warehouse_id' => $warehouses[$d['dest']]->id,
            'vehicle_id' => $d['vehicle'] !== null ? $vehicles[$d['vehicle']]->id : null,
            'status' => $d['status'],
            'scheduled_at' => now()->subHours(6),
            'departed_at' => $d['departed'] ?? null,
            'arrived_at' => $d['arrived'] ?? null,
        ]));

        // 40 kiện hàng phân bổ vào các lô và trạng thái khác nhau
        $packageStatuses = ['pending', 'pending', 'in_transit', 'in_transit', 'in_transit', 'at_warehouse', 'at_warehouse', 'out_for_delivery', 'delivered', 'delivered'];

        for ($i = 1; $i <= 40; $i++) {
            $statusIndex = ($i - 1) % count($packageStatuses);
            $status = $packageStatuses[$statusIndex];
            $shipment = $shipments[($i - 1) % count($shipments)];
            $warehouse = $status === 'at_warehouse' ? $warehouses[($i) % count($warehouses)] : null;
            $vehicle = in_array($status, ['in_transit', 'out_for_delivery']) ? $vehicles[($i) % 5] : null;

            Package::create([
                'rfid_tag' => sprintf('RFID-%08d', $i),
                'tracking_number' => sprintf('TRK-VN%010d', $i),
                'description' => fake()->randomElement(['Điện thoại', 'Quần áo', 'Thiết bị điện tử', 'Thực phẩm khô', 'Văn phòng phẩm', 'Đồ gia dụng']),
                'weight' => round(rand(1, 200) / 10, 1),
                'shipment_id' => $shipment->id,
                'current_warehouse_id' => $warehouse?->id,
                'current_vehicle_id' => $vehicle?->id,
                'status' => $status,
            ]);
        }

        // Tạo lịch sử quét RFID cho 24 giờ qua
        $packages = Package::all();
        $scanCount = 0;

        foreach ($packages as $pkg) {
            $scansForPackage = rand(1, 5);
            for ($s = 0; $s < $scansForPackage; $s++) {
                $hoursAgo = rand(0, 23);
                $locationType = fake()->randomElement(['warehouse', 'vehicle', 'checkpoint']);
                $warehouse = $locationType === 'warehouse' ? $warehouses->random() : null;
                $vehicle = $locationType === 'vehicle' ? $vehicles->random() : null;

                RfidScan::create([
                    'package_id' => $pkg->id,
                    'scanner_id' => sprintf('GATE-%02d', rand(1, 10)),
                    'warehouse_id' => $warehouse?->id,
                    'vehicle_id' => $vehicle?->id,
                    'location_type' => $locationType,
                    'scanned_at' => now()->subHours($hoursAgo)->subMinutes(rand(0, 59)),
                ]);

                $scanCount++;
            }
        }

        $this->command->info("✓ Đã tạo: {$warehouses->count()} kho | {$vehicles->count()} xe | {$shipments->count()} lô | {$packages->count()} kiện | {$scanCount} lượt quét");
    }
}
