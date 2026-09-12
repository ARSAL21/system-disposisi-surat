<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, FileCheck2, Landmark, ScrollText } from '@lucide/vue';
import ResponseStatusBadge from '@/components/back-office/letter-responses/ResponseStatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { LetterResponseDossier } from '@/types';

defineProps<{
    dossier: LetterResponseDossier;
    backHref: string;
}>();
</script>

<template>
    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <Button
                as-child
                variant="ghost"
                class="-ml-3 rounded-full text-muted-foreground"
                ><Link :href="backHref"
                    ><ArrowLeft class="mr-2 size-4" /> Kembali ke dossier</Link
                ></Button
            >
            <div class="flex items-center gap-2">
                <Badge variant="outline" class="gap-1.5 rounded-full"
                    ><Landmark class="size-3.5" />
                    {{ dossier.viewer.display_name }}</Badge
                ><ResponseStatusBadge :status="dossier.status" />
            </div>
        </div>
        <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            <div class="min-w-0">
                <p
                    class="flex items-center gap-2 text-xs font-semibold tracking-[0.18em] text-indigo-600 uppercase dark:text-indigo-300"
                >
                    <ScrollText class="size-4" /> M8.2 · Dossier balasan
                </p>
                <h1
                    class="mt-2 max-w-4xl text-2xl font-semibold tracking-tight sm:text-3xl"
                >
                    {{ dossier.letter.subject }}
                </h1>
                <p
                    class="mt-2 max-w-3xl text-sm leading-6 text-muted-foreground"
                >
                    {{ dossier.letter.sender_organization_name }} · diterima
                    {{ dossier.letter.received_at.slice(0, 10) }}
                </p>
            </div>
            <div class="rounded-2xl border bg-background px-4 py-3 shadow-sm">
                <p class="text-xs text-muted-foreground">Nomor agenda</p>
                <p
                    class="mt-1 flex items-center gap-2 font-mono text-sm font-semibold"
                >
                    <FileCheck2 class="size-4 text-indigo-600" />
                    {{ dossier.letter.agenda_number }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{
                        dossier.letter.source === 'MANUAL'
                            ? 'Surat manual'
                            : 'Pengajuan online'
                    }}
                </p>
            </div>
        </div>
    </div>
</template>
