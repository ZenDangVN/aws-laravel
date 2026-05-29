import DashboardController from '@/actions/App/Http/Controllers/Logistics/DashboardController';
import PackageController from '@/actions/App/Http/Controllers/Logistics/PackageController';
import ShipmentController from '@/actions/App/Http/Controllers/Logistics/ShipmentController';
import WarehouseController from '@/actions/App/Http/Controllers/Logistics/WarehouseController';
import VehicleController from '@/actions/App/Http/Controllers/Logistics/VehicleController';

export const logistics = {
    dashboard: DashboardController.index,
    packages: PackageController,
    shipments: ShipmentController,
    warehouses: WarehouseController,
    vehicles: VehicleController,
};
