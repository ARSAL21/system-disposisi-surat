<script setup lang="ts">
import {
    CheckCircle2,
    CircleDotDashed,
    Clock3,
    FileText,
    Landmark,
    Network,
    Play,
    Send,
    Upload,
    UserRound,
} from '@lucide/vue';
import { computed } from 'vue';
import ResponseStatusBadge from '@/components/back-office/letter-responses/ResponseStatusBadge.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type {
    LetterResponseAssistantBranch,
    LetterResponseDossier,
    LetterResponseSectionBranch,
    LetterResponseSelection,
    LetterResponseUiAction,
} from '@/types';

const props = defineProps<{
    dossier: LetterResponseDossier;
    selected?: LetterResponseSelection | null;
}>();

const emit = defineEmits<{
    select: [selection: LetterResponseSelection];
    action: [action: LetterResponseUiAction];
}>();

const selectedId = computed(() => {
    if (!props.selected) {
        return null;
    }

    if (props.selected.kind === 'executive') {
        return 'executive';
    }

    return props.selected.branch.public_id;
});

function isSelected(id: string): boolean {
    return selectedId.value === id;
}

function selectExecutive(): void {
    emit('select', { kind: 'executive', dossier: props.dossier });
}

function selectAssistant(branch: LetterResponseAssistantBranch): void {
    emit('select', { kind: 'assistant', branch });
}

function selectSection(branch: LetterResponseSectionBranch): void {
    emit('select', { kind: 'section', branch });
}

function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

const assistantGridClass = computed(() => {
    if (props.dossier.assistants.length <= 1) {
        return 'max-w-md grid-cols-1';
    }

    if (props.dossier.assistants.length === 2) {
        return 'max-w-4xl md:grid-cols-2';
    }

    return 'max-w-[85rem] md:grid-cols-2 xl:grid-cols-3';
});

const assistantBusClass = computed(() =>
    props.dossier.assistants.length === 2
        ? 'left-1/4 right-1/4'
        : 'left-[16.666%] right-[16.666%]',
);

function nodeBorderTone(id: string, status?: string): string {
    if (isSelected(id)) {
        return 'border-indigo-500 ring-2 ring-indigo-500/20 bg-indigo-50/70 dark:border-indigo-500 dark:bg-indigo-950/30';
    }

    if (status === 'COMPLETED') {
        return 'border-emerald-200/90 bg-emerald-50/30 hover:border-emerald-300 dark:border-emerald-900/60 dark:bg-emerald-950/15';
    }

    if (status === 'IN_PROGRESS') {
        return 'border-sky-200/90 bg-sky-50/30 hover:border-sky-300 dark:border-sky-900/60 dark:bg-sky-950/15';
    }

    return 'border-border bg-card hover:border-indigo-300 hover:shadow-sm dark:bg-slate-900/80';
}
</script>

<template>
    <section
        class="min-w-0 overflow-hidden rounded-2xl border bg-muted/15 shadow-sm"
        aria-labelledby="response-tree-heading"
    >
        <!-- Card Header with Legend -->
        <header
            class="flex flex-col gap-4 border-b bg-background px-4 py-4 sm:flex-row sm:items-start sm:justify-between sm:px-6"
        >
            <div class="min-w-0">
                <p
                    class="flex items-center gap-2 text-xs font-semibold tracking-[0.14em] text-indigo-600 uppercase dark:text-indigo-300"
                >
                    <Network class="size-4" /> Pohon tanggung jawab balasan
                </p>
                <h2
                    id="response-tree-heading"
                    class="mt-1 text-lg font-semibold tracking-tight"
                >
                    {{ dossier.letter.agenda_number }} · Struktur Mandat &
                    Kontribusi
                </h2>
                <p
                    class="mt-1 max-w-2xl text-xs leading-5 text-muted-foreground"
                >
                    Alur vertikal: Pimpinan menetapkan mandat, Asisten
                    mengoordinasikan telaah, dan Kepala Bagian menyusun bahan
                    teknis.
                </p>
            </div>

            <!-- Status Legend & Counter Badge -->
            <div class="flex flex-wrap items-center gap-3">
                <div
                    class="flex items-center gap-x-3 rounded-xl border bg-muted/30 px-3 py-1.5 text-[11px] text-muted-foreground"
                    aria-label="Keterangan status"
                >
                    <span class="inline-flex items-center gap-1">
                        <CircleDotDashed class="size-3 text-amber-500" />
                        Menunggu
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <Play class="size-3 text-sky-500" /> Dikerjakan
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <CheckCircle2 class="size-3 text-emerald-500" /> Selesai
                    </span>
                </div>
                <Badge variant="outline" class="rounded-full">
                    {{ dossier.assistants.length }} Asisten ·
                    {{ dossier.progress.total }} cabang
                </Badge>
            </div>
        </header>

        <!-- Vertical Tree Content -->
        <div class="p-4 sm:p-6 lg:p-8">
            <div
                class="mx-auto min-w-0"
                role="tree"
                :aria-label="`Pohon tanggung jawab ${dossier.letter.agenda_number}`"
            >
                <!-- Level 1: Pimpinan (Executive Root Node) -->
                <div
                    class="mx-auto max-w-md"
                    role="treeitem"
                    :aria-selected="isSelected('executive')"
                >
                    <button
                        type="button"
                        class="group w-full rounded-2xl border p-4 text-left shadow-xs transition-[border-color,box-shadow,transform,background-color] duration-200 hover:-translate-y-0.5 focus-visible:ring-3 focus-visible:ring-indigo-500/35 focus-visible:outline-none"
                        :class="
                            nodeBorderTone(
                                'executive',
                                dossier.status === 'OPEN'
                                    ? 'IN_PROGRESS'
                                    : 'COMPLETED',
                            )
                        "
                        @click="selectExecutive"
                    >
                        <div class="flex items-start gap-3">
                            <span
                                class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-xl bg-slate-950 text-white shadow-xs dark:bg-white dark:text-slate-950"
                            >
                                <Landmark class="size-4" />
                            </span>
                            <div class="min-w-0 flex-1">
                                <div
                                    class="flex items-start justify-between gap-2"
                                >
                                    <p
                                        class="text-[10px] font-bold tracking-[0.13em] text-muted-foreground uppercase"
                                    >
                                        Pimpinan
                                    </p>
                                    <ResponseStatusBadge
                                        :status="
                                            dossier.status === 'OPEN'
                                                ? 'IN_PROGRESS'
                                                : 'COMPLETED'
                                        "
                                    />
                                </div>
                                <h3
                                    class="mt-1 text-sm leading-5 font-semibold text-foreground"
                                >
                                    {{ dossier.executive.position_name }}
                                </h3>
                                <p
                                    class="mt-0.5 truncate text-xs text-muted-foreground"
                                >
                                    {{
                                        dossier.executive.official_name ??
                                        'Pejabat penerima'
                                    }}
                                </p>
                            </div>
                        </div>

                        <!-- Executive Progress Bar -->
                        <div class="mt-3.5 border-t pt-3">
                            <div
                                class="flex items-center justify-between gap-3 text-[11px]"
                            >
                                <span class="text-muted-foreground"
                                    >Progres seluruh cabang</span
                                >
                                <strong
                                    class="font-semibold text-indigo-700 tabular-nums dark:text-indigo-300"
                                >
                                    {{ dossier.progress.completed }}/{{
                                        dossier.progress.total
                                    }}
                                    cabang ({{ dossier.progress.percent }}%)
                                </strong>
                            </div>
                            <div
                                class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full rounded-full bg-indigo-600 transition-all duration-300"
                                    :style="{
                                        width: `${dossier.progress.percent}%`,
                                    }"
                                />
                            </div>
                        </div>

                        <!-- Timing & Sub-action -->
                        <div
                            class="mt-3 flex items-center justify-between border-t pt-2.5 text-[11px] text-muted-foreground"
                        >
                            <span class="inline-flex items-center gap-1">
                                <Clock3 class="size-3" /> Dibuka:
                                {{ formatDate(dossier.opened_at) }}
                            </span>
                            <span
                                class="font-medium text-indigo-600 dark:text-indigo-300"
                            >
                                Mandat substantif &rarr;
                            </span>
                        </div>
                    </button>
                </div>

                <!-- Stem 1: Pimpinan down to Cabang Asisten -->
                <div
                    v-if="dossier.assistants.length"
                    class="relative mx-auto h-10 w-px bg-indigo-300 dark:bg-indigo-700"
                    aria-hidden="true"
                >
                    <span
                        class="absolute bottom-0 left-1/2 size-3 -translate-x-1/2 translate-y-1/2 rounded-full border-2 border-indigo-500 bg-background ring-4 ring-indigo-100 dark:ring-indigo-950"
                    />
                </div>

                <!-- Level 2: Cabang Asisten Pillars Grid -->
                <ol
                    v-if="dossier.assistants.length"
                    class="relative mx-auto grid gap-8 pt-8"
                    :class="assistantGridClass"
                    role="group"
                >
                    <!-- Horizontal Bus Bar Connecting Assistants -->
                    <li
                        v-if="dossier.assistants.length > 1"
                        class="absolute top-0 hidden h-px bg-indigo-300 md:block dark:bg-indigo-700"
                        :class="assistantBusClass"
                        aria-hidden="true"
                    />

                    <!-- Individual Assistant Pillar -->
                    <li
                        v-for="assistant in dossier.assistants"
                        :key="assistant.public_id"
                        class="relative min-w-0"
                        role="treeitem"
                    >
                        <!-- Top Stem from bus to Assistant -->
                        <div
                            class="absolute -top-8 left-1/2 h-8 w-px -translate-x-1/2 bg-indigo-300 dark:bg-indigo-700"
                            aria-hidden="true"
                        >
                            <span
                                class="absolute bottom-0 left-1/2 size-2.5 -translate-x-1/2 translate-y-1/2 rounded-full border-2 border-indigo-500 bg-background"
                            />
                        </div>

                        <!-- Assistant Pillar Enclosure -->
                        <div
                            class="rounded-2xl border border-indigo-200/80 bg-indigo-50/25 p-3.5 shadow-2xs dark:border-indigo-900/70 dark:bg-indigo-950/10"
                        >
                            <p
                                class="mb-2.5 text-center text-[10px] font-bold tracking-[0.14em] text-indigo-600 uppercase dark:text-indigo-300"
                            >
                                Cabang Asisten
                            </p>

                            <!-- Assistant Node Card -->
                            <button
                                type="button"
                                class="group w-full rounded-xl border p-3.5 text-left shadow-xs transition-[border-color,box-shadow,transform,background-color] duration-200 hover:-translate-y-0.5 focus-visible:ring-3 focus-visible:ring-indigo-500/35 focus-visible:outline-none"
                                :class="
                                    nodeBorderTone(
                                        assistant.public_id,
                                        assistant.status,
                                    )
                                "
                                @click="selectAssistant(assistant)"
                            >
                                <div class="flex items-start gap-3">
                                    <span
                                        class="mt-0.5 grid size-9 shrink-0 place-items-center rounded-xl bg-indigo-600 text-white shadow-xs"
                                    >
                                        <UserRound class="size-4" />
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="flex items-start justify-between gap-2"
                                        >
                                            <p
                                                class="text-[10px] font-bold tracking-[0.13em] text-muted-foreground uppercase"
                                            >
                                                Asisten
                                            </p>
                                            <ResponseStatusBadge
                                                :status="assistant.status"
                                            />
                                        </div>
                                        <h3
                                            class="mt-1 text-sm leading-5 font-semibold text-foreground"
                                        >
                                            {{ assistant.position_name }}
                                        </h3>
                                        <p
                                            class="mt-0.5 truncate text-xs text-muted-foreground"
                                        >
                                            {{
                                                assistant.official_name ??
                                                assistant.unit_name
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Assistant Progress Bar -->
                                <div class="mt-3 border-t pt-2.5">
                                    <div
                                        class="flex items-center justify-between gap-2 text-[11px]"
                                    >
                                        <span class="text-muted-foreground"
                                            >Progres Bagian</span
                                        >
                                        <span
                                            class="font-semibold text-indigo-700 tabular-nums dark:text-indigo-300"
                                        >
                                            {{
                                                assistant.child_progress
                                                    .completed
                                            }}/{{
                                                assistant.child_progress.total
                                            }}
                                            ({{
                                                assistant.child_progress
                                                    .percent
                                            }}%)
                                        </span>
                                    </div>
                                    <div
                                        class="mt-1.5 h-1.5 overflow-hidden rounded-full bg-muted"
                                    >
                                        <div
                                            class="h-full rounded-full bg-indigo-500 transition-all duration-300"
                                            :style="{
                                                width: `${assistant.child_progress.percent}%`,
                                            }"
                                        />
                                    </div>
                                </div>

                                <!-- Timings -->
                                <div
                                    class="mt-2.5 flex items-center justify-between border-t pt-2 text-[10px] text-muted-foreground"
                                >
                                    <span
                                        class="inline-flex items-center gap-1"
                                    >
                                        <Clock3 class="size-3" /> Diteruskan
                                    </span>
                                    <span class="font-medium text-foreground">
                                        {{ formatDate(assistant.forwarded_at) }}
                                    </span>
                                </div>
                            </button>

                            <!-- Assistant Proposal CTA or Preview -->
                            <div
                                v-if="assistant.proposal"
                                class="mt-3 rounded-xl border border-amber-200/90 bg-amber-50/70 p-3 dark:border-amber-900/80 dark:bg-amber-950/20"
                            >
                                <div
                                    class="flex items-center justify-between gap-2"
                                >
                                    <span
                                        class="flex items-center gap-1.5 text-xs font-semibold text-amber-900 dark:text-amber-200"
                                    >
                                        <Send class="size-3.5 text-amber-600" />
                                        Usulan Balasan · v{{
                                            assistant.proposal.current_version
                                                ?.version_number
                                        }}
                                    </span>
                                    <Badge
                                        variant="outline"
                                        class="border-amber-300 bg-amber-100/60 text-[10px] text-amber-800 dark:border-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
                                    >
                                        Siap ditelaah
                                    </Badge>
                                </div>
                                <p
                                    class="mt-1 truncate text-xs text-amber-900/80 dark:text-amber-100/70"
                                >
                                    {{
                                        assistant.proposal.current_version
                                            ?.original_filename
                                    }}
                                </p>
                                <div class="mt-2.5 flex flex-wrap gap-2">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        class="h-7 rounded-lg bg-background text-xs"
                                        @click="selectAssistant(assistant)"
                                    >
                                        Tinjau di Inspektor &rarr;
                                    </Button>
                                </div>
                            </div>

                            <div
                                v-else-if="
                                    assistant.can_submit_proposal &&
                                    assistant.routes?.proposal_store
                                "
                                class="mt-3"
                            >
                                <Button
                                    type="button"
                                    size="sm"
                                    class="w-full rounded-xl text-xs shadow-xs"
                                    @click="
                                        emit('action', {
                                            kind: 'upload',
                                            route: assistant.routes
                                                .proposal_store,
                                            title: 'Buat proposal Asisten',
                                            description:
                                                'Unggah usulan balasan setelah seluruh bahan teknis dipelajari.',
                                        })
                                    "
                                >
                                    <Send class="mr-1.5 size-3.5" /> Buat
                                    proposal balasan
                                </Button>
                            </div>
                        </div>

                        <!-- Stem 2: Asisten down to Kepala Bagian -->
                        <div
                            v-if="assistant.children.length"
                            class="relative mx-auto h-8 w-px bg-teal-300 dark:bg-teal-700"
                            aria-hidden="true"
                        >
                            <span
                                class="absolute bottom-0 left-1/2 size-3 -translate-x-1/2 translate-y-1/2 rounded-full border-2 border-teal-500 bg-background ring-4 ring-teal-100 dark:ring-teal-950"
                            />
                        </div>

                        <!-- Level 3: Kepala Bagian Vertical List -->
                        <ol
                            v-if="assistant.children.length"
                            class="relative space-y-3 pt-4"
                            role="group"
                        >
                            <li
                                v-for="section in assistant.children"
                                :key="section.public_id"
                                class="relative min-w-0"
                                role="treeitem"
                                :aria-selected="isSelected(section.public_id)"
                            >
                                <!-- Section Connector Stem -->
                                <div
                                    class="relative mx-auto -mb-1 h-3 w-px bg-teal-300 dark:bg-teal-700"
                                    aria-hidden="true"
                                >
                                    <span
                                        class="absolute bottom-0 left-1/2 size-2 -translate-x-1/2 translate-y-1/2 rounded-full border-2 border-teal-500 bg-background"
                                    />
                                </div>

                                <!-- Section Node Card -->
                                <button
                                    type="button"
                                    class="group w-full rounded-xl border p-3.5 text-left shadow-xs transition-[border-color,box-shadow,transform,background-color] duration-200 hover:-translate-y-0.5 focus-visible:ring-3 focus-visible:ring-indigo-500/35 focus-visible:outline-none"
                                    :class="
                                        nodeBorderTone(
                                            section.public_id,
                                            section.status,
                                        )
                                    "
                                    @click="selectSection(section)"
                                >
                                    <div class="flex items-start gap-3">
                                        <span
                                            class="mt-0.5 grid size-8 shrink-0 place-items-center rounded-lg bg-teal-600 text-white shadow-xs"
                                        >
                                            <UserRound class="size-3.5" />
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="flex items-start justify-between gap-2"
                                            >
                                                <p
                                                    class="text-[10px] font-bold tracking-[0.13em] text-muted-foreground uppercase"
                                                >
                                                    Kepala Bagian
                                                </p>
                                                <ResponseStatusBadge
                                                    :status="section.status"
                                                />
                                            </div>
                                            <h4
                                                class="mt-1 text-xs leading-4 font-semibold text-foreground"
                                            >
                                                {{ section.position_name }}
                                            </h4>
                                            <p
                                                class="mt-0.5 truncate text-[11px] text-muted-foreground"
                                            >
                                                {{
                                                    section.official_name ??
                                                    section.unit_name
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Section Timing -->
                                    <div
                                        class="mt-2.5 flex items-center justify-between border-t pt-2 text-[10px] text-muted-foreground"
                                    >
                                        <span
                                            class="inline-flex items-center gap-1"
                                        >
                                            <Clock3 class="size-3" /> Aktivitas
                                        </span>
                                        <span
                                            class="font-medium text-foreground"
                                        >
                                            {{
                                                formatDate(
                                                    section.completed_at ??
                                                        section.started_at ??
                                                        section.received_at,
                                                )
                                            }}
                                        </span>
                                    </div>

                                    <!-- Attached Technical Material -->
                                    <div
                                        v-if="section.material"
                                        class="mt-2 flex items-center gap-1.5 rounded-lg border border-indigo-200/80 bg-indigo-50/60 px-2 py-1 text-[11px] text-indigo-700 dark:border-indigo-900/60 dark:bg-indigo-950/30 dark:text-indigo-300"
                                    >
                                        <FileText
                                            class="size-3 shrink-0 text-indigo-600"
                                        />
                                        <span class="truncate font-medium">
                                            {{
                                                section.material.current_version
                                                    ?.original_filename
                                            }}
                                        </span>
                                        <Badge
                                            variant="secondary"
                                            class="ml-auto shrink-0 px-1 py-0 text-[9px] font-semibold"
                                        >
                                            v{{
                                                section.material.current_version
                                                    ?.version_number
                                            }}
                                        </Badge>
                                    </div>
                                </button>

                                <!-- Upload CTA if eligible -->
                                <div
                                    v-if="
                                        section.can_upload_material &&
                                        section.routes?.material_store
                                    "
                                    class="mt-1.5 pl-2"
                                >
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        class="h-7 w-full rounded-lg text-xs"
                                        @click="
                                            emit('action', {
                                                kind: 'upload',
                                                route: section.routes
                                                    .material_store,
                                                title: 'Unggah bahan teknis',
                                                description: `Tambahkan PDF hasil telaah teknis ${section.position_name}. Dokumen akan disimpan sebagai versi immutable.`,
                                            })
                                        "
                                    >
                                        <Upload class="mr-1.5 size-3" /> Unggah
                                        bahan teknis
                                    </Button>
                                </div>
                            </li>
                        </ol>

                        <!-- Empty state if assistant has no child sections -->
                        <div
                            v-else
                            class="mt-3 rounded-xl border border-dashed p-4 text-center text-xs text-muted-foreground"
                        >
                            Belum diteruskan ke Kepala Bagian.
                        </div>
                    </li>
                </ol>

                <div
                    v-else
                    class="rounded-2xl border border-dashed p-8 text-center text-sm text-muted-foreground"
                >
                    Surat belum memiliki cabang disposisi lanjutan.
                </div>
            </div>
        </div>
    </section>
</template>
