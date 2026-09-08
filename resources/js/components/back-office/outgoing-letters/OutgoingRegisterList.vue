<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Building2, CalendarDays, Inbox, UserRoundCheck } from '@lucide/vue';
import OutgoingLetterStatusBadge from '@/components/back-office/outgoing-letters/OutgoingLetterStatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { OutgoingLetterListItem, OutgoingLetterStatus } from '@/types';

defineProps<{ letters: OutgoingLetterListItem[] }>();
defineEmits<{ reset: [] }>();

const stageOrder: OutgoingLetterStatus[] = [
    'AUTHORIZED',
    'NUMBER_ASSIGNED',
    'SIGNED_DOCUMENT_UPLOADED',
    'ADMIN_VERIFIED',
    'DELIVERED',
];

function stageIndex(status: OutgoingLetterStatus): number {
    return stageOrder.indexOf(status);
}

function formatDate(value: string | null): string {
    if (!value) {
        return 'Belum ditetapkan';
    }

    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'medium' }).format(new Date(value));
}
</script>

<template>
    <section class="overflow-hidden rounded-3xl border bg-card shadow-sm" aria-labelledby="outgoing-register-heading">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4 sm:px-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-muted-foreground">Antrean penerbitan</p>
                <h2 id="outgoing-register-heading" class="mt-1 text-lg font-semibold tracking-tight">Surat yang perlu ditindaklanjuti</h2>
            </div>
            <Badge variant="outline" class="rounded-full">{{ letters.length }} ditampilkan</Badge>
        </header>

        <div v-if="letters.length" class="divide-y">
            <article v-for="letter in letters" :key="letter.public_id" class="group grid gap-4 p-5 transition-colors hover:bg-muted/25 sm:p-6 xl:grid-cols-[minmax(0,1fr)_17rem_12rem] xl:items-center">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <OutgoingLetterStatusBadge :status="letter.status" />
                        <Badge variant="outline" class="rounded-full">{{ letter.source === 'ONLINE' ? 'Online' : 'Manual' }}</Badge>
                    </div>
                    <h3 class="mt-3 text-base font-semibold leading-snug sm:text-lg">{{ letter.subject }}</h3>
                    <div class="mt-3 grid gap-2 text-xs text-muted-foreground sm:grid-cols-2">
                        <p class="flex min-w-0 items-center gap-2"><Building2 class="size-3.5 shrink-0" /><span class="truncate">{{ letter.sender_organization_name }}</span></p>
                        <p class="flex items-center gap-2"><Inbox class="size-3.5 shrink-0" />Agenda masuk {{ letter.incoming_agenda_number }}</p>
                        <p class="flex items-center gap-2"><UserRoundCheck class="size-3.5 shrink-0" /><span class="truncate">{{ letter.signatory_position }} · {{ letter.signatory_name ?? 'Pejabat aktif' }}</span></p>
                        <p class="flex items-center gap-2"><CalendarDays class="size-3.5 shrink-0" />{{ formatDate(letter.letter_date) }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border bg-background p-4">
                    <div class="flex items-center justify-between text-[11px] font-medium text-muted-foreground">
                        <span>Mandat</span><span>Pengiriman</span>
                    </div>
                    <div class="mt-3 flex items-center" aria-label="Progres penerbitan">
                        <template v-for="index in 5" :key="index">
                            <span class="size-2.5 shrink-0 rounded-full border-2" :class="letter.status === 'WITHDRAWN' ? 'border-slate-400 bg-slate-300' : index - 1 <= stageIndex(letter.status) ? 'border-indigo-600 bg-indigo-600' : 'border-border bg-background'" />
                            <span v-if="index < 5" class="h-0.5 flex-1" :class="letter.status !== 'WITHDRAWN' && index <= stageIndex(letter.status) ? 'bg-indigo-600' : 'bg-border'" />
                        </template>
                    </div>
                    <p class="mt-3 truncate font-mono text-xs font-semibold">{{ letter.outgoing_number ?? 'Nomor belum diberikan' }}</p>
                </div>

                <div class="flex items-center justify-between gap-3 xl:flex-col xl:items-stretch">
                    <p class="text-xs font-medium text-muted-foreground xl:text-center">{{ letter.next_action_label ?? (letter.status === 'WITHDRAWN' ? 'Mandat dihentikan' : 'Proses selesai') }}</p>
                    <Button as-child class="min-h-11 rounded-xl">
                        <Link :href="letter.links.detail">Buka detail <ArrowRight class="size-4" /></Link>
                    </Button>
                </div>
            </article>
        </div>

        <div v-else class="grid min-h-72 place-items-center p-8 text-center">
            <div class="max-w-sm">
                <span class="mx-auto grid size-12 place-items-center rounded-2xl bg-muted text-muted-foreground"><Inbox class="size-5" /></span>
                <h3 class="mt-4 font-semibold">Tidak ada surat pada filter ini</h3>
                <p class="mt-2 text-sm leading-6 text-muted-foreground">Ubah pencarian atau tampilkan kembali seluruh status penerbitan.</p>
                <Button type="button" variant="outline" class="mt-5 rounded-xl" @click="$emit('reset')">Tampilkan semua</Button>
            </div>
        </div>

        <footer v-if="$slots.pagination" class="border-t bg-muted/15 px-5 py-4 sm:px-6">
            <slot name="pagination" />
        </footer>
    </section>
</template>
