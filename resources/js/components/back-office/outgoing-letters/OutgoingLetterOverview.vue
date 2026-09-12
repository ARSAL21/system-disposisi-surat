<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    Building2,
    CalendarDays,
    FileInput,
    Gavel,
    UserRoundCheck,
} from '@lucide/vue';
import OutgoingLetterStatusBadge from '@/components/back-office/outgoing-letters/OutgoingLetterStatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { OutgoingLetterDetail } from '@/types';

defineProps<{ letter: OutgoingLetterDetail }>();

function formatDate(value: string): string {
    return new Intl.DateTimeFormat('id-ID', { dateStyle: 'long' }).format(
        new Date(value),
    );
}
</script>

<template>
    <div class="grid gap-5 xl:grid-cols-2">
        <Card>
            <CardHeader class="pb-3"
                ><CardTitle class="flex items-center gap-2 text-base"
                    ><FileInput class="size-4 text-indigo-600" />
                    {{
                        letter.origin === 'RESPONSE'
                            ? 'Surat masuk sumber'
                            : 'Asal surat mandiri'
                    }}</CardTitle
                ></CardHeader
            >
            <CardContent v-if="letter.incoming_letter" class="space-y-4">
                <div>
                    <p class="text-xs text-muted-foreground">Perihal awal</p>
                    <p class="mt-1 leading-snug font-semibold">
                        {{ letter.incoming_letter.subject }}
                    </p>
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl bg-muted/35 p-3">
                        <p class="text-xs text-muted-foreground">
                            Agenda masuk
                        </p>
                        <p class="mt-1 font-mono text-xs font-semibold">
                            {{ letter.incoming_letter.agenda_number }}
                        </p>
                    </div>
                    <div class="rounded-xl bg-muted/35 p-3">
                        <p class="text-xs text-muted-foreground">Diterima</p>
                        <p class="mt-1 text-sm font-semibold">
                            {{ formatDate(letter.incoming_letter.received_at) }}
                        </p>
                    </div>
                </div>
                <p
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Building2 class="size-4 shrink-0" />{{
                        letter.incoming_letter.sender_organization_name
                    }}
                </p>
                <Button
                    v-if="letter.incoming_letter.dossier_url"
                    as-child
                    variant="outline"
                    class="w-full rounded-xl"
                    ><Link :href="letter.incoming_letter.dossier_url"
                        >Buka dossier penyusunan
                        <ArrowUpRight class="size-4" /></Link
                ></Button>
            </CardContent>
            <CardContent v-else class="space-y-4">
                <div>
                    <p class="text-xs text-muted-foreground">Unit penyusun</p>
                    <p class="mt-1 font-semibold">
                        {{ letter.standalone_draft?.originating_unit_name }}
                    </p>
                </div>
                <div class="rounded-xl bg-muted/35 p-3">
                    <p class="text-xs text-muted-foreground">Penerima</p>
                    <p class="mt-1 text-sm font-semibold">
                        {{ letter.standalone_draft?.recipient_name }}
                    </p>
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ letter.standalone_draft?.template_name }} · template
                    versi {{ letter.standalone_draft?.template_version_number }}
                </p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader
                class="flex flex-row items-start justify-between gap-3 pb-3"
            >
                <CardTitle class="flex items-center gap-2 text-base"
                    ><Gavel class="size-4 text-indigo-600" />
                    {{
                        letter.origin === 'RESPONSE'
                            ? 'Mandat penerbitan'
                            : 'Persetujuan penerbitan'
                    }}</CardTitle
                >
                <OutgoingLetterStatusBadge :status="letter.status" />
            </CardHeader>
            <CardContent class="space-y-4">
                <div>
                    <p class="text-xs text-muted-foreground">
                        Perihal surat balasan
                    </p>
                    <p class="mt-1 leading-snug font-semibold">
                        {{ letter.subject }}
                    </p>
                </div>
                <div class="rounded-xl border bg-muted/20 p-3">
                    <p class="text-xs text-muted-foreground">
                        Sumber substansi
                    </p>
                    <p class="mt-1 text-sm font-semibold">
                        {{ letter.mandate.source_document_title }} · versi
                        {{ letter.mandate.source_version_number }}
                    </p>
                </div>
                <div class="grid gap-3 text-sm sm:grid-cols-2">
                    <p class="flex items-start gap-2">
                        <UserRoundCheck
                            class="mt-0.5 size-4 shrink-0 text-emerald-600"
                        /><span
                            ><span class="block text-xs text-muted-foreground"
                                >Penandatangan</span
                            >{{ letter.signatory.position_name
                            }}<span
                                v-if="letter.signatory.official_name"
                                class="mt-0.5 block text-xs text-muted-foreground"
                                >{{ letter.signatory.official_name }}</span
                            ></span
                        >
                    </p>
                    <p v-if="letter.mandate" class="flex items-start gap-2">
                        <CalendarDays
                            class="mt-0.5 size-4 shrink-0 text-indigo-600"
                        /><span
                            ><span class="block text-xs text-muted-foreground"
                                >Diotorisasi</span
                            >{{ formatDate(letter.mandate.authorized_at)
                            }}<span
                                class="mt-0.5 block text-xs text-muted-foreground"
                                >{{ letter.mandate.authorized_position }}</span
                            ></span
                        >
                    </p>
                </div>
                <Badge variant="outline" class="rounded-full">{{
                    letter.origin === 'RESPONSE'
                        ? letter.source === 'ONLINE'
                            ? 'Pengiriman portal'
                            : 'Penyerahan offline'
                        : 'Menunggu pengesahan Sekda'
                }}</Badge>
            </CardContent>
        </Card>
    </div>
</template>
