<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Building2, CalendarClock, Crown } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    formatRoutingDateTime,
    letterRoutingStatusClass,
    letterRoutingStatusLabels,
} from '@/lib/letterRoutingPresentation';
import type { LetterRoutingItem } from '@/types';

defineProps<{
    letter: LetterRoutingItem;
    backHref: string;
    backLabel: string;
    preview?: boolean;
}>();
</script>

<template>
    <header class="rounded-3xl border bg-card p-5 shadow-sm sm:p-7">
        <Button as-child variant="ghost" class="mb-4 -ml-3 min-h-11">
            <Link :href="backHref">
                <ArrowLeft class="size-4" aria-hidden="true" />
                {{ backLabel }}
            </Link>
        </Button>

        <div
            class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
        >
            <div class="max-w-3xl">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge
                        variant="outline"
                        :class="letterRoutingStatusClass(letter.status)"
                    >
                        {{ letterRoutingStatusLabels[letter.status] }}
                    </Badge>
                    <Badge v-if="preview" variant="secondary">
                        Pratinjau lokal
                    </Badge>
                    <span class="font-mono text-xs text-muted-foreground">
                        Agenda {{ letter.agenda_number }}
                    </span>
                </div>

                <h1
                    class="mt-4 text-2xl leading-tight font-semibold tracking-tight sm:text-3xl"
                >
                    {{ letter.subject }}
                </h1>

                <div
                    class="mt-4 flex flex-col gap-2 text-sm text-muted-foreground sm:flex-row sm:flex-wrap sm:gap-x-5"
                >
                    <span class="flex items-center gap-2">
                        <Building2 class="size-4" aria-hidden="true" />
                        {{ letter.sender_organization_name }}
                    </span>
                    <span class="flex items-center gap-2 tabular-nums">
                        <CalendarClock class="size-4" aria-hidden="true" />
                        Diterima {{ formatRoutingDateTime(letter.received_at) }}
                    </span>
                </div>
            </div>

            <div
                v-if="letter.current_route"
                class="flex max-w-md items-start gap-3 rounded-2xl border border-violet-200 bg-violet-50/70 p-4 text-sm dark:border-violet-900 dark:bg-violet-950/25"
            >
                <Crown
                    class="mt-0.5 size-5 shrink-0 text-violet-700 dark:text-violet-300"
                    aria-hidden="true"
                />
                <div>
                    <p class="font-semibold">
                        {{ letter.current_route.target_position.name }}
                    </p>
                    <p
                        class="mt-1 leading-6 text-violet-800 dark:text-violet-200"
                    >
                        {{ letter.current_route.target_position.holder_name }}
                    </p>
                </div>
            </div>
        </div>
    </header>
</template>
