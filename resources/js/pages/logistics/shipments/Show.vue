<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Package, Shipment } from '@/types/logistics';
import { logistics } from '@/lib/logistics';

const props = defineProps<{ shipment: Shipment }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Logistics', href: logistics.dashboard() },
            { title: 'Lô hàng', href: logistics.shipments.index() },
            { title: 'Chi tiết' },
        ],
    },
});

const deliveredCount = props.shipment.packages?.filter((p: Package) => p.status === 'delivered').length ?? 0;
const totalCount = props.shipment.packages?.length ?? 0;
const progressPercent = totalCount > 0 ? Math.round((deliveredCount / totalCount) * 100) : 0;
</script>

<template>
    <Head :title="`Lô hàng ${shipment.reference_number}`" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="grid gap-4 md:grid-cols-3">
            <Card class="md:col-span-1">
                <CardHeader>
                    <CardTitle>Thông tin lô hàng</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div>
                        <div class="text-xs text-muted-foreground">Mã lô</div>
                        <div class="font-mono font-medium">{{ shipment.reference_number }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">Trạng thái</div>
                        <Badge class="mt-1">{{ shipment.status }}</Badge>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">Từ kho</div>
                        <div>{{ shipment.origin_warehouse?.name }} ({{ shipment.origin_warehouse?.city }})</div>
                    </div>
                    <div>
                        <div class="text-xs text-muted-foreground">Đến kho</div>
                        <div>{{ shipment.destination_warehouse?.name }} ({{ shipment.destination_warehouse?.city }})</div>
                    </div>
                    <div v-if="shipment.vehicle">
                        <div class="text-xs text-muted-foreground">Phương tiện</div>
                        <div>{{ shipment.vehicle.plate_number }} — {{ shipment.vehicle.driver_name }}</div>
                    </div>
                    <div v-if="totalCount > 0">
                        <div class="mb-1 flex justify-between text-xs text-muted-foreground">
                            <span>Tiến độ giao hàng</span>
                            <span>{{ deliveredCount }}/{{ totalCount }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-muted">
                            <div class="h-2 rounded-full bg-primary transition-all" :style="{ width: `${progressPercent}%` }" />
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card class="md:col-span-2">
                <CardHeader>
                    <CardTitle>Danh sách kiện hàng ({{ totalCount }})</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="px-4 py-3">Tracking</th>
                                <th class="px-4 py-3">RFID</th>
                                <th class="px-4 py-3">Mô tả</th>
                                <th class="px-4 py-3">Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="pkg in shipment.packages" :key="pkg.id" class="border-b last:border-0">
                                <td class="px-4 py-2 font-mono text-xs">{{ pkg.tracking_number }}</td>
                                <td class="px-4 py-2 font-mono text-xs text-muted-foreground">{{ pkg.rfid_tag }}</td>
                                <td class="px-4 py-2">{{ pkg.description ?? '—' }}</td>
                                <td class="px-4 py-2"><Badge variant="outline">{{ pkg.status }}</Badge></td>
                            </tr>
                            <tr v-if="!shipment.packages?.length">
                                <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">Chưa có kiện hàng</td>
                            </tr>
                        </tbody>
                    </table>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
