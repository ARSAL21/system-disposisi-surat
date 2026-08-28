<script setup lang="ts">
import AppSidebar from '@/components/AppSidebar.vue';
import SidebarShell from '@/layouts/app/SidebarShell.vue';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <SidebarShell :breadcrumbs="breadcrumbs">
        <template #sidebar>
            <AppSidebar />
        </template>
        <slot />
    </SidebarShell>
</template>
