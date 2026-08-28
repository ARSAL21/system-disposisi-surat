<script setup lang="ts">
import type { LucideIcon } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';

defineProps<{
    title: string;
    description: string;
    icon: LucideIcon;
}>();
</script>

<template>
    <section
        class="mx-auto w-full max-w-5xl"
        aria-labelledby="back-office-module-title"
    >
        <Card
            class="relative min-h-72 overflow-hidden rounded-2xl border-blue-100 bg-gradient-to-br from-white via-blue-50/70 to-violet-50 py-0 shadow-sm dark:border-blue-900/40 dark:from-slate-950 dark:via-blue-950/40 dark:to-violet-950/30"
        >
            <div
                aria-hidden="true"
                class="absolute -top-24 -right-16 size-64 rounded-full bg-violet-400/15 blur-3xl"
            />
            <CardContent
                class="relative flex min-h-72 items-center p-6 sm:p-10"
            >
                <div class="max-w-2xl">
                    <span
                        class="mb-5 flex size-12 items-center justify-center rounded-2xl bg-primary/10 text-primary ring-1 ring-primary/10"
                    >
                        <component :is="icon" class="size-6" />
                    </span>
                    <Badge variant="outline" class="mb-4 bg-background/70">
                        Fondasi modul
                    </Badge>
                    <h1
                        id="back-office-module-title"
                        class="text-2xl font-semibold tracking-tight sm:text-3xl"
                    >
                        {{ title }}
                    </h1>
                    <p class="mt-3 max-w-xl leading-7 text-muted-foreground">
                        {{ description }}
                    </p>
                    <p class="mt-6 text-sm font-medium text-primary">
                        Route dan authorization server-side telah aktif.
                    </p>
                </div>
            </CardContent>
        </Card>
    </section>
</template>
