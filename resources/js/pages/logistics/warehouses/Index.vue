<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useI18n } from 'vue-i18n';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Warehouse } from '@/types/logistics';
import { logistics } from '@/lib/logistics';

const { t } = useI18n();

defineProps<{ warehouses: Warehouse[] }>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Logistics', href: logistics.dashboard() },
            { title: 'Warehouses', href: logistics.warehouses.index() },
        ],
    },
});
</script>

<template>
    <Head :title="t('logistics.warehouses.title')" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">{{ t('logistics.warehouses.title') }}</h1>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Card v-for="warehouse in warehouses" :key="warehouse.id">
                <CardHeader>
                    <CardTitle class="flex items-center justify-between">
                        <span>{{ warehouse.name }}</span>
                        <span class="rounded-md bg-muted px-2 py-0.5 font-mono text-xs">{{ warehouse.code }}</span>
                    </CardTitle>
                    <p class="text-sm text-muted-foreground">{{ warehouse.city }}</p>
                </CardHeader>
                <CardContent>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <div class="text-2xl font-bold">{{ warehouse.packages_count ?? 0 }}</div>
                            <div class="text-xs text-muted-foreground">{{ t('logistics.warehouses.packagesCount') }}</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-amber-500">{{ warehouse.scans_today ?? 0 }}</div>
                            <div class="text-xs text-muted-foreground">{{ t('logistics.warehouses.scansToday') }}</div>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-muted-foreground">{{ warehouse.address }}</p>
                </CardContent>
            </Card>
            <div v-if="warehouses.length === 0" class="col-span-full py-8 text-center text-muted-foreground">
                {{ t('logistics.warehouses.empty') }}
            </div>
        </div>
    </div>
</template>
