<script setup lang="ts">
import BackOfficeSidebarLayout from '@/layouts/back-office/BackOfficeSidebarLayout.vue';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);
</script>

<template>
    <BackOfficeSidebarLayout :breadcrumbs="breadcrumbs">
        <slot />
    </BackOfficeSidebarLayout>
</template>
