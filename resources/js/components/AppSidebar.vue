<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { i18n } from '@/plugins/i18n';
import { BookOpen, FolderGit2, LayoutGrid, Package, PackageSearch, Truck, Warehouse } from 'lucide-vue-next';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { logistics } from '@/lib/logistics';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const { t } = i18n.global;

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: t('nav.dashboard'),
        href: dashboard(),
        icon: LayoutGrid,
    },
]);

const logisticsNavItems = computed<NavItem[]>(() => [
    {
        title: t('nav.logistics'),
        href: logistics.dashboard(),
        icon: PackageSearch,
    },
    {
        title: t('nav.packages'),
        href: logistics.packages.index(),
        icon: Package,
    },
    {
        title: t('nav.shipments'),
        href: logistics.shipments.index(),
        icon: Truck,
    },
    {
        title: t('nav.warehouses'),
        href: logistics.warehouses.index(),
        icon: Warehouse,
    },
    {
        title: t('nav.vehicles'),
        href: logistics.vehicles.index(),
        icon: Truck,
    },
]);

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
            <NavMain :items="logisticsNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
