<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { History, LayoutDashboard, ShieldCheck } from '@lucide/vue';
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
import { index as authorizationIndex } from '@/routes/back-office/authorization';
import { index as privilegeAuditIndex } from '@/routes/back-office/privilege-audits';
import type { NavItem } from '@/types';

const page = usePage();
const currentPath = computed(() => page.url.split('?')[0]);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: backOffice.dashboard(),
            icon: LayoutDashboard,
            isActive: currentPath.value === backOffice.dashboard.url(),
        },
    ];

    if (page.props.auth.capabilities.can_view_authorization) {
        items.push({
            title: 'Manage Role',
            href: authorizationIndex(),
            icon: ShieldCheck,
            isActive: currentPath.value === authorizationIndex.url(),
        });
    }

    if (page.props.auth.capabilities.can_view_privilege_audits) {
        items.push({
            title: 'Audit Perubahan Privilege',
            href: privilegeAuditIndex(),
            icon: History,
            isActive: currentPath.value === privilegeAuditIndex.url(),
        });
    }

    return items;
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="backOffice.dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" label="Portal Internal" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
