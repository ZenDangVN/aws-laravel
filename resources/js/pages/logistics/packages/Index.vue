<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { useI18n } from 'vue-i18n';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import type { Package, PackageStatus } from '@/types/logistics';
import { logistics } from '@/lib/logistics';

const { t } = useI18n();

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
            { title: 'Packages', href: logistics.packages.index() },
        ],
    },
});

const statuses: { value: PackageStatus | '' }[] = [
    { value: '' },
    { value: 'pending' },
    { value: 'in_transit' },
    { value: 'at_warehouse' },
    { value: 'out_for_delivery' },
    { value: 'delivered' },
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
    <Head :title="t('logistics.packages.title')" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">{{ t('logistics.packages.title') }}</h1>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-2">
            <Input v-model="search" :placeholder="t('logistics.packages.searchPlaceholder')" class="w-64" @keyup.enter="doSearch" />
            <Button
                v-for="s in statuses"
                :key="s.value"
                :variant="filters.status === s.value || (!filters.status && !s.value) ? 'default' : 'outline'"
                size="sm"
                @click="applyFilter(s.value)"
            >
                {{ s.value ? t(`packageStatus.${s.value}`) : t('logistics.packages.all') }}
            </Button>
        </div>

        <Card>
            <CardContent class="p-0">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b text-left text-muted-foreground">
                            <th class="px-4 py-3">{{ t('logistics.packages.columns.tracking') }}</th>
                            <th class="px-4 py-3">{{ t('logistics.packages.columns.rfidTag') }}</th>
                            <th class="px-4 py-3">{{ t('logistics.packages.columns.status') }}</th>
                            <th class="px-4 py-3">{{ t('logistics.packages.columns.location') }}</th>
                            <th class="px-4 py-3">{{ t('logistics.packages.columns.updatedAt') }}</th>
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
                                <Badge :variant="statusVariant[pkg.status]">{{ t(`packageStatus.${pkg.status}`) }}</Badge>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ pkg.current_warehouse?.name ?? pkg.current_vehicle?.plate_number ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ new Date(pkg.updated_at).toLocaleString() }}
                            </td>
                        </tr>
                        <tr v-if="packages.data.length === 0">
                            <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">{{ t('logistics.packages.empty') }}</td>
                        </tr>
                    </tbody>
                </table>
            </CardContent>
        </Card>
    </div>
</template>
