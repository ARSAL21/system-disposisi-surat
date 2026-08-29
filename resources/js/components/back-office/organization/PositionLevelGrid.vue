<script setup lang="ts">
import { LockKeyhole } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import type { PositionLevel } from '@/types';

defineProps<{ levels: PositionLevel[] }>();
</script>

<template>
    <section
        class="grid gap-4 md:grid-cols-2 xl:grid-cols-4"
        aria-label="Katalog level workflow"
    >
        <article
            v-for="(level, index) in levels"
            :key="level.id"
            class="relative overflow-hidden rounded-2xl border bg-card p-5 shadow-sm"
        >
            <span
                class="absolute top-0 left-0 h-1 w-full bg-gradient-to-r from-blue-500 to-violet-500"
                aria-hidden="true"
            />
            <div class="flex items-start justify-between gap-3">
                <span
                    class="grid size-10 place-items-center rounded-xl bg-blue-50 font-semibold text-blue-700 dark:bg-blue-950/50 dark:text-blue-300"
                    >{{ index + 1 }}</span
                >
                <Badge variant="outline" class="gap-1"
                    ><LockKeyhole class="size-3" aria-hidden="true" />
                    Terlindungi</Badge
                >
            </div>
            <p
                class="mt-5 font-mono text-xs text-violet-700 dark:text-violet-300"
            >
                {{ level.code }}
            </p>
            <h2 class="mt-2 font-semibold">{{ level.name }}</h2>
            <p class="mt-2 text-sm text-muted-foreground">
                {{ level.position_count }} jabatan · urutan
                {{ level.hierarchy_order }}
            </p>
        </article>
    </section>
    <p
        class="rounded-2xl border border-blue-200 bg-blue-50/60 p-4 text-sm leading-6 text-blue-900 dark:border-blue-900 dark:bg-blue-950/25 dark:text-blue-100"
    >
        Level ini merupakan invariant workflow dan hanya disinkronkan melalui
        <code class="rounded bg-background/70 px-1.5 py-0.5 font-mono text-xs"
            >organization:sync-levels</code
        >. UI tidak dapat mengubah kode, urutan, atau statusnya.
    </p>
</template>
