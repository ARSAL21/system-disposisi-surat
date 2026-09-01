<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    CalendarClock,
    Crown,
    FileText,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    formatRoutingDateTime,
    letterRoutingStatusClass,
    letterRoutingStatusLabels,
} from '@/lib/letterRoutingPresentation';
import type { LetterRoutingItem } from '@/types';

defineProps<{ letters: LetterRoutingItem[] }>();
</script>

<template>
    <div class="grid gap-3 p-3 lg:hidden">
        <article
            v-for="letter in letters"
            :key="letter.id"
            class="rounded-2xl border bg-card p-4 shadow-xs"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <Badge
                    variant="outline"
                    :class="letterRoutingStatusClass(letter.status)"
                >
                    {{ letterRoutingStatusLabels[letter.status] }}
                </Badge>
                <span class="font-mono text-xs text-muted-foreground">
                    {{ letter.agenda_number }}
                </span>
            </div>

            <div class="mt-4 flex gap-3">
                <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-blue-700 dark:text-blue-300"
                >
                    <FileText class="size-4" aria-hidden="true" />
                </span>
                <h2 class="leading-6 font-semibold">{{ letter.subject }}</h2>
            </div>

            <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                <div class="flex gap-3">
                    <Building2
                        class="mt-0.5 size-4 shrink-0 text-violet-600 dark:text-violet-300"
                        aria-hidden="true"
                    />
                    <div>
                        <dt class="text-xs text-muted-foreground">Pengirim</dt>
                        <dd class="mt-1 font-medium">
                            {{ letter.sender_organization_name }}
                        </dd>
                    </div>
                </div>
                <div class="flex gap-3">
                    <CalendarClock
                        class="mt-0.5 size-4 shrink-0 text-blue-600 dark:text-blue-300"
                        aria-hidden="true"
                    />
                    <div>
                        <dt class="text-xs text-muted-foreground">Diterima</dt>
                        <dd class="mt-1 font-medium tabular-nums">
                            {{ formatRoutingDateTime(letter.received_at) }}
                        </dd>
                    </div>
                </div>
            </dl>

            <div
                v-if="letter.current_route"
                class="mt-4 flex items-start gap-3 rounded-xl border border-violet-200 bg-violet-50/65 p-3 dark:border-violet-900 dark:bg-violet-950/25"
            >
                <Crown
                    class="mt-0.5 size-4 shrink-0 text-violet-700 dark:text-violet-300"
                    aria-hidden="true"
                />
                <div>
                    <p class="text-xs text-muted-foreground">Tujuan routing</p>
                    <p class="mt-1 font-semibold">
                        {{ letter.current_route.target_position.name }}
                    </p>
                    <p class="mt-1 text-xs leading-5 text-muted-foreground">
                        {{ letter.current_route.target_position.holder_name }}
                    </p>
                </div>
            </div>

            <Button
                as-child
                :variant="
                    letter.status === 'REGISTERED' ? 'default' : 'outline'
                "
                class="mt-5 min-h-11 w-full"
            >
                <Link :href="letter.links.show">
                    {{
                        letter.status === 'REGISTERED'
                            ? 'Tinjau & routing'
                            : 'Lihat detail routing'
                    }}
                    <ArrowRight class="size-4" aria-hidden="true" />
                </Link>
            </Button>
        </article>
    </div>
</template>
