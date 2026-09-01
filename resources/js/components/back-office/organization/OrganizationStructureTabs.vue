<script setup lang="ts">
import { BriefcaseBusiness, Building2, Layers3 } from '@lucide/vue';
import type { OrganizationStructureFilters } from '@/types';

defineProps<{ active: OrganizationStructureFilters['section'] }>();
const emit = defineEmits<{
    change: [section: OrganizationStructureFilters['section']];
}>();
const tabs = [
    { value: 'levels' as const, label: 'Level Workflow', icon: Layers3 },
    { value: 'units' as const, label: 'Unit Organisasi', icon: Building2 },
    { value: 'positions' as const, label: 'Daftar Jabatan', icon: BriefcaseBusiness },
];
</script>

<template>
    <div
        class="grid gap-2 rounded-2xl border bg-muted/35 p-2 sm:grid-cols-3"
        role="tablist"
        aria-label="Bagian struktur organisasi"
    >
        <button
            v-for="tab in tabs"
            :key="tab.value"
            type="button"
            role="tab"
            class="flex min-h-11 items-center justify-center gap-2 rounded-xl px-4 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            :class="
                active === tab.value
                    ? 'bg-background text-foreground shadow-sm'
                    : 'text-muted-foreground hover:bg-background/60 hover:text-foreground'
            "
            :aria-selected="active === tab.value"
            @click="emit('change', tab.value)"
        >
            <component :is="tab.icon" class="size-4" aria-hidden="true" />
            {{ tab.label }}
        </button>
    </div>
</template>
