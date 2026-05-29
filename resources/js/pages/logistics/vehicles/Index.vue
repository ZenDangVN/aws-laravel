<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import type { Vehicle, VehicleStatus } from '@/types/logistics';
import { logistics } from '@/lib/logistics';

defineProps<{ vehicles: Vehicle[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Logistics', href: logistics.dashboard() },
            { title: 'Phương tiện', href: logistics.vehicles.index() },
        ],
    },
});

const statusVariant: Record<VehicleStatus, 'default' | 'secondary' | 'destructive'> = {
    available: 'secondary',
    on_route: 'default',
    maintenance: 'destructive',
};

const statusLabel: Record<VehicleStatus, string> = {
    available: 'Sẵn sàng',
    on_route: 'Đang chạy',
    maintenance: 'Bảo trì',
};

const typeLabel: Record<string, string> = { truck: 'Xe tải', van: 'Xe van', motorcycle: 'Xe máy' };
</script>

<template>
    <Head title="Phương tiện" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Phương tiện</h1>
        </div>

        <Card>
            <CardContent class="p-0">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-muted-foreground">
                            <th class="px-4 py-3">Biển số</th>
                            <th class="px-4 py-3">Loại xe</th>
                            <th class="px-4 py-3">Tài xế</th>
                            <th class="px-4 py-3">Điện thoại</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-4 py-3">Kiện hàng</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="vehicle in vehicles" :key="vehicle.id" class="border-b last:border-0 hover:bg-muted/50">
                            <td class="px-4 py-3 font-mono font-medium">{{ vehicle.plate_number }}</td>
                            <td class="px-4 py-3">{{ typeLabel[vehicle.type] }}</td>
                            <td class="px-4 py-3">{{ vehicle.driver_name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ vehicle.driver_phone ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <Badge :variant="statusVariant[vehicle.status]">{{ statusLabel[vehicle.status] }}</Badge>
                            </td>
                            <td class="px-4 py-3">{{ vehicle.packages_count ?? 0 }}</td>
                        </tr>
                        <tr v-if="vehicles.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">Chưa có phương tiện nào</td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>
