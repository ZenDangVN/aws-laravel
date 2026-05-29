<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import type { Package, PackageStatus } from '@/types/logistics';
import { logistics } from '@/lib/logistics';

defineProps<{
    packages: {
        data: Package[];
        links: { url: string | null; label: string; active: boolean }[];
        meta: { total: number; per_page: number; current_page: number; last_page: number };
    };
    filters: { status?: string; search?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Logistics', href: logistics.dashboard() },
            { title: 'Kiện hàng', href: logistics.packages.index() },
        ],
    },
});

const statuses: { value: PackageStatus | ''; label: string }[] = [
    { value: '', label: 'Tất cả' },
    { value: 'pending', label: 'Chờ xử lý' },
    { value: 'in_transit', label: 'Đang vận chuyển' },
    { value: 'at_warehouse', label: 'Tại kho' },
    { value: 'out_for_delivery', label: 'Đang giao' },
    { value: 'delivered', label: 'Đã giao' },
];

const statusVariant: Record<PackageStatus, 'default' | 'secondary' | 'outline' | 'destructive'> = {
    pending: 'secondary',
    in_transit: 'default',
    at_warehouse: 'outline',
    out_for_delivery: 'default',
    delivered: 'secondary',
};

const search = ref('');

function applyFilter(status: string) {
    router.get(logistics.packages.index(), { status: status || undefined, search: search.value || undefined }, { preserveState: true, replace: true });
}

function doSearch() {
    router.get(logistics.packages.index(), { search: search.value || undefined }, { preserveState: true, replace: true });
}
</script>

<template>
    <Head title="Kiện hàng" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Kiện hàng</h1>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-2">
            <Input v-model="search" placeholder="Tìm tracking / RFID..." class="w-64" @keyup.enter="doSearch" />
            <Button v-for="s in statuses" :key="s.value" :variant="filters.status === s.value || (!filters.status && !s.value) ? 'default' : 'outline'" size="sm" @click="applyFilter(s.value)">
                {{ s.label }}
            </Button>
        </div>

        <Card>
            <CardContent class="p-0">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-muted-foreground">
                            <th class="px-4 py-3">Tracking</th>
                            <th class="px-4 py-3">RFID Tag</th>
                            <th class="px-4 py-3">Trạng thái</th>
                            <th class="px-4 py-3">Vị trí hiện tại</th>
                            <th class="px-4 py-3">Cập nhật</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="pkg in packages.data" :key="pkg.id" class="border-b last:border-0 hover:bg-muted/50">
                            <td class="px-4 py-3">
                                <Link :href="logistics.packages.show({ package: pkg.id })" class="font-medium hover:underline">
                                    {{ pkg.tracking_number }}
                                </Link>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">{{ pkg.rfid_tag }}</td>
                            <td class="px-4 py-3">
                                <Badge :variant="statusVariant[pkg.status]">{{ pkg.status }}</Badge>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ pkg.current_warehouse?.name ?? pkg.current_vehicle?.plate_number ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ new Date(pkg.updated_at).toLocaleString('vi-VN') }}
                            </td>
                        </tr>
                        <tr v-if="packages.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">Không có kiện hàng nào</td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>
