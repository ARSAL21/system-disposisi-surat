<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import InternalDashboardWelcome from '@/components/back-office/dashboard/InternalDashboardWelcome.vue';
import backOffice from '@/routes/back-office';

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

const page = usePage();
const firstName = computed(
    () => page.props.auth.user.name.trim().split(/\s+/)[0] || 'Staf',
);
</script>

<template>
    <Head title="Dashboard Internal" />

    <div class="flex flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
        <InternalDashboardWelcome :user-name="firstName" />
    </div>
</template>
