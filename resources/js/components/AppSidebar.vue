<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { FilePlus2, Files, LayoutDashboard } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
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
import publicRoutes from '@/routes/public';
import type { NavItem } from '@/types';

const page = usePage();
const currentPath = computed(() => page.url.split('?')[0]);

const mainNavItems = computed<NavItem[]>(() => {
    return [
        {
            title: 'Dashboard',
            href: publicRoutes.dashboard(),
            icon: LayoutDashboard,
            isActive: currentPath.value === publicRoutes.dashboard.url(),
        },
        {
            title: 'Surat Saya',
            href: publicRoutes.submissions.index(),
            icon: Files,
            isActive:
                currentPath.value.startsWith(
                    publicRoutes.submissions.index.url(),
                ) &&
                currentPath.value !== publicRoutes.submissions.create.url(),
        },
        {
            title: 'Buat Surat',
            href: publicRoutes.submissions.create(),
            icon: FilePlus2,
            isActive:
                currentPath.value === publicRoutes.submissions.create.url(),
        },
    ];
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="publicRoutes.dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" label="Portal Publik" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
