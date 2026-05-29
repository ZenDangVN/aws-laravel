<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Doughnut, Line } from 'vue-chartjs';
import {
    ArcElement,
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LinearScale,
    LineElement,
    PointElement,
    Title,
    Tooltip,
} from 'chart.js';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { LogisticsStats, RfidScan, RfidScanEvent } from '@/types/logistics';
import { logistics } from '@/lib/logistics';

ChartJS.register(Title, Tooltip, Legend, ArcElement, CategoryScale, LinearScale, LineElement, PointElement);

const props = defineProps<{
    stats: LogisticsStats;
    recent_scans: RfidScan[];
    scans_per_hour: Record<string, number>;
}>();

const { t } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Logistics', href: logistics.dashboard() }],
    },
});

const liveScans = ref<RfidScanEvent[]>([]);
let echo: typeof window.Echo | null = null;

onMounted(async () => {
    const { default: echoInstance } = await import('@/echo');
    echo = echoInstance;
    echo.channel('logistics').listen('.RfidScanReceived', (event: RfidScanEvent) => {
        liveScans.value.unshift(event);
        if (liveScans.value.length > 50) {
            liveScans.value.pop();
        }
    });
});

onUnmounted(() => {
    echo?.leaveChannel('logistics');
});

const statusColors: Record<string, string> = {
    pending: '#94a3b8',
    in_transit: '#3b82f6',
    at_warehouse: '#f59e0b',
    out_for_delivery: '#8b5cf6',
    delivered: '#22c55e',
};

const doughnutData = {
    labels: ['pending', 'in_transit', 'at_warehouse', 'out_for_delivery', 'delivered'],
    datasets: [{
        data: [
            props.stats.pending,
            props.stats.in_transit,
            props.stats.at_warehouse,
            props.stats.out_for_delivery,
            props.stats.delivered,
        ],
        backgroundColor: Object.values(statusColors),
        borderWidth: 0,
    }],
};

const hours = Array.from({ length: 24 }, (_, i) => `${String(i).padStart(2, '0')}:00`);
const lineData = {
    labels: hours,
    datasets: [{
        label: 'Scans',
        data: hours.map((_, i) => props.scans_per_hour[String(i).padStart(2, '0')] ?? 0),
        borderColor: '#3b82f6',
        backgroundColor: 'rgba(59,130,246,0.1)',
        tension: 0.4,
        fill: true,
    }],
};

const chartOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } };
</script>

<template>
    <Head :title="t('logistics.dashboard.title')" />

    <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
        <!-- KPI Cards -->
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('logistics.dashboard.totalPackages') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold">{{ stats.total_packages }}</div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('logistics.dashboard.activeShipments') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold text-blue-500">{{ stats.active_shipments }}</div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('logistics.dashboard.vehiclesOnRoute') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold text-violet-500">{{ stats.vehicles_on_route }}</div>
                </CardContent>
            </Card>
            <Card>
                <CardHeader class="pb-2">
                    <CardTitle class="text-sm font-medium text-muted-foreground">{{ t('logistics.dashboard.scansToday') }}</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="text-3xl font-bold text-amber-500">{{ stats.scans_today }}</div>
                </CardContent>
            </Card>
        </div>

        <!-- Charts -->
        <div class="grid gap-4 md:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('logistics.dashboard.packageStatus') }}</CardTitle>
                </CardHeader>
                <CardContent class="h-64">
                    <Doughnut :data="doughnutData" :options="{ ...chartOptions, plugins: { legend: { display: true, position: 'right' } } }" />
                </CardContent>
            </Card>
            <Card>
                <CardHeader>
                    <CardTitle>{{ t('logistics.dashboard.scansPerHour') }}</CardTitle>
                </CardHeader>
                <CardContent class="h-64">
                    <Line :data="lineData" :options="chartOptions" />
                </CardContent>
            </Card>
        </div>

        <!-- Live Scan Feed -->
        <Card>
            <CardHeader>
                <CardTitle class="flex items-center gap-2">
                    <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-green-500" />
                    {{ t('logistics.dashboard.liveFeed') }}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div class="max-h-96 overflow-y-auto">
                    <table class="w-full text-sm">
                        <thead class="sticky top-0 bg-card">
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-2 pr-4">{{ t('logistics.dashboard.columns.time') }}</th>
                                <th class="pb-2 pr-4">{{ t('logistics.dashboard.columns.rfidTracking') }}</th>
                                <th class="pb-2 pr-4">{{ t('logistics.dashboard.columns.location') }}</th>
                                <th class="pb-2">{{ t('logistics.dashboard.columns.status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="scan in [...liveScans, ...recent_scans].slice(0, 50)"
                                :key="scan.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-2 pr-4 font-mono text-xs text-muted-foreground">
                                    {{ new Date(scan.scanned_at).toLocaleTimeString() }}
                                </td>
                                <td class="py-2 pr-4">
                                    <div class="font-mono text-xs">{{ scan.rfid_tag }}</div>
                                    <div class="text-muted-foreground">{{ scan.tracking_number }}</div>
                                </td>
                                <td class="py-2 pr-4">
                                    {{ scan.warehouse?.name ?? scan.vehicle?.plate_number ?? scan.location_type }}
                                </td>
                                <td class="py-2">
                                    <Badge variant="outline">
                                        {{ t(`packageStatus.${scan.package_status}`) }}
                                    </Badge>
                                </td>
                            </tr>
                            <tr v-if="liveScans.length === 0 && recent_scans.length === 0">
                                <td colspan="4" class="py-8 text-center text-muted-foreground">
                                    {{ t('logistics.dashboard.empty') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
