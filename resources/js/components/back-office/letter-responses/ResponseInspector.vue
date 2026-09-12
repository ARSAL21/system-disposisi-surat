<script setup lang="ts">
import {
    Download,
    FileText,
    History,
    MessageSquareText,
    RotateCcw,
    Upload,
    UserRound,
} from '@lucide/vue';
import { computed } from 'vue';
import ResponseStatusBadge from '@/components/back-office/letter-responses/ResponseStatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import type {
    LetterResponseAssistantBranch,
    LetterResponseDossier,
    LetterResponseSectionBranch,
    LetterResponseSelection,
    LetterResponseUiAction,
} from '@/types';

const props = defineProps<{
    selection: LetterResponseSelection | null;
    dossier: LetterResponseDossier;
}>();
const emit = defineEmits<{ action: [action: LetterResponseUiAction] }>();

const title = computed(() => {
    if (!props.selection) {
        return 'Pilih bagian pohon';
    }

    if (props.selection.kind === 'executive') {
        return props.selection.dossier.executive.position_name;
    }

    return props.selection.branch.position_name;
});
const actor = computed(() => {
    if (!props.selection) {
        return 'Detail akan muncul di sini';
    }

    if (props.selection.kind === 'executive') {
        return (
            props.selection.dossier.executive.official_name ??
            'Pejabat penerima'
        );
    }

    return (
        props.selection.branch.official_name ?? props.selection.branch.unit_name
    );
});
const branch = computed<
    LetterResponseAssistantBranch | LetterResponseSectionBranch | null
>(() => {
    if (!props.selection || props.selection.kind === 'executive') {
        return null;
    }

    return props.selection.branch;
});
const status = computed(() => {
    if (!props.selection) {
        return 'PENDING' as const;
    }

    if (props.selection.kind === 'executive') {
        return props.selection.dossier.status === 'OPEN'
            ? ('IN_PROGRESS' as const)
            : ('COMPLETED' as const);
    }

    return props.selection.branch.status;
});
const documents = computed(() => {
    if (!props.selection) {
        return [];
    }

    if (props.selection.kind === 'executive') {
        return [
            ...(props.selection.dossier.consolidation
                ? [props.selection.dossier.consolidation]
                : []),
            ...props.selection.dossier.assistants.flatMap((assistant) =>
                assistant.proposal ? [assistant.proposal] : [],
            ),
        ];
    }

    if (props.selection.kind === 'assistant') {
        return props.selection.branch.proposal
            ? [props.selection.branch.proposal]
            : [];
    }

    return props.selection.branch.material
        ? [props.selection.branch.material]
        : [];
});

function formatDate(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}
function formatBytes(bytes: number): string {
    return `${(bytes / 1_048_576).toFixed(1)} MB`;
}
</script>

<template>
    <Card
        class="h-fit border-border/80 shadow-sm lg:sticky lg:top-6 lg:max-h-[calc(100vh-4rem)] lg:overflow-y-auto"
    >
        <CardHeader class="border-b bg-muted/15 pb-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p
                        class="text-xs font-semibold tracking-[0.16em] text-muted-foreground uppercase"
                    >
                        Inspektor
                    </p>
                    <CardTitle class="mt-1 truncate text-lg">{{
                        title
                    }}</CardTitle>
                    <p
                        class="mt-1 flex items-center gap-1.5 text-sm text-muted-foreground"
                    >
                        <UserRound class="size-3.5" /> {{ actor }}
                    </p>
                </div>
                <ResponseStatusBadge :status="status" />
            </div>
        </CardHeader>
        <CardContent class="space-y-5 p-4">
            <div
                v-if="!selection"
                class="rounded-xl border border-dashed bg-muted/20 p-5 text-center"
            >
                <span
                    class="mx-auto grid size-10 place-items-center rounded-xl bg-indigo-500/10 text-indigo-600"
                    ><History class="size-5"
                /></span>
                <p class="mt-3 text-sm font-medium">Belum ada kartu dipilih</p>
                <p class="mt-1 text-xs leading-5 text-muted-foreground">
                    Klik eksekutif, Asisten, atau Kepala Bagian untuk membuka
                    detail.
                </p>
            </div>
            <template v-else>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-xl bg-muted/30 p-3">
                        <p class="text-xs text-muted-foreground">Diterima</p>
                        <p class="mt-1 font-medium">
                            {{
                                formatDate(
                                    branch && 'received_at' in branch
                                        ? branch.received_at
                                        : dossier.opened_at,
                                )
                            }}
                        </p>
                    </div>
                    <div class="rounded-xl bg-muted/30 p-3">
                        <p class="text-xs text-muted-foreground">Selesai</p>
                        <p class="mt-1 font-medium">
                            {{
                                formatDate(
                                    branch && 'completed_at' in branch
                                        ? branch.completed_at
                                        : dossier.finalized_at,
                                )
                            }}
                        </p>
                    </div>
                </div>
                <div
                    v-if="
                        branch &&
                        'completion_note' in branch &&
                        branch.completion_note
                    "
                    class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-3 dark:border-indigo-900 dark:bg-indigo-950/25"
                >
                    <p
                        class="flex items-center gap-2 text-xs font-semibold text-indigo-700 dark:text-indigo-300"
                    >
                        <MessageSquareText class="size-3.5" /> Catatan
                        penyelesaian
                    </p>
                    <p
                        class="mt-2 text-sm leading-6 text-indigo-950/80 dark:text-indigo-100/80"
                    >
                        {{ branch.completion_note }}
                    </p>
                </div>
                <div v-if="documents.length" class="space-y-3">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold">Dossier dokumen</p>
                        <Badge variant="outline" class="rounded-full"
                            >{{ documents.length }} seri</Badge
                        >
                    </div>
                    <div
                        v-for="document in documents"
                        :key="document.public_id"
                        class="rounded-xl border p-3"
                    >
                        <div class="flex items-start gap-3">
                            <span
                                class="grid size-8 shrink-0 place-items-center rounded-lg bg-muted text-indigo-600"
                                ><FileText class="size-4"
                            /></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium">
                                    {{ document.title }}
                                </p>
                                <p
                                    class="mt-1 truncate text-xs text-muted-foreground"
                                >
                                    {{
                                        document.current_version
                                            ?.original_filename
                                    }}
                                </p>
                            </div>
                            <Badge variant="secondary" class="rounded-full"
                                >v{{
                                    document.current_version?.version_number ??
                                    0
                                }}</Badge
                            >
                        </div>
                        <Separator class="my-3" />
                        <div
                            v-if="document.current_version"
                            class="space-y-2 text-xs text-muted-foreground"
                        >
                            <p class="flex items-center justify-between gap-2">
                                <span>Diunggah oleh</span
                                ><span class="font-medium text-foreground">{{
                                    document.current_version.uploaded_by
                                }}</span>
                            </p>
                            <p class="flex items-center justify-between gap-2">
                                <span>Ukuran</span
                                ><span>{{
                                    formatBytes(
                                        document.current_version.size_bytes,
                                    )
                                }}</span>
                            </p>
                            <p class="flex items-center justify-between gap-2">
                                <span>Waktu</span
                                ><span>{{
                                    formatDate(
                                        document.current_version.uploaded_at,
                                    )
                                }}</span>
                            </p>
                            <p class="truncate" title="Fingerprint SHA-256">
                                SHA-256 ·
                                {{
                                    document.current_version.sha256_fingerprint
                                }}
                            </p>
                        </div>
                        <p
                            v-if="document.open_revision_reason"
                            class="mt-3 rounded-lg bg-amber-50 p-2 text-xs leading-5 text-amber-900 dark:bg-amber-950/30 dark:text-amber-100"
                        >
                            Perlu revisi: {{ document.open_revision_reason }}
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <Button
                                v-if="document.current_version?.links.preview"
                                as-child
                                size="sm"
                                variant="outline"
                                class="h-8 flex-1 rounded-lg text-xs"
                                ><a
                                    :href="
                                        document.current_version.links.preview
                                    "
                                    target="_blank"
                                    rel="noopener"
                                    >Pratinjau</a
                                ></Button
                            >
                            <Button
                                v-if="document.current_version?.links.download"
                                as-child
                                size="sm"
                                variant="ghost"
                                class="h-8 rounded-lg px-3 text-xs"
                                ><a
                                    :href="
                                        document.current_version.links.download
                                    "
                                    ><Download class="mr-1.5 size-3.5" />
                                    Unduh</a
                                ></Button
                            >
                            <Button
                                v-if="
                                    document.can_upload &&
                                    document.routes?.revision_store
                                "
                                type="button"
                                size="sm"
                                variant="outline"
                                class="h-8 rounded-lg text-xs"
                                @click="
                                    emit('action', {
                                        kind: 'upload',
                                        route: document.routes.revision_store,
                                        title: 'Unggah versi revisi',
                                        description:
                                            'Versi sebelumnya tetap tersimpan dan tidak akan ditimpa.',
                                    })
                                "
                                ><Upload class="mr-1.5 size-3.5" />
                                Revisi</Button
                            >
                            <Button
                                v-if="
                                    document.can_return &&
                                    document.routes?.return
                                "
                                type="button"
                                size="sm"
                                variant="outline"
                                class="h-8 rounded-lg text-xs"
                                @click="
                                    emit('action', {
                                        kind: 'return',
                                        route: document.routes.return,
                                        title: 'Kembalikan dokumen',
                                        description:
                                            'Jelaskan perbaikan yang perlu dilakukan oleh pemilik dokumen.',
                                    })
                                "
                                ><RotateCcw class="mr-1.5 size-3.5" />
                                Kembalikan</Button
                            >
                        </div>
                    </div>
                </div>
                <div
                    v-else
                    class="rounded-xl border border-dashed p-4 text-center text-xs leading-5 text-muted-foreground"
                >
                    Belum ada lampiran pada bagian ini.
                </div>
            </template>
        </CardContent>
    </Card>
</template>
