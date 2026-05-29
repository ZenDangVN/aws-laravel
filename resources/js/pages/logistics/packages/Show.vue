<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Package } from '@/types/logistics';
import { logistics } from '@/lib/logistics';

const props = defineProps<{ package: Package }>();
const { t } = useI18n();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Logistics', href: logistics.dashboard() },
            { title: 'Packages', href: logistics.packages.index() },
            { title: 'Detail' },
        ],
    },
});

const currentStatus = ref(props.package.status);
let echo: typeof window.Echo | null = null;

onMounted(async () => {
    const { default: echoInstance } = await import('@/echo');
    echo = echoInstance;
    echo.private(`packages.${props.package.id}`).listen('.PackageStatusUpdated', (event: { status: string }) => {
        currentStatus.value = event.status as typeof currentStatus.value;
    });
});

onUnmounted(() => {
    echo?.leaveChannel(`private-packages.${props.package.id}`);
});
</script>

<template>
    <Head :title="package.tracking_number" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="grid gap-4 md:grid-cols-3">
            <!-- Package Info -->
            <Card class="md:col-span-1">
                <CardHeader>
                    <CardTitle>{{ t('logistics.packages.detail.title') }}</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div>
                        <div class="text-xs text-muted-foreground">{{ t('logistics.packages.detail.trackingNumber') }}</div>
                        <div class="font-mono font-medium">{{ package.tracking_number }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">{{ t('logistics.packages.detail.rfidTag') }}</div>
                        <div class="font-mono text-sm">{{ package.rfid_tag }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">{{ t('logistics.packages.detail.status') }}</div>
                        <Badge class="mt-1">{{ t(`packageStatus.${currentStatus}`) }}</Badge>
                    </div>
                    <div v-if="package.description">
                        <div class="text-xs text-muted-foreground">{{ t('logistics.packages.detail.description') }}</div>
                        <div>{{ package.description }}</div>
                    </div>
                    <div v-if="package.weight">
                        <div class="text-xs text-muted-foreground">{{ t('logistics.packages.detail.weight') }}</div>
                        <div>{{ package.weight }} {{ t('logistics.packages.detail.kg') }}</div>
                    </div>
                    <div v-if="package.current_warehouse">
                        <div class="text-xs text-muted-foreground">{{ t('logistics.packages.detail.atWarehouse') }}</div>
                        <div>{{ package.current_warehouse.name }}</div>
                    </div>
                    <div v-if="package.current_vehicle">
                        <div class="text-xs text-muted-foreground">{{ t('logistics.packages.detail.onVehicle') }}</div>
                        <div>{{ package.current_vehicle.plate_number }}</div>
                    </div>
                </CardContent>
            </Card>

            <!-- Scan Timeline -->
            <Card class="md:col-span-2">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-green-500" />
                        {{ t('logistics.packages.detail.scanHistory') }}
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="relative max-h-[60vh] overflow-y-auto pl-4">
                        <div class="absolute left-4 top-0 h-full w-0.5 bg-border" />
                        <div v-for="scan in package.rfid_scans" :key="scan.id" class="relative mb-4 pl-6">
                            <div class="absolute -left-1.5 top-1.5 h-3 w-3 rounded-full border-2 border-primary bg-background" />
                            <div class="rounded-lg border bg-card p-3">
                                <div class="flex items-center justify-between">
                                    <Badge variant="outline" class="text-xs">{{ scan.location_type }}</Badge>
                                    <span class="text-xs text-muted-foreground">
                                        {{ new Date(scan.scanned_at).toLocaleString() }}
                                    </span>
                                </div>
                                <div class="mt-1 text-sm font-medium">
                                    {{ scan.warehouse?.name ?? scan.vehicle?.plate_number ?? scan.scanner_id }}
                                </div>
                                <div class="text-xs text-muted-foreground">{{ t('logistics.packages.detail.scanner') }}: {{ scan.scanner_id }}</div>
                            </div>
                        </div>
                        <div v-if="!package.rfid_scans?.length" class="py-8 text-center text-muted-foreground">
                            {{ t('logistics.packages.detail.noHistory') }}
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
