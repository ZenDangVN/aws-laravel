export type PackageStatus = 'pending' | 'in_transit' | 'at_warehouse' | 'out_for_delivery' | 'delivered';
export type VehicleStatus = 'available' | 'on_route' | 'maintenance';
export type VehicleType = 'truck' | 'van' | 'motorcycle';
export type ShipmentStatus = 'pending' | 'loading' | 'in_transit' | 'arrived' | 'completed';
export type LocationType = 'warehouse' | 'vehicle' | 'checkpoint';

export type Warehouse = {
    id: number;
    name: string;
    code: string;
    address: string;
    city: string;
    latitude: number | null;
    longitude: number | null;
    packages_count?: number;
    rfid_scans_count?: number;
    scans_today?: number;
    created_at: string;
    updated_at: string;
};

export type Vehicle = {
    id: number;
    plate_number: string;
    type: VehicleType;
    driver_name: string;
    driver_phone: string | null;
    status: VehicleStatus;
    packages_count?: number;
    shipments?: Shipment[];
    created_at: string;
    updated_at: string;
};

export type Shipment = {
    id: number;
    reference_number: string;
    origin_warehouse_id: number;
    destination_warehouse_id: number;
    vehicle_id: number | null;
    status: ShipmentStatus;
    scheduled_at: string | null;
    departed_at: string | null;
    arrived_at: string | null;
    origin_warehouse?: Warehouse;
    destination_warehouse?: Warehouse;
    vehicle?: Vehicle;
    packages?: Package[];
    packages_count?: number;
    created_at: string;
    updated_at: string;
};

export type Package = {
    id: number;
    rfid_tag: string;
    tracking_number: string;
    description: string | null;
    weight: number | null;
    shipment_id: number | null;
    current_warehouse_id: number | null;
    current_vehicle_id: number | null;
    status: PackageStatus;
    shipment?: Shipment;
    current_warehouse?: Warehouse;
    current_vehicle?: Vehicle;
    rfid_scans?: RfidScan[];
    created_at: string;
    updated_at: string;
};

export type RfidScan = {
    id: number;
    package_id: number;
    scanner_id: string;
    warehouse_id: number | null;
    vehicle_id: number | null;
    location_type: LocationType;
    scanned_at: string;
    package?: Package;
    warehouse?: Warehouse;
    vehicle?: Vehicle;
    created_at: string;
    updated_at: string;
};

export type LogisticsStats = {
    total_packages: number;
    pending: number;
    in_transit: number;
    at_warehouse: number;
    out_for_delivery: number;
    delivered: number;
    active_shipments: number;
    warehouses: number;
    vehicles_on_route: number;
    scans_today: number;
};

export type RfidScanEvent = {
    id: number;
    rfid_tag: string;
    tracking_number: string;
    package_status: PackageStatus;
    scanner_id: string;
    location_type: LocationType;
    warehouse: { id: number; name: string; code: string } | null;
    vehicle: { id: number; plate_number: string } | null;
    scanned_at: string;
};
