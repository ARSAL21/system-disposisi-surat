<script setup lang="ts">
import {
    BadgeCheck,
    CircleAlert,
    FilePenLine,
    FileUp,
    Hash,
    RotateCcw,
    Send,
    ShieldCheck,
    ShieldX,
} from '@lucide/vue';
import { computed } from 'vue';
import type { Component } from 'vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { OutgoingLetterDetail, OutgoingLetterUiAction } from '@/types';

const props = defineProps<{ letter: OutgoingLetterDetail }>();
const emit = defineEmits<{ action: [action: OutgoingLetterUiAction] }>();

const primaryAction = computed<{
    label: string;
    description: string;
    icon: Component;
    action: OutgoingLetterUiAction;
} | null>(() => {
    const routes = props.letter.routes;
    const capabilities = props.letter.capabilities;

    if (capabilities.can_assign_number && routes.assign_number) {
        return {
            label: 'Berikan nomor resmi',
            description: 'Catat nomor agenda keluar dan tanggal surat.',
            icon: Hash,
            action: { kind: 'assign_number', route: routes.assign_number },
        };
    }

    if (
        capabilities.can_upload_signed_document &&
        routes.upload_signed_document &&
        ['NUMBER_ASSIGNED', 'SIGNED_DOCUMENT_UPLOADED'].includes(
            props.letter.status,
        )
    ) {
        return {
            label:
                props.letter.status === 'NUMBER_ASSIGNED'
                    ? 'Unggah PDF bertanda tangan'
                    : 'Unggah versi perbaikan',
            description: 'Simpan dokumen final sebagai versi immutable.',
            icon: FileUp,
            action: {
                kind: 'upload_signed_document',
                route: routes.upload_signed_document,
            },
        };
    }

    if (capabilities.can_verify && routes.verify) {
        return {
            label: 'Verifikasi dokumen final',
            description: 'Nyatakan PDF sesuai nomor dan mandat.',
            icon: BadgeCheck,
            action: { kind: 'verify', route: routes.verify },
        };
    }

    if (capabilities.can_deliver && routes.deliver) {
        return {
            label:
                props.letter.source === 'ONLINE'
                    ? 'Publikasikan ke portal'
                    : 'Catat penyerahan',
            description: 'Selesaikan pengiriman dan simpan buktinya.',
            icon: Send,
            action: {
                kind: 'deliver',
                route: routes.deliver,
                source: props.letter.source ?? 'MANUAL',
            },
        };
    }

    return null;
});

function requestRevision(): void {
    if (props.letter.routes.request_document_revision) {
        emit('action', {
            kind: 'request_document_revision',
            route: props.letter.routes.request_document_revision,
        });
    }
}

function withdraw(): void {
    if (props.letter.routes.withdraw) {
        emit('action', {
            kind: 'withdraw',
            route: props.letter.routes.withdraw,
        });
    }
}

function createCorrection(): void {
    if (props.letter.routes.create_correction) {
        emit('action', {
            kind: 'create_correction',
            route: props.letter.routes.create_correction,
        });
    }
}
</script>

<template>
    <Card class="overflow-hidden">
        <CardHeader class="border-b bg-muted/20 pb-4"
            ><CardTitle class="flex items-center gap-2 text-base"
                ><ShieldCheck class="size-4 text-indigo-600" /> Meja
                tindakan</CardTitle
            ></CardHeader
        >
        <CardContent class="p-5">
            <div
                v-if="primaryAction"
                class="rounded-2xl border border-indigo-200 bg-indigo-50/60 p-4 dark:border-indigo-900 dark:bg-indigo-950/25"
            >
                <span
                    class="grid size-11 place-items-center rounded-xl bg-indigo-600 text-white"
                    ><component :is="primaryAction.icon" class="size-5"
                /></span>
                <h3 class="mt-4 font-semibold">{{ primaryAction.label }}</h3>
                <p class="mt-1 text-sm leading-6 text-muted-foreground">
                    {{ primaryAction.description }}
                </p>
                <Button
                    type="button"
                    class="mt-4 min-h-11 w-full rounded-xl"
                    @click="emit('action', primaryAction.action)"
                    >{{ primaryAction.label }}</Button
                >
            </div>

            <Alert
                v-else-if="letter.status === 'DELIVERED'"
                class="border-emerald-200 bg-emerald-50/60 dark:border-emerald-900 dark:bg-emerald-950/25"
            >
                <ShieldCheck class="size-4" /><AlertTitle
                    >Penerbitan selesai</AlertTitle
                ><AlertDescription
                    >Dokumen dan bukti pengiriman terkunci. Koreksi harus dibuat
                    sebagai surat baru dengan nomor baru.</AlertDescription
                >
                <Button
                    v-if="
                        letter.capabilities.can_create_correction &&
                        letter.routes.create_correction
                    "
                    type="button"
                    variant="outline"
                    class="mt-4 w-full rounded-xl"
                    @click="createCorrection"
                    ><FilePenLine class="size-4" /> Buat surat koreksi</Button
                >
            </Alert>
            <Alert v-else-if="letter.status === 'WITHDRAWN'"
                ><ShieldX class="size-4" /><AlertTitle
                    >Mandat telah ditarik</AlertTitle
                ><AlertDescription
                    >Surat ini menjadi histori read-only dan tidak dapat diberi
                    nomor.</AlertDescription
                ></Alert
            >
            <Alert v-else
                ><CircleAlert class="size-4" /><AlertTitle
                    >Menunggu pihak berwenang</AlertTitle
                ><AlertDescription
                    >Akun ini dapat melihat proses, tetapi tidak memiliki
                    tindakan pada tahap sekarang.</AlertDescription
                ></Alert
            >

            <div
                v-if="
                    letter.capabilities.can_request_document_revision &&
                    letter.routes.request_document_revision
                "
                class="mt-4 border-t pt-4"
            >
                <Button
                    type="button"
                    variant="outline"
                    class="w-full rounded-xl"
                    @click="requestRevision"
                    ><RotateCcw class="size-4" /> Minta perbaikan PDF</Button
                >
            </div>
            <div
                v-if="
                    letter.capabilities.can_withdraw && letter.routes.withdraw
                "
                class="mt-4 border-t pt-4"
            >
                <Button
                    type="button"
                    variant="ghost"
                    class="w-full rounded-xl text-destructive hover:text-destructive"
                    @click="withdraw"
                    ><ShieldX class="size-4" /> Tarik mandat sebelum
                    penomoran</Button
                >
            </div>
        </CardContent>
    </Card>
</template>
