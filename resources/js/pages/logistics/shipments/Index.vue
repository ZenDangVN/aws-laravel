<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import type { Shipment, ShipmentStatus } from '@/types/logistics';
import { logistics } from '@/lib/logistics';

const { t } = useI18n();

defineProps<{
    shipments: {
        data: Shipment[];
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { status?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Logistics', href: logistics.dashboard() },
            { title: 'Shipments', href: logistics.shipments.index() },
        ],
    },
});

const statuses: { value: ShipmentStatus | '' }[] = [
    { value: '' },
    { value: 'pending' },
    { value: 'loading' },
    { value: 'in_transit' },
    { value: 'arrived' },
    { value: 'completed' },
];

const statusVariant: Record<ShipmentStatus, 'default' | 'secondary' | 'outline'> = {
    pending: 'secondary',
    loading: 'outline',
    in_transit: 'default',
    arrived: 'outline',
    completed: 'secondary',
};

function applyFilter(status: string) {
    router.get(logistics.shipments.index(), { status: status || undefined }, { preserveState: true, replace: true });
}
</script>

<template>
    <Head :title="t('logistics.shipments.title')" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">{{ t('logistics.shipments.title') }}</h1>
        </div>

        <div class="flex flex-wrap gap-2">
            <Button
                v-for="s in statuses"
                :key="s.value"
                :variant="filters.status === s.value || (!filters.status && !s.value) ? 'default' : 'outline'"
                size="sm"
                @click="applyFilter(s.value)"
            >
                {{ s.value ? t(`shipmentStatus.${s.value}`) : t('logistics.packages.all') }}
            </Button>
        </div>

        <Card>
            <CardContent class="p-0">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-muted-foreground">
                            <th class="px-4 py-3">{{ t('logistics.shipments.columns.ref') }}</th>
                            <th class="px-4 py-3">{{ t('logistics.shipments.columns.route') }}</th>
                            <th class="px-4 py-3">{{ t('logistics.shipments.columns.vehicle') }}</th>
                            <th class="px-4 py-3">{{ t('logistics.shipments.columns.status') }}</th>
                            <th class="px-4 py-3">{{ t('logistics.shipments.columns.packages') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="shipment in shipments.data" :key="shipment.id" class="border-b last:border-0 hover:bg-muted/50">
                            <td class="px-4 py-3">
                                <Link :href="logistics.shipments.show({ shipment: shipment.id })" class="font-medium hover:underline">
                                    {{ shipment.reference_number }}
                                </Link>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-muted-foreground">{{ shipment.origin_warehouse?.code }}</span>
                                <span class="mx-1">→</span>
                                <span class="text-muted-foreground">{{ shipment.destination_warehouse?.code }}</span>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">{{ shipment.vehicle?.plate_number ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <Badge :variant="statusVariant[shipment.status]">{{ t(`shipmentStatus.${shipment.status}`) }}</Badge>
                            </td>
                            <td class="px-4 py-3">{{ shipment.packages_count ?? 0 }}</td>
                        </tr>
                        <tr v-if="shipments.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">{{ t('logistics.shipments.empty') }}</td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>
