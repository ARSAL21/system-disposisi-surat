<script setup lang="ts">
import {
    ClipboardList,
    Info,
    Send,
    ShieldCheck,
    UsersRound,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import FirstDispositionConfirmationDialog from '@/components/back-office/dispositions/FirstDispositionConfirmationDialog.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import type {
    CreateFirstDispositionPayload,
    DispositionInstructionLabelOption,
    DispositionPositionOption,
} from '@/types';

const props = defineProps<{
    positions: DispositionPositionOption[];
    instructionLabels: DispositionInstructionLabelOption[];
    canCreate: boolean;
    processing?: boolean;
    errors?: Record<string, string>;
}>();

const emit = defineEmits<{
    confirm: [payload: CreateFirstDispositionPayload];
}>();

const maximumRecipients = 3;
const selectedPositionIds = ref<number[]>([]);
const selectedInstructionIds = ref<number[]>([]);
const instructionNote = ref('');
const confirmationOpen = ref(false);
const localErrors = ref<Record<string, string>>({});

const assistantPositions = computed(() =>
    props.positions.filter((position) => position.level_code === 'ASSISTANT'),
);
const availableAssistantPositions = computed(() =>
    assistantPositions.value.filter(
        (position) => position.is_available && position.holder_name,
    ),
);
const selectedPositions = computed(() =>
    availableAssistantPositions.value.filter((position) =>
        selectedPositionIds.value.includes(position.id),
    ),
);
const selectedInstructions = computed(() =>
    props.instructionLabels.filter((label) =>
        selectedInstructionIds.value.includes(label.id),
    ),
);
const noteLength = computed(() => instructionNote.value.length);
const mergedErrors = computed(() => ({
    ...(props.errors ?? {}),
    ...localErrors.value,
}));

watch(
    () => props.positions,
    () => {
        selectedPositionIds.value = selectedPositionIds.value.filter(
            (positionId) =>
                availableAssistantPositions.value.some(
                    (position) => position.id === positionId,
                ),
        );
    },
);

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

    if (selectedPositions.value.length === 0) {
        nextErrors.recipient_position_ids =
            'Pilih sedikitnya satu jabatan Asisten yang memiliki pejabat aktif.';
    } else if (selectedPositions.value.length > maximumRecipients) {
        nextErrors.recipient_position_ids =
            'Maksimal tiga jabatan Asisten dapat dipilih.';
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
    if (!props.canCreate || !validateForm()) {
        return;
    }

    confirmationOpen.value = true;
}

function confirmDisposition(): void {
    if (
        selectedPositions.value.length === 0 ||
        props.processing ||
        !validateForm()
    ) {
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
    <div class="rounded-3xl border border-indigo-500/30 bg-card p-6 shadow-lg shadow-indigo-500/5 dark:border-indigo-500/20 dark:bg-slate-900/90">
        <!-- Header -->
        <div class="flex items-start gap-3.5 border-b border-border/70 pb-5 dark:border-border/50">
            <div
                class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-indigo-600/10 text-indigo-600 shadow-xs dark:bg-indigo-400/10 dark:text-indigo-400"
            >
                <ClipboardList class="size-5" />
            </div>
            <div>
                <h3 class="font-['Syne',sans-serif] text-base font-bold text-foreground sm:text-lg">
                    Form Lembar Disposisi Pimpinan
                </h3>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    Tunjuk 1-3 Asisten penerima dan tetapkan instruksi kebijakan terarah.
                </p>
            </div>
        </div>

        <div class="mt-6 grid gap-6">
            <Alert v-if="!canCreate" class="rounded-2xl">
                <Info class="size-4" />
                <AlertTitle>Akses Baca-Saja</AlertTitle>
                <AlertDescription class="text-xs">
                    Surat dapat diperiksa, tetapi jabatan aktif Anda tidak memiliki kewenangan membuat disposisi pertama untuk route ini.
                </AlertDescription>
            </Alert>

            <Alert
                v-else-if="availableAssistantPositions.length === 0"
                variant="destructive"
                class="rounded-2xl"
            >
                <UsersRound class="size-4" />
                <AlertTitle>Asisten Penerima Belum Tersedia</AlertTitle>
                <AlertDescription class="text-xs">
                    Belum ada jabatan Asisten dengan satu pemegang aktif yang dapat diverifikasi di sistem.
                </AlertDescription>
            </Alert>

            <!-- Recipient Assistant Selection -->
            <fieldset class="space-y-3">
                <div class="flex items-center justify-between gap-3">
                    <legend class="font-mono text-xs font-bold text-foreground uppercase tracking-wider">
                        Asisten Penerima <span class="text-destructive">*</span>
                    </legend>
                    <span
                        class="rounded-full bg-indigo-500/10 px-2.5 py-0.5 font-mono text-[11px] font-bold text-indigo-700 dark:text-indigo-300"
                    >
                        {{ selectedPositions.length }}/{{ maximumRecipients }} Dipilih
                    </span>
                </div>

                <div
                    class="grid gap-2"
                    :aria-invalid="Boolean(mergedErrors.recipient_position_ids)"
                >
                    <label
                        v-for="position in assistantPositions"
                        :key="position.id"
                        :for="`assistant-recipient-${position.id}`"
                        class="flex items-start gap-3 rounded-2xl border p-3.5 transition-all duration-200"
                        :class="[
                            selectedPositionIds.includes(position.id)
                                ? 'border-indigo-500 bg-indigo-500/10 shadow-xs dark:border-indigo-500/80 dark:bg-indigo-950/40'
                                : 'border-border/80 bg-background/60 hover:border-indigo-300 dark:bg-slate-950/40',
                            position.is_available && position.holder_name
                                ? 'cursor-pointer'
                                : 'cursor-not-allowed opacity-50',
                        ]"
                    >
                        <Checkbox
                            :id="`assistant-recipient-${position.id}`"
                            :model-value="selectedPositionIds.includes(position.id)"
                            :disabled="
                                !canCreate ||
                                processing ||
                                !position.is_available ||
                                !position.holder_name
                            "
                            class="mt-0.5"
                            @update:model-value="toggleRecipient(position.id, $event === true)"
                        />
                        <span class="min-w-0">
                            <span class="block text-xs font-bold text-foreground">
                                {{ position.name }}
                            </span>
                            <span class="mt-0.5 block text-[11px] text-muted-foreground">
                                Pejabat: {{ position.holder_name ?? 'Jabatan belum terisi' }}
                            </span>
                        </span>
                    </label>
                </div>
                <InputError :message="mergedErrors.recipient_position_ids" />
            </fieldset>

            <!-- Instructions -->
            <fieldset class="space-y-3">
                <legend class="font-mono text-xs font-bold text-foreground uppercase tracking-wider">
                    Instruksi Disposisi <span class="text-destructive">*</span>
                </legend>

                <div
                    class="grid gap-2"
                    :aria-invalid="Boolean(mergedErrors.instruction_label_ids)"
                >
                    <label
                        v-for="label in instructionLabels"
                        :key="label.id"
                        :for="`instruction-label-${label.id}`"
                        class="flex cursor-pointer items-start gap-3 rounded-2xl border p-3 transition-all duration-200"
                        :class="[
                            selectedInstructionIds.includes(label.id)
                                ? 'border-indigo-500 bg-indigo-500/10 shadow-xs dark:border-indigo-500/80 dark:bg-indigo-950/40'
                                : 'border-border/80 bg-background/60 hover:border-indigo-300 dark:bg-slate-950/40',
                        ]"
                    >
                        <Checkbox
                            :id="`instruction-label-${label.id}`"
                            :model-value="selectedInstructionIds.includes(label.id)"
                            :disabled="!canCreate || processing"
                            class="mt-0.5"
                            @update:model-value="toggleInstruction(label.id, $event === true)"
                        />
                        <span class="min-w-0">
                            <span class="block text-xs font-bold text-foreground">
                                {{ label.name }}
                            </span>
                            <span
                                v-if="label.description"
                                class="mt-0.5 block text-[11px] text-muted-foreground leading-relaxed"
                            >
                                {{ label.description }}
                            </span>
                        </span>
                    </label>
                </div>
                <InputError :message="mergedErrors.instruction_label_ids" />
            </fieldset>

            <!-- Additional Notes -->
            <div class="space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <Label for="disposition-note" class="font-mono text-xs font-bold uppercase tracking-wider">
                        Catatan Tambahan Pimpinan
                    </Label>
                    <span
                        class="font-mono text-[10px] tabular-nums"
                        :class="noteLength > 2000 ? 'font-bold text-destructive' : 'text-muted-foreground'"
                    >
                        {{ noteLength }}/2.000
                    </span>
                </div>
                <textarea
                    id="disposition-note"
                    v-model="instructionNote"
                    rows="4"
                    maxlength="2000"
                    :disabled="!canCreate || processing"
                    class="min-h-24 w-full resize-y rounded-2xl border border-border/80 bg-background/80 px-3.5 py-2.5 text-xs text-foreground shadow-xs transition-all focus:border-indigo-600 focus:outline-none focus:ring-1 focus:ring-indigo-600 disabled:opacity-50"
                    placeholder="Instruksi spesifik atau batas waktu khusus dari pimpinan..."
                    @input="delete localErrors.instruction_note"
                />
                <InputError :message="mergedErrors.instruction_note" />
            </div>

            <!-- Invariant Assurance Notice -->
            <div class="flex items-start gap-3 rounded-2xl border border-border/60 bg-muted/40 p-3.5 text-xs">
                <ShieldCheck class="mt-0.5 size-4 shrink-0 text-indigo-600 dark:text-indigo-400" />
                <p class="leading-relaxed text-muted-foreground text-[11px]">
                    Disposisi tercatat atas jabatan eksekutif aktif. Setelah dikirim, alur berpindah ke meja Asisten dan dicatat permanen dalam audit log.
                </p>
            </div>

            <!-- Submit Button -->
            <Button
                type="button"
                class="h-12 w-full rounded-2xl bg-indigo-600 text-xs font-bold text-white shadow-md shadow-indigo-600/25 transition-all hover:bg-indigo-700 sm:text-sm"
                :disabled="
                    !canCreate ||
                    processing ||
                    availableAssistantPositions.length === 0
                "
                @click="openConfirmation"
            >
                <Send class="mr-2 size-4" />
                <span>Tinjau & Kirim Disposisi</span>
            </Button>
        </div>
    </div>

    <FirstDispositionConfirmationDialog
        v-model:open="confirmationOpen"
        :recipients="selectedPositions"
        :instructions="selectedInstructions"
        :note="instructionNote"
        :processing="processing"
        @confirm="confirmDisposition"
    />
</template>
