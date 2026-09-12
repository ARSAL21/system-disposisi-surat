<script setup lang="ts">
import { CalendarDays, FileCheck2, Landmark, UserRound } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import type { SekdaApprovalDetail } from '@/types';

defineProps<{ approval: SekdaApprovalDetail }>();

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(
        new Date(value),
    );
}
</script>

<template>
    <header class="overflow-hidden rounded-3xl border bg-card shadow-sm">
        <div
            class="grid gap-6 p-5 sm:p-7 lg:grid-cols-[minmax(0,1fr)_20rem] lg:items-end"
        >
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <Badge class="rounded-full bg-indigo-600"
                        >Pengesahan Sekda</Badge
                    ><Badge variant="outline" class="rounded-full">{{
                        approval.originating_unit_name
                    }}</Badge>
                </div>
                <h1
                    class="mt-4 text-2xl font-semibold tracking-tight sm:text-3xl"
                >
                    {{ approval.subject }}
                </h1>
                <p
                    class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 font-mono text-sm text-muted-foreground"
                >
                    <FileCheck2 class="size-4 text-indigo-600" />{{
                        approval.outgoing_number
                    }}<span aria-hidden="true">·</span
                    >{{ formatDate(approval.letter_date) }}
                </p>
            </div>
            <div class="rounded-2xl border bg-muted/25 p-4 text-sm">
                <p class="flex items-center gap-2 font-semibold">
                    <Landmark class="size-4 text-indigo-600" /> Keputusan
                    pejabat
                </p>
                <p class="mt-2 leading-6 text-muted-foreground">
                    Pengesahan QR adalah persetujuan elektronik tercatat, bukan
                    TTE tersertifikasi.
                </p>
            </div>
        </div>
        <dl
            class="grid divide-y border-t bg-muted/15 sm:grid-cols-3 sm:divide-x sm:divide-y-0"
        >
            <div class="p-4 sm:px-6">
                <dt
                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                >
                    <UserRound class="size-3.5" />Penerima
                </dt>
                <dd class="mt-1 font-semibold">
                    {{ approval.recipient.name }}
                </dd>
                <dd
                    v-if="approval.recipient.organization"
                    class="mt-0.5 text-xs text-muted-foreground"
                >
                    {{ approval.recipient.organization }}
                </dd>
            </div>
            <div class="p-4 sm:px-6">
                <dt
                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                >
                    <CalendarDays class="size-3.5" />Tembusan
                </dt>
                <dd class="mt-1 text-sm font-semibold">
                    {{
                        approval.copy_recipients.length
                            ? approval.copy_recipients.join(', ')
                            : 'Tidak ada tembusan'
                    }}
                </dd>
            </div>
            <div class="p-4 sm:px-6">
                <dt class="text-xs text-muted-foreground">Status saat ini</dt>
                <dd class="mt-1 text-sm font-semibold">
                    {{
                        approval.status === 'SEKDA_REVIEW'
                            ? 'Menunggu pengesahan Sekda'
                            : approval.status === 'AWAITING_MANUAL_SIGNATURE'
                              ? 'Menunggu tanda tangan fisik'
                              : approval.status === 'MANUAL_SCAN_REVIEW'
                                ? 'Scan menunggu pemeriksaan'
                                : approval.status === 'READY_FOR_DELIVERY'
                                  ? 'Siap dikirim'
                                  : 'Perlu diperbaiki'
                    }}
                </dd>
            </div>
        </dl>
    </header>
</template>
