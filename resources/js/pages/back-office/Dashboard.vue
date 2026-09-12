<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminDashboardView from '@/components/back-office/dashboard/AdminDashboardView.vue';
import InternalDashboardWelcome from '@/components/back-office/dashboard/InternalDashboardWelcome.vue';
import backOffice from '@/routes/back-office';
import type { AdminDashboardData, IntakeDashboardData } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard Internal',
                href: backOffice.dashboard(),
            },
        ],
    },
});

const props = defineProps<{
    adminDashboard?: AdminDashboardData | null;
    intakeDashboard?: IntakeDashboardData | null;
    preview?: boolean;
}>();

const page = usePage();
const firstName = computed(
    () => page.props.auth.user.name.trim().split(/\s+/)[0] || 'Staf',
);

const hasAdminDashboard = computed(() => Boolean(props.adminDashboard));
const hasIntakeDashboard = computed(() => Boolean(props.intakeDashboard));

// Default active view: administrator gets command center first
const activeView = ref<'admin' | 'intake'>(
    hasAdminDashboard.value ? 'admin' : 'intake',
);
</script>

<template>
    <Head
        :title="
            hasAdminDashboard ? 'Dashboard Administrator' : 'Dashboard Internal'
        "
    />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
        <!-- View Switcher Tabs if user has both admin metrics and intake queue -->
        <div
            v-if="hasAdminDashboard && hasIntakeDashboard"
            class="flex w-fit items-center gap-1.5 rounded-xl border bg-muted/60 p-1 text-xs font-medium"
        >
            <button
                type="button"
                class="flex cursor-pointer items-center gap-2 rounded-lg px-4 py-2 whitespace-nowrap transition-all"
                :class="
                    activeView === 'admin'
                        ? 'bg-background font-semibold text-foreground shadow-xs'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeView = 'admin'"
            >
                <span>Pusat Kendali Administrator</span>
            </button>
            <button
                type="button"
                class="flex cursor-pointer items-center gap-2 rounded-lg px-4 py-2 whitespace-nowrap transition-all"
                :class="
                    activeView === 'intake'
                        ? 'bg-background font-semibold text-foreground shadow-xs'
                        : 'text-muted-foreground hover:text-foreground'
                "
                @click="activeView = 'intake'"
            >
                <span>Antrean Intake Persuratan</span>
            </button>
        </div>

        <AdminDashboardView
            v-if="hasAdminDashboard && activeView === 'admin'"
            :dashboard-data="adminDashboard"
            :user-name="firstName"
            :preview="preview"
        />

        <InternalDashboardWelcome
            v-else
            :user-name="firstName"
            :dashboard-data="intakeDashboard"
            :preview="preview"
        />
    </div>
</template>
