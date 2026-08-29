<script setup lang="ts">
import {
    BriefcaseBusiness,
    Building2,
    CircleUserRound,
    Layers3,
} from '@lucide/vue';

defineProps<{
    items: Array<{
        label: string;
        value: number;
        kind: 'levels' | 'units' | 'positions' | 'occupied';
    }>;
}>();

const icons = {
    levels: Layers3,
    units: Building2,
    positions: BriefcaseBusiness,
    occupied: CircleUserRound,
};
</script>

<template>
    <section
        class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4"
        aria-label="Ringkasan organisasi"
    >
        <article
            v-for="item in items"
            :key="item.label"
            class="flex items-center gap-4 rounded-2xl border bg-card p-4 shadow-sm"
        >
            <span
                class="grid size-11 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300"
            >
                <component
                    :is="icons[item.kind]"
                    class="size-5"
                    aria-hidden="true"
                />
            </span>
            <div>
                <p class="text-2xl font-semibold tracking-tight tabular-nums">
                    {{ item.value }}
                </p>
                <p class="text-sm text-muted-foreground">{{ item.label }}</p>
            </div>
        </article>
    </section>
</template>
