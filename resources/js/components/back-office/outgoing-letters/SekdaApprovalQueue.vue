<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Building2, FileCheck2, Landmark } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { SekdaApprovalQueueItem } from '@/types';

defineProps<{ items: SekdaApprovalQueueItem[] }>();
</script>

<template>
    <section
        v-if="items.length"
        class="overflow-hidden rounded-3xl border border-violet-200 bg-violet-50/35 shadow-sm dark:border-violet-950 dark:bg-violet-950/15"
        aria-labelledby="sekda-approval-queue-heading"
    >
        <header
            class="flex flex-wrap items-center justify-between gap-4 border-b border-violet-200/70 bg-background/60 px-5 py-4 sm:px-6 dark:border-violet-950"
        >
            <div class="flex items-center gap-3">
                <span
                    class="grid size-10 place-items-center rounded-2xl bg-violet-700 text-white"
                    ><Landmark class="size-5"
                /></span>
                <div>
                    <p
                        class="text-xs font-semibold tracking-[0.16em] text-violet-700 uppercase dark:text-violet-300"
                    >
                        Meja Sekda
                    </p>
                    <h2
                        id="sekda-approval-queue-heading"
                        class="mt-0.5 text-lg font-semibold"
                    >
                        Menunggu pengesahan surat
                    </h2>
                </div>
            </div>
            <Badge class="rounded-full bg-violet-700"
                >{{ items.length }} surat bernomor</Badge
            >
        </header>
        <div class="divide-y divide-violet-200/70 dark:divide-violet-950">
            <article
                v-for="item in items"
                :key="item.public_id"
                class="grid gap-4 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center"
            >
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge variant="outline" class="rounded-full"
                            ><Building2 class="mr-1 size-3.5" />{{
                                item.originating_unit_name
                            }}</Badge
                        ><Badge variant="outline" class="rounded-full"
                            ><FileCheck2 class="mr-1 size-3.5" />{{
                                item.outgoing_number
                            }}</Badge
                        >
                    </div>
                    <h3 class="mt-3 leading-snug font-semibold">
                        {{ item.subject }}
                    </h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Kepada {{ item.recipient_name }} · PDF bernomor siap
                        diperiksa
                    </p>
                </div>
                <Button
                    v-if="item.approval_url"
                    as-child
                    class="min-h-11 rounded-xl"
                    ><Link :href="item.approval_url"
                        >Buka meja pengesahan
                        <ArrowRight class="size-4" /></Link
                ></Button>
            </article>
        </div>
    </section>
</template>
