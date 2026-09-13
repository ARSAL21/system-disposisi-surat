<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    Archive,
    BookOpenCheck,
    ClipboardCheck,
    FilePlus2,
    History,
    Inbox,
    Landmark,
    LayoutDashboard,
    ListChecks,
    MailOpen,
    Network,
    ChartNoAxesCombined,
    Route as RouteIcon,
    ShieldCheck,
    SendHorizontal,
    FilePenLine,
    HelpCircle,
    ShieldAlert,
    UserRoundCog,
    Users,
    FileSearch,
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
import { useTwoFactorWalkthrough } from '@/composables/useTwoFactorWalkthrough';
import backOffice from '@/routes/back-office';
import { index as authorizationIndex } from '@/routes/back-office/authorization';
import { index as privilegeAuditIndex } from '@/routes/back-office/privilege-audits';
import type { NavItem } from '@/types';

const page = usePage();
const { startTour } = useTwoFactorWalkthrough();
const currentPath = computed(() => page.url.split('?')[0]);
const requiresMfaSetup = computed(() =>
    Boolean(page.props.auth.user?.requires_mfa_setup),
);

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

    const isM81Preview =
        currentPath.value.startsWith('/back-office/previews/intake/manual') ||
        currentPath.value.startsWith('/back-office/previews/incoming-letters');

    if (page.props.auth.capabilities.can_create_manual_intake || isM81Preview) {
        const manualIntakePath = isM81Preview
            ? '/back-office/previews/intake/manual/create'
            : '/back-office/intake/manual/create';
        items.push({
            title: 'Catat Surat Manual',
            href: manualIntakePath,
            icon: FilePlus2,
            isActive: currentPath.value.startsWith(manualIntakePath),
        });
    }

    if (
        page.props.auth.capabilities.can_view_incoming_register ||
        isM81Preview
    ) {
        const incomingRegisterPath = isM81Preview
            ? '/back-office/previews/incoming-letters'
            : '/back-office/incoming-letters';
        items.push({
            title: 'Buku Surat Masuk',
            href: incomingRegisterPath,
            icon: BookOpenCheck,
            isActive: currentPath.value.startsWith(incomingRegisterPath),
        });
    }

    if (page.props.auth.capabilities.can_view_standalone_outgoing === true) {
        const draftPath = '/back-office/standalone-outgoing';
        items.push({
            title: 'Konsep Surat Keluar',
            href: draftPath,
            icon: FilePenLine,
            isActive: currentPath.value.startsWith(draftPath),
        });
    }

    if (page.props.auth.capabilities.can_view_outgoing_templates === true) {
        const templatePath = '/back-office/outgoing-templates';
        items.push({
            title: 'Template Surat',
            href: templatePath,
            icon: Archive,
            isActive: currentPath.value.startsWith(templatePath),
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

    const isExpertConsultationPreview = currentPath.value.startsWith(
        '/back-office/previews/expert-consultations',
    );

    if (
        page.props.auth.capabilities.can_view_expert_consultations === true ||
        (isExpertConsultationPreview &&
            !currentPath.value.endsWith('/coordination'))
    ) {
        const consultationPath = isExpertConsultationPreview
            ? '/back-office/previews/expert-consultations'
            : '/back-office/expert-consultations';
        items.push({
            title: 'Tugas Telaah Staf Ahli',
            href: consultationPath,
            icon: FileSearch,
            isActive: currentPath.value.startsWith(consultationPath),
        });
    }

    const isExpertCoordinationPreview = currentPath.value.endsWith(
        '/expert-consultations/coordination',
    );

    if (
        page.props.auth.capabilities.can_coordinate_expert_consultations ===
            true ||
        isExpertCoordinationPreview
    ) {
        const coordinationPath = isExpertCoordinationPreview
            ? '/back-office/previews/expert-consultations/coordination'
            : '/back-office/expert-consultations/coordination';
        items.push({
            title: 'Koordinasi Telaah',
            href: coordinationPath,
            icon: Landmark,
            isActive: currentPath.value.startsWith(coordinationPath),
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

    const isReportPreview = currentPath.value.startsWith(
        '/back-office/previews/reports',
    );

    if (page.props.auth.capabilities.can_view_reports || isReportPreview) {
        const reportPath = isReportPreview
            ? '/back-office/previews/reports'
            : '/back-office/reports';
        items.push({
            title: 'Laporan Periodik',
            href: reportPath,
            icon: ChartNoAxesCombined,
            isActive: currentPath.value.startsWith(reportPath),
        });
    }

    const isLetterResponsePreview = currentPath.value.startsWith(
        '/back-office/previews/letter-responses',
    );

    if (
        page.props.auth.capabilities.can_view_letter_responses ||
        isLetterResponsePreview
    ) {
        const responsePath = isLetterResponsePreview
            ? '/back-office/previews/letter-responses'
            : '/back-office/letter-responses';
        items.push({
            title: 'Penyusunan Balasan',
            href: responsePath,
            icon: Network,
            isActive: currentPath.value.startsWith(responsePath),
        });
    }

    const isOutgoingPreview = currentPath.value.startsWith(
        '/back-office/previews/outgoing-letters',
    );

    if (
        page.props.auth.capabilities.can_view_outgoing_register === true ||
        isOutgoingPreview
    ) {
        const outgoingPath = isOutgoingPreview
            ? '/back-office/previews/outgoing-letters'
            : '/back-office/outgoing-letters';
        items.push({
            title: 'Register Surat Keluar',
            href: outgoingPath,
            icon: SendHorizontal,
            isActive: currentPath.value.startsWith(outgoingPath),
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

    const isUserManagementPreview =
        currentPath.value.startsWith('/back-office/previews/users') ||
        currentPath.value.startsWith('/back-office/users');

    if (
        page.props.auth.capabilities.can_view_users ||
        isUserManagementPreview
    ) {
        const usersPath = isUserManagementPreview
            ? '/back-office/previews/users'
            : '/back-office/users';
        items.push({
            title: 'Kelola Pengguna',
            href: usersPath,
            icon: Users,
            isActive:
                currentPath.value.startsWith('/back-office/users') ||
                currentPath.value.startsWith('/back-office/previews/users'),
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

    if (requiresMfaSetup.value) {
        return items.map((item) => ({
            ...item,
            isLocked: true,
            lockReason:
                'Fitur operasional ditangguhkan hingga 2FA berhasil dikonfirmasi.',
        }));
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

            <!-- Restricted Access Notice in Sidebar -->
            <div
                v-if="requiresMfaSetup"
                class="mx-2 my-1 rounded-xl border border-amber-300/70 bg-gradient-to-br from-amber-500/15 via-orange-500/10 to-amber-500/15 p-2.5 shadow-2xs group-data-[collapsible=icon]:hidden dark:border-amber-600/40 dark:from-amber-950/40 dark:to-orange-950/30"
            >
                <div
                    class="flex items-center gap-1.5 text-xs font-bold text-amber-800 dark:text-amber-300"
                >
                    <ShieldAlert
                        class="size-4 shrink-0 text-amber-600 dark:text-amber-400"
                    />
                    <span>Akses Terbatas</span>
                </div>
                <p class="mt-1 text-[11px] leading-tight text-muted-foreground">
                    Menu dikunci. Wajib verifikasi 2FA untuk admin.
                </p>
                <button
                    type="button"
                    class="mt-2 flex w-full items-center justify-center gap-1.5 rounded-lg bg-amber-600 px-2 py-1.5 text-[11px] font-semibold text-white shadow-2xs transition-colors hover:bg-amber-700 dark:bg-amber-600 dark:hover:bg-amber-500"
                    @click="startTour"
                >
                    <HelpCircle class="size-3.5" />
                    <span>Panduan Aktivasi</span>
                </button>
            </div>
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
