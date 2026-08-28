<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import { Toaster } from '@/components/ui/sonner';
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
    <a
        href="#main-content"
        class="fixed top-3 left-3 z-[100] -translate-y-20 rounded-lg bg-primary px-4 py-3 text-sm font-semibold text-primary-foreground shadow-lg transition-transform focus:translate-y-0"
    >
        Lewati ke konten utama
    </a>
    <AppShell variant="sidebar">
        <slot name="sidebar" />
        <AppContent
            id="main-content"
            variant="sidebar"
            class="min-w-0 overflow-x-clip"
            tabindex="-1"
        >
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
