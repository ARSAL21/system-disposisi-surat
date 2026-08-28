<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppSidebarLayout from '@/layouts/app/AppSidebarLayout.vue';
import BackOfficeLayout from '@/layouts/back-office/BackOfficeLayout.vue';
import type { BreadcrumbItem } from '@/types';

const { breadcrumbs = [] } = defineProps<{
    breadcrumbs?: BreadcrumbItem[];
}>();

const page = usePage();
const isInternalAccount = computed(
    () => page.props.auth.user.account_type === 'INTERNAL',
);
</script>

<template>
    <BackOfficeLayout v-if="isInternalAccount" :breadcrumbs="breadcrumbs">
        <slot />
    </BackOfficeLayout>
    <AppSidebarLayout v-else :breadcrumbs="breadcrumbs">
        <slot />
    </AppSidebarLayout>
</template>
