<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import DashboardSummaryGrid from '@/components/public/dashboard/DashboardSummaryGrid.vue';
import PublicDashboardWelcome from '@/components/public/dashboard/PublicDashboardWelcome.vue';
import RecentSubmissionsPanel from '@/components/public/dashboard/RecentSubmissionsPanel.vue';
import SubmissionGuidePanel from '@/components/public/dashboard/SubmissionGuidePanel.vue';
import publicRoutes from '@/routes/public';
import type { LetterSubmission, PublicDashboardSummary } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard Publik',
                href: publicRoutes.dashboard(),
            },
        ],
    },
});

defineProps<{
    summary: PublicDashboardSummary;
    recentSubmissions: LetterSubmission[];
}>();

const page = usePage();
const firstName = computed(
    () => page.props.auth.user.name.trim().split(/\s+/)[0] || 'Pengguna',
);
</script>

<template>
    <Head title="Dashboard Publik" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
        <PublicDashboardWelcome :user-name="firstName" />
        <DashboardSummaryGrid :summary="summary" />
        <div class="grid min-w-0 gap-5 xl:grid-cols-[minmax(0,1fr)_22rem]">
            <RecentSubmissionsPanel :submissions="recentSubmissions" />
            <SubmissionGuidePanel />
        </div>
    </div>
</template>
