<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Files } from '@lucide/vue';

const page = usePage();
</script>

<template>
    <div class="flex min-w-0 items-center gap-3">
        <span
            class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-[0_10px_30px_-12px_rgba(21,94,88,0.75)]"
            aria-hidden="true"
        >
            <Files class="size-5" :stroke-width="1.8" />
        </span>
        <span class="min-w-0 leading-tight">
            <span class="block truncate text-sm font-semibold tracking-tight">
                {{ page.props.name }}
            </span>
            <span class="block truncate text-xs text-muted-foreground">
                Layanan surat online
            </span>
        </span>
    </div>
</template>
