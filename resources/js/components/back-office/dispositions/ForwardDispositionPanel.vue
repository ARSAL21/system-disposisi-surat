<script setup lang="ts">
import {
    Check,
    ChevronRight,
    CircleHelp,
    Eraser,
    Search,
    Send,
    ShieldCheck,
    UsersRound,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ForwardDispositionConfirmationDialog from '@/components/back-office/dispositions/ForwardDispositionConfirmationDialog.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type {
    DispositionInstructionLabelOption,
    DispositionPositionOption,
    ForwardDispositionPayload,
} from '@/types';

const maximumRecipients = 50;

const props = defineProps<{
    positions: DispositionPositionOption[];
    instructionLabels: DispositionInstructionLabelOption[];
    canForward: boolean;
    processing?: boolean;
    errors?: Record<string, string>;
}>();

const emit = defineEmits<{
    confirm: [payload: ForwardDispositionPayload];
}>();

const search = ref('');
const selectedPositionIds = ref<number[]>([]);
const selectedInstructionIds = ref<number[]>([]);
const instructionNote = ref('');
const confirmationOpen = ref(false);
const localErrors = ref<Record<string, string>>({});

const availableSectionHeads = computed(() =>
    props.positions.filter(
        (position) =>
            position.level_code === 'SECTION_HEAD' &&
            position.is_available &&
            position.holder_name,
    ),
);
const searchTerm = computed(() =>
    search.value.trim().toLocaleLowerCase('id-ID'),
);
const filteredSectionHeads = computed(() => {
    if (!searchTerm.value) {
        return availableSectionHeads.value;
    }

    return availableSectionHeads.value.filter((position) =>
        [position.name, position.holder_name, position.unit_name]
            .filter((value): value is string => Boolean(value))
            .some((value) =>
                value.toLocaleLowerCase('id-ID').includes(searchTerm.value),
            ),
    );
});
const recipientGroups = computed(() => {
    const groups = new Map<string, DispositionPositionOption[]>();

    for (const position of filteredSectionHeads.value) {
        const unit = position.unit_name ?? 'Unit belum ditetapkan';
        groups.set(unit, [...(groups.get(unit) ?? []), position]);
    }

    return [...groups.entries()].map(([unit, positions]) => ({
        unit,
        positions,
    }));
});
const selectedRecipients = computed(() =>
    availableSectionHeads.value.filter((position) =>
        selectedPositionIds.value.includes(position.id),
    ),
);
const selectedInstructions = computed(() =>
    props.instructionLabels.filter((label) =>
        selectedInstructionIds.value.includes(label.id),
    ),
);
const noteLength = computed(() => instructionNote.value.length);
const remainingRecipientSlots = computed(
    () => maximumRecipients - selectedPositionIds.value.length,
);
const mergedErrors = computed(() => ({
    ...(props.errors ?? {}),
    ...localErrors.value,
}));

watch(
    availableSectionHeads,
    (positions) => {
        const availableIds = new Set(positions.map((position) => position.id));
        selectedPositionIds.value = selectedPositionIds.value.filter((id) =>
            availableIds.has(id),
        );
    },
    { immediate: true },
);

function isRecipientSelected(positionId: number): boolean {
    return selectedPositionIds.value.includes(positionId);
}

function toggleRecipient(positionId: number, selected: boolean): void {
    if (selected) {
        if (
            !selectedPositionIds.value.includes(positionId) &&
            selectedPositionIds.value.length < maximumRecipients
        ) {
            selectedPositionIds.value = [
                ...selectedPositionIds.value,
                positionId,
            ];
        }
    } else {
        selectedPositionIds.value = selectedPositionIds.value.filter(
            (id) => id !== positionId,
        );
    }

    delete localErrors.value.recipient_position_ids;
}

function clearRecipients(): void {
    selectedPositionIds.value = [];
    delete localErrors.value.recipient_position_ids;
}

function toggleInstruction(labelId: number, selected: boolean): void {
    if (selected) {
        if (!selectedInstructionIds.value.includes(labelId)) {
            selectedInstructionIds.value = [
                ...selectedInstructionIds.value,
                labelId,
            ];
        }
    } else {
        selectedInstructionIds.value = selectedInstructionIds.value.filter(
            (id) => id !== labelId,
        );
    }

    delete localErrors.value.instruction_label_ids;
}

function validateForm(): boolean {
    const nextErrors: Record<string, string> = {};

    if (selectedRecipients.value.length === 0) {
        nextErrors.recipient_position_ids =
            'Pilih sedikitnya satu Kepala Bagian penerima.';
    } else if (selectedRecipients.value.length > maximumRecipients) {
        nextErrors.recipient_position_ids = `Maksimal ${maximumRecipients} Kepala Bagian dapat dipilih.`;
    }

    if (selectedInstructionIds.value.length === 0) {
        nextErrors.instruction_label_ids =
            'Pilih sedikitnya satu instruksi disposisi.';
    } else if (selectedInstructionIds.value.length > 10) {
        nextErrors.instruction_label_ids =
            'Maksimal 10 instruksi dapat dipilih.';
    }

    if (noteLength.value > 2000) {
        nextErrors.instruction_note = 'Catatan maksimal 2.000 karakter.';
    }

    localErrors.value = nextErrors;

    return Object.keys(nextErrors).length === 0;
}

function openConfirmation(): void {
    if (!props.canForward || !validateForm()) {
        return;
    }

    confirmationOpen.value = true;
}

function confirmDisposition(): void {
    if (props.processing || !validateForm()) {
        return;
    }

    emit('confirm', {
        recipient_position_ids: [...selectedPositionIds.value],
        instruction_label_ids: [...selectedInstructionIds.value],
        instruction_note: instructionNote.value.trim(),
    });
}
</script>

<template>
    <Card
        class="overflow-hidden border-violet-200/80 bg-gradient-to-br from-violet-50/55 via-background to-blue-50/60 py-0 shadow-sm dark:border-violet-950 dark:from-violet-950/20 dark:via-background dark:to-blue-950/20"
    >
        <CardHeader
            class="border-b border-violet-100 p-5 sm:p-7 dark:border-violet-950"
        >
            <div
                class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
            >
                <div class="flex max-w-2xl items-start gap-4">
                    <span
                        class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-700 to-blue-700 text-white shadow-md"
                    >
                        <UsersRound class="size-6" aria-hidden="true" />
                    </span>
                    <div>
                        <CardTitle class="text-xl"
                            >Penerusan multi-penerima</CardTitle
                        >
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            Bentuk beberapa jalur kerja sekaligus dari cabang
                            Asisten ini. Setiap Kepala Bagian akan menerima
                            disposisi pada inbox jabatannya.
                        </p>
                    </div>
                </div>

                <div
                    class="grid grid-cols-3 overflow-hidden rounded-2xl border bg-background/80 text-center shadow-xs"
                    aria-label="Tahapan penerusan disposisi"
                >
                    <div class="min-w-20 border-r px-3 py-3">
                        <p
                            class="text-xs font-semibold text-violet-700 dark:text-violet-300"
                        >
                            01
                        </p>
                        <p
                            class="mt-1 text-[11px] leading-4 text-muted-foreground"
                        >
                            Pilih Kabag
                        </p>
                    </div>
                    <div class="min-w-20 border-r px-3 py-3">
                        <p
                            class="text-xs font-semibold text-blue-700 dark:text-blue-300"
                        >
                            02
                        </p>
                        <p
                            class="mt-1 text-[11px] leading-4 text-muted-foreground"
                        >
                            Beri arahan
                        </p>
                    </div>
                    <div class="min-w-20 px-3 py-3">
                        <p
                            class="text-xs font-semibold text-emerald-700 dark:text-emerald-300"
                        >
                            03
                        </p>
                        <p
                            class="mt-1 text-[11px] leading-4 text-muted-foreground"
                        >
                            Konfirmasi
                        </p>
                    </div>
                </div>
            </div>
        </CardHeader>

        <CardContent
            class="grid gap-6 p-5 sm:p-7 xl:grid-cols-[minmax(0,1.35fr)_minmax(22rem,0.8fr)]"
        >
            <section
                class="min-w-0"
                aria-labelledby="recipient-selection-title"
            >
                <div
                    class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
                >
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="flex size-7 items-center justify-center rounded-lg bg-violet-100 text-xs font-bold text-violet-800 dark:bg-violet-950 dark:text-violet-200"
                                >1</span
                            >
                            <h2
                                id="recipient-selection-title"
                                class="font-semibold"
                            >
                                Pilih Kepala Bagian
                            </h2>
                        </div>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            Pilih satu atau beberapa penerima yang memiliki
                            pejabat aktif.
                        </p>
                    </div>
                    <Badge
                        variant="outline"
                        class="w-fit border-violet-200 bg-violet-50/70 px-3 py-1.5 text-violet-800 dark:border-violet-900 dark:bg-violet-950/30 dark:text-violet-200"
                    >
                        {{ selectedRecipients.length }}/{{ maximumRecipients }}
                        dipilih
                    </Badge>
                </div>

                <div class="relative mt-5">
                    <Label for="section-head-search" class="sr-only">
                        Cari Kepala Bagian
                    </Label>
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        id="section-head-search"
                        v-model="search"
                        class="min-h-11 rounded-xl pl-10"
                        placeholder="Cari jabatan, unit, atau nama pejabat..."
                        autocomplete="off"
                    />
                </div>

                <div
                    v-if="selectedRecipients.length > 0"
                    class="mt-4 flex flex-wrap items-center gap-2 rounded-2xl border border-violet-200/80 bg-violet-50/45 p-3 dark:border-violet-900 dark:bg-violet-950/20"
                    aria-live="polite"
                >
                    <span
                        class="mr-1 text-xs font-semibold text-violet-800 dark:text-violet-200"
                    >
                        Jalur terpilih
                    </span>
                    <button
                        v-for="recipient in selectedRecipients"
                        :key="recipient.id"
                        type="button"
                        class="inline-flex min-h-8 items-center gap-1 rounded-full border border-violet-200 bg-background px-2.5 text-xs font-medium transition-colors hover:border-violet-400 hover:bg-violet-100 dark:border-violet-800 dark:bg-slate-950 dark:hover:bg-violet-950"
                        :aria-label="`Hapus ${recipient.name} dari pilihan`"
                        @click="toggleRecipient(recipient.id, false)"
                    >
                        {{ recipient.name }}
                        <span aria-hidden="true">×</span>
                    </button>
                    <Button
                        type="button"
                        variant="ghost"
                        class="min-h-8 px-2 text-xs text-muted-foreground"
                        @click="clearRecipients"
                    >
                        <Eraser class="size-3.5" aria-hidden="true" />
                        Bersihkan
                    </Button>
                </div>

                <div
                    class="mt-4 grid gap-5"
                    role="group"
                    aria-describedby="recipient-selection-help recipient-selection-error"
                >
                    <section
                        v-for="group in recipientGroups"
                        :key="group.unit"
                        class="rounded-2xl border bg-background/70 p-3 shadow-xs"
                        :aria-label="`Pilihan dari ${group.unit}`"
                    >
                        <p
                            class="px-1 pb-3 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            {{ group.unit }}
                        </p>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <label
                                v-for="position in group.positions"
                                :key="position.id"
                                :for="`section-head-${position.id}`"
                                class="group flex min-h-24 cursor-pointer items-start gap-3 rounded-xl border p-3 transition-colors duration-200 motion-reduce:transition-none"
                                :class="
                                    isRecipientSelected(position.id)
                                        ? 'border-violet-500 bg-violet-50/80 shadow-sm dark:border-violet-700 dark:bg-violet-950/30'
                                        : 'border-border hover:border-violet-300 hover:bg-violet-50/40 dark:hover:border-violet-800 dark:hover:bg-violet-950/15'
                                "
                            >
                                <Checkbox
                                    :id="`section-head-${position.id}`"
                                    :model-value="
                                        isRecipientSelected(position.id)
                                    "
                                    :disabled="
                                        !canForward ||
                                        Boolean(processing) ||
                                        (!isRecipientSelected(position.id) &&
                                            remainingRecipientSlots === 0)
                                    "
                                    class="mt-0.5"
                                    @update:model-value="
                                        toggleRecipient(
                                            position.id,
                                            $event === true,
                                        )
                                    "
                                />
                                <span class="min-w-0">
                                    <span class="flex items-start gap-2">
                                        <span
                                            class="block text-sm leading-5 font-semibold"
                                        >
                                            {{ position.name }}
                                        </span>
                                        <Check
                                            v-if="
                                                isRecipientSelected(position.id)
                                            "
                                            class="mt-0.5 size-4 shrink-0 text-violet-700 dark:text-violet-300"
                                            aria-hidden="true"
                                        />
                                    </span>
                                    <span
                                        class="mt-1 block text-xs leading-5 text-muted-foreground"
                                    >
                                        {{ position.holder_name }}
                                    </span>
                                </span>
                            </label>
                        </div>
                    </section>

                    <Alert
                        v-if="availableSectionHeads.length === 0"
                        variant="destructive"
                    >
                        <CircleHelp class="size-4" aria-hidden="true" />
                        <AlertTitle>Kepala Bagian belum tersedia</AlertTitle>
                        <AlertDescription>
                            Belum ada jabatan Kepala Bagian dengan satu pejabat
                            aktif yang dapat menerima disposisi.
                        </AlertDescription>
                    </Alert>

                    <div
                        v-else-if="recipientGroups.length === 0"
                        class="rounded-2xl border border-dashed p-5 text-center"
                    >
                        <p class="font-medium">Tidak ada hasil pencarian</p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Coba kata kunci jabatan, unit, atau nama pejabat
                            lain.
                        </p>
                    </div>
                </div>
                <p
                    id="recipient-selection-help"
                    class="mt-3 text-xs leading-5 text-muted-foreground"
                >
                    Maksimal {{ maximumRecipients }} penerima dalam satu
                    tindakan. Penerima yang dipilih akan memperoleh branch kerja
                    independen pada tahap berikutnya.
                </p>
                <div
                    id="recipient-selection-error"
                    class="mt-2"
                    aria-live="polite"
                >
                    <InputError
                        :message="mergedErrors.recipient_position_ids"
                    />
                </div>
            </section>

            <section
                class="grid content-start gap-5"
                aria-label="Arahan disposisi"
            >
                <div class="rounded-2xl border bg-background/80 p-4 shadow-xs">
                    <div class="flex items-center gap-2">
                        <span
                            class="flex size-7 items-center justify-center rounded-lg bg-blue-100 text-xs font-bold text-blue-800 dark:bg-blue-950 dark:text-blue-200"
                            >2</span
                        >
                        <h2 class="font-semibold">Tetapkan arahan</h2>
                    </div>
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">
                        Instruksi ini dibagikan kepada seluruh penerima yang
                        dipilih.
                    </p>

                    <fieldset class="mt-4 space-y-2">
                        <legend class="sr-only">
                            Label instruksi disposisi
                        </legend>
                        <label
                            v-for="label in instructionLabels"
                            :key="label.id"
                            :for="`forward-instruction-${label.id}`"
                            class="flex min-h-12 cursor-pointer items-start gap-3 rounded-xl border p-3 transition-colors duration-200 motion-reduce:transition-none"
                            :class="
                                selectedInstructionIds.includes(label.id)
                                    ? 'border-blue-400 bg-blue-50/75 dark:border-blue-700 dark:bg-blue-950/30'
                                    : 'border-border hover:border-blue-300 hover:bg-blue-50/40 dark:hover:border-blue-800 dark:hover:bg-blue-950/15'
                            "
                        >
                            <Checkbox
                                :id="`forward-instruction-${label.id}`"
                                :model-value="
                                    selectedInstructionIds.includes(label.id)
                                "
                                :disabled="!canForward || processing"
                                class="mt-0.5"
                                @update:model-value="
                                    toggleInstruction(label.id, $event === true)
                                "
                            />
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold">{{
                                    label.name
                                }}</span>
                                <span
                                    v-if="label.description"
                                    class="mt-1 block text-xs leading-5 text-muted-foreground"
                                >
                                    {{ label.description }}
                                </span>
                            </span>
                        </label>
                    </fieldset>
                    <div class="mt-2" aria-live="polite">
                        <InputError
                            :message="mergedErrors.instruction_label_ids"
                        />
                    </div>
                </div>

                <div class="rounded-2xl border bg-background/80 p-4 shadow-xs">
                    <div class="flex items-center justify-between gap-3">
                        <Label for="forward-instruction-note"
                            >Catatan tambahan</Label
                        >
                        <span
                            class="text-xs tabular-nums"
                            :class="
                                noteLength > 2000
                                    ? 'font-semibold text-destructive'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{ noteLength }}/2.000
                        </span>
                    </div>
                    <textarea
                        id="forward-instruction-note"
                        v-model="instructionNote"
                        rows="5"
                        maxlength="2000"
                        :disabled="!canForward || processing"
                        :aria-invalid="Boolean(mergedErrors.instruction_note)"
                        aria-describedby="forward-instruction-note-help forward-instruction-note-error"
                        class="mt-3 min-h-28 w-full resize-y rounded-xl border border-input bg-background px-3 py-2 text-sm leading-6 shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                        placeholder="Tambahkan konteks yang perlu diketahui seluruh penerima..."
                        @input="delete localErrors.instruction_note"
                    />
                    <p
                        id="forward-instruction-note-help"
                        class="mt-2 text-xs leading-5 text-muted-foreground"
                    >
                        Opsional. Catatan bersifat sama untuk setiap Kepala
                        Bagian penerima.
                    </p>
                    <div
                        id="forward-instruction-note-error"
                        class="mt-2"
                        aria-live="polite"
                    >
                        <InputError :message="mergedErrors.instruction_note" />
                    </div>
                </div>

                <Alert v-if="!canForward">
                    <CircleHelp class="size-4" aria-hidden="true" />
                    <AlertTitle>Penerusan belum tersedia</AlertTitle>
                    <AlertDescription>
                        Branch ini dapat diperiksa, tetapi jabatan aktif Anda
                        tidak dapat meneruskannya.
                    </AlertDescription>
                </Alert>

                <div
                    class="rounded-2xl bg-slate-950 p-4 text-slate-100 shadow-sm dark:bg-slate-900"
                >
                    <div class="flex items-start gap-3">
                        <ShieldCheck
                            class="mt-0.5 size-5 shrink-0 text-emerald-300"
                            aria-hidden="true"
                        />
                        <div>
                            <p class="font-semibold">
                                Checkpoint histori resmi
                            </p>
                            <p class="mt-1 text-sm leading-6 text-slate-300">
                                Sistem akan memverifikasi ulang jabatan, target,
                                instruksi, dokumen, dan status branch sebelum
                                mencatat disposisi.
                            </p>
                        </div>
                    </div>
                </div>

                <Button
                    type="button"
                    class="min-h-12 w-full bg-violet-700 text-base hover:bg-violet-800"
                    :disabled="
                        !canForward ||
                        processing ||
                        availableSectionHeads.length === 0
                    "
                    @click="openConfirmation"
                >
                    <Send class="size-4" aria-hidden="true" />
                    Tinjau penerusan
                    <ChevronRight class="size-4" aria-hidden="true" />
                </Button>
            </section>
        </CardContent>
    </Card>

    <ForwardDispositionConfirmationDialog
        v-model:open="confirmationOpen"
        :recipients="selectedRecipients"
        :instructions="selectedInstructions"
        :note="instructionNote"
        :processing="processing"
        @confirm="confirmDisposition"
    />
</template>
