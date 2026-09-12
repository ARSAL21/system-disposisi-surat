<script setup lang="ts">
import BackOfficeSidebar from '@/components/back-office/BackOfficeSidebar.vue';
import TwoFactorSweetAlert from '@/components/back-office/TwoFactorSweetAlert.vue';
import TwoFactorTourPopover from '@/components/back-office/TwoFactorTourPopover.vue';
import SidebarShell from '@/layouts/app/SidebarShell.vue';
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
    <SidebarShell :breadcrumbs="breadcrumbs">
        <template #sidebar>
            <BackOfficeSidebar />
        </template>
        <slot />
        <TwoFactorSweetAlert />
        <TwoFactorTourPopover />
    </SidebarShell>
</template>
