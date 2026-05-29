<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import type { Vehicle, VehicleStatus } from '@/types/logistics';
import { logistics } from '@/lib/logistics';

const { t } = useI18n();

defineProps<{ vehicles: Vehicle[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Logistics', href: logistics.dashboard() },
            { title: 'Vehicles', href: logistics.vehicles.index() },
        ],
    },
});

const statusVariant: Record<VehicleStatus, 'default' | 'secondary' | 'destructive'> = {
    available: 'secondary',
    on_route: 'default',
    maintenance: 'destructive',
};
</script>

<template>
    <Head :title="t('logistics.vehicles.title')" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">{{ t('logistics.vehicles.title') }}</h1>
        </div>

        <Card>
            <CardContent class="p-0">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-muted-foreground">
                            <th class="px-4 py-3">{{ t('logistics.vehicles.columns.plate') }}</th>
                            <th class="px-4 py-3">{{ t('logistics.vehicles.columns.type') }}</th>
                            <th class="px-4 py-3">{{ t('logistics.vehicles.columns.driver') }}</th>
                            <th class="px-4 py-3">{{ t('logistics.vehicles.columns.phone') }}</th>
                            <th class="px-4 py-3">{{ t('logistics.vehicles.columns.status') }}</th>
                            <th class="px-4 py-3">{{ t('logistics.vehicles.columns.packages') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="vehicle in vehicles" :key="vehicle.id" class="border-b last:border-0 hover:bg-muted/50">
                            <td class="px-4 py-3 font-mono font-medium">{{ vehicle.plate_number }}</td>
                            <td class="px-4 py-3">{{ t(`logistics.vehicles.types.${vehicle.type}`) }}</td>
                            <td class="px-4 py-3">{{ vehicle.driver_name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ vehicle.driver_phone ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <Badge :variant="statusVariant[vehicle.status]">{{ t(`logistics.vehicles.statuses.${vehicle.status}`) }}</Badge>
                            </td>
                            <td class="px-4 py-3">{{ vehicle.packages_count ?? 0 }}</td>
                        </tr>
                        <tr v-if="vehicles.length === 0">
                            <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">{{ t('logistics.vehicles.empty') }}</td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>
