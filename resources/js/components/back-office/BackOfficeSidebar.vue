<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    Archive,
    ClipboardCheck,
    History,
    Inbox,
    Landmark,
    LayoutDashboard,
    ListChecks,
    MailOpen,
    Network,
    Route as RouteIcon,
    ShieldCheck,
    UserRoundCog,
} from '@lucide/vue';
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

    if (page.props.auth.capabilities.can_view_intake) {
        items.push({
            title: 'Penerimaan Surat',
            href: '/back-office/intake/submissions',
            icon: Inbox,
            isActive: currentPath.value.startsWith(
                '/back-office/intake/submissions',
            ),
        });
    }

    if (page.props.auth.capabilities.can_decide_intake) {
        const approvalPath = '/back-office/intake/approvals';
        items.push({
            title: 'Persetujuan Surat',
            href: approvalPath,
            icon: ClipboardCheck,
            isActive: currentPath.value.startsWith(approvalPath),
        });
    }

    if (page.props.auth.capabilities.can_view_letter_routing) {
        const routingPath = '/back-office/letter-routing';
        items.push({
            title: 'Routing Surat',
            href: routingPath,
            icon: RouteIcon,
            isActive: currentPath.value.startsWith(routingPath),
        });
    }

    if (page.props.auth.capabilities.can_view_executive_inbox) {
        const executiveInboxPath = '/back-office/executive/inbox';
        items.push({
            title: 'Inbox Pimpinan',
            href: executiveInboxPath,
            icon: Landmark,
            isActive: currentPath.value.startsWith(executiveInboxPath),
        });
    }

    if (page.props.auth.capabilities.can_view_dispositions) {
        const dispositionInboxPath = '/back-office/dispositions/inbox';
        items.push({
            title: 'Inbox Disposisi',
            href: dispositionInboxPath,
            icon: MailOpen,
            isActive: currentPath.value.startsWith(dispositionInboxPath),
        });
    }

    if (page.props.auth.capabilities.can_view_document_versions) {
        const archivePath = '/back-office/documents';
        items.push({
            title: 'Arsip Dokumen',
            href: archivePath,
            icon: Archive,
            isActive:
                currentPath.value === archivePath ||
                /^\/back-office\/letters\/[^/]+\/documents$/.test(
                    currentPath.value,
                ),
        });
    }

    if (page.props.auth.capabilities.can_view_letter_activities) {
        const activityPath = '/back-office/audits/letters';
        items.push({
            title: 'Aktivitas Surat',
            href: activityPath,
            icon: Activity,
            isActive: currentPath.value === activityPath,
        });
    }

    if (page.props.auth.capabilities.can_view_authorization) {
        items.push({
            title: 'Manage Role',
            href: authorizationIndex(),
            icon: ShieldCheck,
            isActive: currentPath.value === authorizationIndex.url(),
        });
    }

    if (page.props.auth.capabilities.can_view_organization) {
        items.push(
            {
                title: 'Struktur Organisasi',
                href: '/back-office/organization/structure',
                icon: Network,
                isActive:
                    currentPath.value === '/back-office/organization/structure',
            },
            {
                title: 'Penugasan Jabatan',
                href: '/back-office/organization/assignments',
                icon: UserRoundCog,
                isActive:
                    currentPath.value ===
                    '/back-office/organization/assignments',
            },
        );
    }

    if (page.props.auth.capabilities.can_view_disposition_instructions) {
        const instructionPath = '/back-office/workflow/instruction-labels';
        items.push({
            title: 'Instruksi Disposisi',
            href: instructionPath,
            icon: ListChecks,
            isActive: currentPath.value.startsWith(instructionPath),
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
