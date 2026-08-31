<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Building2, Clock3, FileText, Send } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    dispositionRecipientStatusClass,
    dispositionRecipientStatusLabels,
    formatRoutingDateTime,
} from '@/lib/letterRoutingPresentation';
import type { DispositionInboxItem } from '@/types';

defineProps<{ dispositions: DispositionInboxItem[] }>();
</script>

<template>
    <div class="grid gap-3 p-3 lg:hidden">
        <article
            v-for="disposition in dispositions"
            :key="disposition.recipient_id"
            class="rounded-2xl border bg-card p-4 shadow-xs"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <Badge
                    variant="outline"
                    :class="dispositionRecipientStatusClass(disposition.status)"
                >
                    {{ dispositionRecipientStatusLabels[disposition.status] }}
                </Badge>
                <span class="font-mono text-xs text-muted-foreground">
                    {{ disposition.letter.agenda_number }}
                </span>
            </div>

            <div class="mt-4 flex gap-3">
                <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-blue-700 dark:text-blue-300"
                >
                    <FileText class="size-4" aria-hidden="true" />
                </span>
                <h2 class="leading-6 font-semibold">
                    {{ disposition.letter.subject }}
                </h2>
            </div>

            <dl class="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                <div class="flex gap-3">
                    <Building2
                        class="mt-0.5 size-4 shrink-0 text-violet-600 dark:text-violet-300"
                        aria-hidden="true"
                    />
                    <div>
                        <dt class="text-xs text-muted-foreground">
                            Asal surat
                        </dt>
                        <dd class="mt-1 font-medium">
                            {{ disposition.letter.sender_organization_name }}
                        </dd>
                    </div>
                </div>
                <div class="flex gap-3">
                    <Clock3
                        class="mt-0.5 size-4 shrink-0 text-blue-600 dark:text-blue-300"
                        aria-hidden="true"
                    />
                    <div>
                        <dt class="text-xs text-muted-foreground">Diterima</dt>
                        <dd class="mt-1 font-medium tabular-nums">
                            {{ formatRoutingDateTime(disposition.received_at) }}
                        </dd>
                    </div>
                </div>
            </dl>

            <div class="mt-4 flex items-start gap-3 rounded-xl bg-muted/55 p-3">
                <Send
                    class="mt-0.5 size-4 shrink-0 text-emerald-700 dark:text-emerald-300"
                    aria-hidden="true"
                />
                <div>
                    <p class="text-xs text-muted-foreground">Dikirim oleh</p>
                    <p class="mt-1 font-semibold">
                        {{ disposition.sender.name }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ disposition.sender.position }}
                    </p>
                </div>
            </div>

            <ul
                class="mt-4 flex flex-wrap gap-2"
                aria-label="Instruksi disposisi"
            >
                <li
                    v-for="instruction in disposition.instructions"
                    :key="instruction.code"
                >
                    <Badge variant="secondary">{{ instruction.name }}</Badge>
                </li>
            </ul>

            <Button as-child class="mt-5 min-h-11 w-full">
                <Link :href="disposition.links.show">
                    Buka dan periksa disposisi
                    <ArrowRight class="size-4" aria-hidden="true" />
                </Link>
            </Button>
        </article>
    </div>
</template>
