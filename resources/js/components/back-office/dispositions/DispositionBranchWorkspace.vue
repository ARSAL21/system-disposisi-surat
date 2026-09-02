<script setup lang="ts">
import {
    CheckCircle2,
    CircleAlert,
    Clock3,
    LockKeyhole,
    Play,
    Route,
    ShieldCheck,
    Sparkles,
    UserRoundCheck,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import CompleteDispositionBranchDialog from '@/components/back-office/dispositions/CompleteDispositionBranchDialog.vue';
import DispositionBranchJournal from '@/components/back-office/dispositions/DispositionBranchJournal.vue';
import DispositionBranchTimeline from '@/components/back-office/dispositions/DispositionBranchTimeline.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import {
    dispositionRecipientStatusClass,
    dispositionRecipientStatusLabels,
    formatRoutingDateTime,
} from '@/lib/letterRoutingPresentation';
import type {
    AddDispositionFollowUpPayload,
    CompleteDispositionBranchPayload,
    DispositionBranchCapabilities,
    DispositionBranchLifecycle,
} from '@/types';

const props = defineProps<{
    branch: DispositionBranchLifecycle;
    positionName: string;
    holderName: string | null;
    capabilities: DispositionBranchCapabilities;
    processingAction?: 'start' | 'follow_up' | 'complete' | null;
    errors?: Record<string, string>;
}>();

const emit = defineEmits<{
    start: [];
    addFollowUp: [payload: AddDispositionFollowUpPayload];
    complete: [payload: CompleteDispositionBranchPayload];
}>();

const completionDialogOpen = ref(false);

watch(
    () => props.branch.status,
    (status) => {
        if (status === 'COMPLETED') {
            completionDialogOpen.value = false;
        }
    },
);

const stageCopy = computed(() => {
    if (props.branch.status === 'PENDING') {
        return {
            eyebrow: 'Siap dikerjakan',
            title: 'Ambil kendali atas cabang Anda',
            description:
                'Mulai penanganan untuk membuka jurnal kerja, atau selesaikan langsung bila tindak lanjut telah tuntas di luar sistem.',
            tone: 'from-amber-100/80 via-background to-blue-100/55 dark:from-amber-950/30 dark:via-background dark:to-blue-950/25',
        };
    }

    if (props.branch.status === 'IN_PROGRESS') {
        return {
            eyebrow: 'Penanganan aktif',
            title: 'Dokumentasikan progres, tuntaskan dengan jelas',
            description:
                'Catat perkembangan penting secara kronologis. Ketika pekerjaan selesai, tutup cabang dengan hasil akhir yang dapat ditinjau Asisten.',
            tone: 'from-blue-100/80 via-background to-violet-100/55 dark:from-blue-950/30 dark:via-background dark:to-violet-950/25',
        };
    }

    return {
        eyebrow: 'Histori final',
        title: 'Cabang telah diselesaikan',
        description:
            'Seluruh jejak kerja dipertahankan sebagai catatan read-only dan tidak dapat dibuka kembali pada workflow MVP.',
        tone: 'from-emerald-100/80 via-background to-teal-100/55 dark:from-emerald-950/30 dark:via-background dark:to-teal-950/25',
    };
});

const hasAnyAction = computed(
    () =>
        props.capabilities.can_start_branch ||
        props.capabilities.can_add_follow_up ||
        props.capabilities.can_complete_branch,
);
</script>

<template>
    <section
        class="overflow-hidden rounded-[1.75rem] border bg-card shadow-sm"
        aria-labelledby="branch-workspace-title"
    >
        <header
            class="relative overflow-hidden border-b bg-gradient-to-br p-5 sm:p-7 lg:p-8"
            :class="stageCopy.tone"
        >
            <div
                class="pointer-events-none absolute -top-24 -right-20 size-64 rounded-full bg-white/30 blur-3xl dark:bg-white/5"
                aria-hidden="true"
            />
            <div
                class="relative flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
            >
                <div class="flex max-w-3xl items-start gap-4">
                    <span
                        class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-slate-950 text-white shadow-md dark:bg-slate-100 dark:text-slate-950"
                    >
                        <Route class="size-6" aria-hidden="true" />
                    </span>
                    <div>
                        <p
                            class="text-xs font-bold tracking-[0.18em] text-muted-foreground uppercase"
                        >
                            {{ stageCopy.eyebrow }}
                        </p>
                        <h2
                            id="branch-workspace-title"
                            class="mt-2 text-xl leading-tight font-semibold tracking-tight sm:text-2xl"
                        >
                            {{ stageCopy.title }}
                        </h2>
                        <p
                            class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground"
                        >
                            {{ stageCopy.description }}
                        </p>
                    </div>
                </div>

                <div
                    class="flex w-full flex-col gap-3 rounded-2xl border bg-background/80 p-4 shadow-xs backdrop-blur-sm sm:w-auto sm:min-w-72"
                >
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-xs font-medium text-muted-foreground">
                            Status cabang
                        </span>
                        <Badge
                            variant="outline"
                            :class="
                                dispositionRecipientStatusClass(branch.status)
                            "
                        >
                            {{
                                dispositionRecipientStatusLabels[branch.status]
                            }}
                        </Badge>
                    </div>
                    <div class="border-t pt-3">
                        <p class="font-semibold">{{ positionName }}</p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ holderName ?? 'Pejabat aktif tidak tersedia' }}
                        </p>
                    </div>
                </div>
            </div>
        </header>

        <div class="grid gap-6 p-5 sm:p-7 lg:p-8">
            <DispositionBranchTimeline :branch="branch" />

            <Alert
                v-if="errors?.branch || errors?.status"
                variant="destructive"
                aria-live="assertive"
            >
                <CircleAlert class="size-4" aria-hidden="true" />
                <AlertTitle>Tindakan belum dapat diproses</AlertTitle>
                <AlertDescription>
                    {{ errors.branch ?? errors.status }}
                </AlertDescription>
            </Alert>

            <div
                class="grid items-start gap-6 xl:grid-cols-[minmax(0,1.25fr)_minmax(19rem,0.65fr)]"
            >
                <DispositionBranchJournal
                    :branch="branch"
                    :can-add="capabilities.can_add_follow_up"
                    :processing="processingAction === 'follow_up'"
                    :error="errors?.note"
                    @add="emit('addFollowUp', $event)"
                />

                <aside class="grid gap-4 xl:sticky xl:top-6">
                    <div
                        v-if="branch.status === 'PENDING'"
                        class="overflow-hidden rounded-3xl border bg-slate-950 text-slate-100 shadow-sm dark:bg-slate-900"
                    >
                        <div class="border-b border-white/10 p-5 sm:p-6">
                            <span
                                class="flex size-11 items-center justify-center rounded-2xl bg-blue-500 text-white"
                            >
                                <Sparkles class="size-5" aria-hidden="true" />
                            </span>
                            <h3 class="mt-4 text-lg font-semibold">
                                Tentukan langkah pertama
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-slate-300">
                                Mulai cabang untuk mencatat progres.
                                Penyelesaian langsung tetap memerlukan hasil
                                akhir.
                            </p>
                        </div>
                        <div class="grid gap-3 p-5 sm:p-6">
                            <Button
                                v-if="capabilities.can_start_branch"
                                type="button"
                                class="min-h-12 bg-blue-600 text-white hover:bg-blue-500"
                                :disabled="Boolean(processingAction)"
                                @click="emit('start')"
                            >
                                <Spinner v-if="processingAction === 'start'" />
                                <Play
                                    v-else
                                    class="size-4"
                                    aria-hidden="true"
                                />
                                {{
                                    processingAction === 'start'
                                        ? 'Memulai...'
                                        : 'Mulai penanganan'
                                }}
                            </Button>
                            <Button
                                v-if="capabilities.can_complete_branch"
                                type="button"
                                variant="outline"
                                class="min-h-12 border-white/20 bg-white/5 text-white hover:bg-white/10 hover:text-white"
                                :disabled="Boolean(processingAction)"
                                @click="completionDialogOpen = true"
                            >
                                <CheckCircle2
                                    class="size-4"
                                    aria-hidden="true"
                                />
                                Selesaikan langsung
                            </Button>
                        </div>
                    </div>

                    <div
                        v-else-if="branch.status === 'IN_PROGRESS'"
                        class="rounded-3xl border border-blue-200 bg-gradient-to-br from-blue-50 via-background to-violet-50 p-5 shadow-sm sm:p-6 dark:border-blue-900 dark:from-blue-950/30 dark:via-background dark:to-violet-950/20"
                    >
                        <span
                            class="flex size-11 items-center justify-center rounded-2xl bg-blue-700 text-white shadow-sm"
                        >
                            <Clock3 class="size-5" aria-hidden="true" />
                        </span>
                        <h3 class="mt-4 text-lg font-semibold">
                            Cabang sedang berjalan
                        </h3>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            Pastikan perkembangan penting telah tercatat sebelum
                            menutup cabang ini.
                        </p>
                        <Button
                            v-if="capabilities.can_complete_branch"
                            type="button"
                            class="mt-5 min-h-12 w-full bg-emerald-700 hover:bg-emerald-800"
                            :disabled="Boolean(processingAction)"
                            @click="completionDialogOpen = true"
                        >
                            <CheckCircle2 class="size-4" aria-hidden="true" />
                            Selesaikan cabang
                        </Button>
                    </div>

                    <div
                        v-else
                        class="overflow-hidden rounded-3xl border border-emerald-200 bg-emerald-50/55 shadow-sm dark:border-emerald-900 dark:bg-emerald-950/20"
                    >
                        <div class="p-5 sm:p-6">
                            <span
                                class="flex size-11 items-center justify-center rounded-2xl bg-emerald-600 text-white"
                            >
                                <CheckCircle2
                                    class="size-5"
                                    aria-hidden="true"
                                />
                            </span>
                            <h3 class="mt-4 text-lg font-semibold">
                                Hasil penyelesaian
                            </h3>
                            <p
                                class="mt-3 text-sm leading-6 whitespace-pre-wrap"
                            >
                                {{ branch.completion_note }}
                            </p>
                        </div>
                        <div
                            v-if="branch.completed_by"
                            class="border-t border-emerald-200 bg-background/70 p-5 dark:border-emerald-900"
                        >
                            <div class="flex items-start gap-3">
                                <UserRoundCheck
                                    class="mt-0.5 size-5 shrink-0 text-emerald-700 dark:text-emerald-300"
                                    aria-hidden="true"
                                />
                                <div>
                                    <p class="font-semibold">
                                        {{ branch.completed_by.name }}
                                    </p>
                                    <p
                                        class="mt-1 text-xs leading-5 text-muted-foreground"
                                    >
                                        {{ branch.completed_by.position }}
                                    </p>
                                    <p
                                        class="mt-2 text-xs font-medium tabular-nums"
                                    >
                                        {{
                                            formatRoutingDateTime(
                                                branch.completed_at,
                                            )
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <Alert
                        v-if="!hasAnyAction && branch.status !== 'COMPLETED'"
                    >
                        <LockKeyhole class="size-4" aria-hidden="true" />
                        <AlertTitle>Mode baca saja</AlertTitle>
                        <AlertDescription>
                            Aksi lifecycle belum tersedia untuk assignment atau
                            environment ini.
                        </AlertDescription>
                    </Alert>

                    <p
                        class="flex items-start gap-2 px-1 text-xs leading-5 text-muted-foreground"
                    >
                        <ShieldCheck
                            class="mt-0.5 size-4 shrink-0"
                            aria-hidden="true"
                        />
                        Status surat keseluruhan ditentukan backend dari seluruh
                        cabang, bukan dari tampilan ini.
                    </p>
                </aside>
            </div>
        </div>
    </section>

    <CompleteDispositionBranchDialog
        v-model:open="completionDialogOpen"
        :direct-completion="branch.status === 'PENDING'"
        :processing="processingAction === 'complete'"
        :error="errors?.completion_note"
        @confirm="emit('complete', $event)"
    />
</template>
