<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Package, RfidScanEvent } from '@/types/logistics';
import { logistics } from '@/lib/logistics';

const props = defineProps<{ package: Package }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Logistics', href: logistics.dashboard() },
            { title: 'Kiện hàng', href: logistics.packages.index() },
            { title: 'Chi tiết' },
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
    <Head :title="`Kiện hàng ${package.tracking_number}`" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="grid gap-4 md:grid-cols-3">
            <!-- Package Info -->
            <Card class="md:col-span-1">
                <CardHeader>
                    <CardTitle>Thông tin kiện hàng</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div>
                        <div class="text-xs text-muted-foreground">Tracking Number</div>
                        <div class="font-mono font-medium">{{ package.tracking_number }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">RFID Tag</div>
                        <div class="font-mono text-sm">{{ package.rfid_tag }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">Trạng thái</div>
                        <Badge class="mt-1">{{ currentStatus }}</Badge>
                    </div>
                    <div v-if="package.description">
                        <div class="text-xs text-muted-foreground">Mô tả</div>
                        <div>{{ package.description }}</div>
                    </div>
                    <div v-if="package.weight">
                        <div class="text-xs text-muted-foreground">Khối lượng</div>
                        <div>{{ package.weight }} kg</div>
                    </div>
                    <div v-if="package.current_warehouse">
                        <div class="text-xs text-muted-foreground">Đang ở kho</div>
                        <div>{{ package.current_warehouse.name }}</div>
                    </div>
                    <div v-if="package.current_vehicle">
                        <div class="text-xs text-muted-foreground">Đang trên xe</div>
                        <div>{{ package.current_vehicle.plate_number }}</div>
                    </div>
                </CardContent>
            </Card>

            <!-- Scan Timeline -->
            <Card class="md:col-span-2">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <span class="inline-block h-2 w-2 animate-pulse rounded-full bg-green-500" />
                        Lịch sử quét RFID
                    </CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="relative max-h-[60vh] overflow-y-auto pl-4">
                        <div class="absolute left-4 top-0 h-full w-0.5 bg-border" />
                        <div
                            v-for="scan in package.rfid_scans"
                            :key="scan.id"
                            class="relative mb-4 pl-6"
                        >
                            <div class="absolute -left-1.5 top-1.5 h-3 w-3 rounded-full border-2 border-primary bg-background" />
                            <div class="rounded-lg border bg-card p-3">
                                <div class="flex items-center justify-between">
                                    <Badge variant="outline" class="text-xs">{{ scan.location_type }}</Badge>
                                    <span class="text-xs text-muted-foreground">
                                        {{ new Date(scan.scanned_at).toLocaleString('vi-VN') }}
                                    </span>
                                </div>
                                <div class="mt-1 text-sm font-medium">
                                    {{ scan.warehouse?.name ?? scan.vehicle?.plate_number ?? scan.scanner_id }}
                                </div>
                                <div class="text-xs text-muted-foreground">Scanner: {{ scan.scanner_id }}</div>
                            </div>
                        </div>
                        <div v-if="!package.rfid_scans?.length" class="py-8 text-center text-muted-foreground">
                            Chưa có lịch sử quét
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
