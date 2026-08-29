<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Building2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';

defineProps<{
    eyebrow: string;
    title: string;
    description: string;
    secondaryHref: string;
    secondaryLabel: string;
}>();
</script>

<template>
    <header
        class="relative overflow-hidden rounded-3xl border bg-gradient-to-br from-blue-50 via-background to-violet-50 p-6 shadow-sm sm:p-8 dark:from-blue-950/35 dark:to-violet-950/25"
    >
        <div
            class="absolute -top-20 right-0 size-56 rounded-full bg-violet-300/20 blur-3xl dark:bg-violet-700/15"
            aria-hidden="true"
        />
        <div
            class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
        >
            <div class="max-w-3xl">
                <p
                    class="mb-3 flex items-center gap-2 text-xs font-semibold tracking-[0.18em] text-blue-700 uppercase dark:text-blue-300"
                >
                    <Building2 class="size-4" aria-hidden="true" />
                    {{ eyebrow }}
                </p>
                <h1 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                    {{ title }}
                </h1>
                <p
                    class="mt-3 max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base"
                >
                    {{ description }}
                </p>
            </div>
            <Button
                as-child
                variant="outline"
                class="min-h-11 shrink-0 bg-background/80"
            >
                <Link :href="secondaryHref">
                    {{ secondaryLabel }}
                    <ArrowRight class="size-4" aria-hidden="true" />
                </Link>
            </Button>
        </div>
    </header>
</template>
