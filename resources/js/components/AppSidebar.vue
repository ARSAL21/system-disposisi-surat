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
import backOffice from '@/routes/back-office';
import publicRoutes from '@/routes/public';
import type { NavItem } from '@/types';

const page = usePage();
const currentPath = computed(() => page.url.split('?')[0]);
const isPublicAccount = computed(
    () => page.props.auth.user.account_type === 'PUBLIC',
);

const homeHref = computed(() =>
    isPublicAccount.value ? publicRoutes.dashboard() : backOffice.dashboard(),
);

const navigationLabel = computed(() =>
    isPublicAccount.value ? 'Portal Publik' : 'Portal Internal',
);

const mainNavItems = computed<NavItem[]>(() => {
    if (!isPublicAccount.value) {
        return [
            {
                title: 'Dashboard',
                href: backOffice.dashboard(),
                icon: LayoutDashboard,
                isActive: currentPath.value === backOffice.dashboard.url(),
            },
        ];
    }

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
                        <Link :href="homeHref">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" :label="navigationLabel" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
