<script setup lang="ts">
import {
    ClipboardList,
    Info,
    Send,
    ShieldCheck,
    UserRoundCheck,
    UsersRound,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import FirstDispositionConfirmationDialog from '@/components/back-office/dispositions/FirstDispositionConfirmationDialog.vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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

const selectedPositionId = ref<number | null>(null);
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
const selectedPosition = computed(
    () =>
        availableAssistantPositions.value.find(
            (position) => position.id === selectedPositionId.value,
        ) ?? null,
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
        if (
            selectedPositionId.value !== null &&
            !availableAssistantPositions.value.some(
                (position) => position.id === selectedPositionId.value,
            )
        ) {
            selectedPositionId.value = null;
        }
    },
);

function updateTarget(value: unknown): void {
    const targetId = Number(value);
    selectedPositionId.value = Number.isInteger(targetId) ? targetId : null;
    delete localErrors.value.recipient_position_id;
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

    if (!selectedPosition.value) {
        nextErrors.recipient_position_id =
            'Pilih satu jabatan Asisten yang memiliki pejabat aktif.';
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
    if (!selectedPosition.value || props.processing || !validateForm()) {
        return;
    }

    emit('confirm', {
        recipient_position_id: selectedPosition.value.id,
        instruction_label_ids: [...selectedInstructionIds.value],
        instruction_note: instructionNote.value.trim(),
    });
}
</script>

<template>
    <Card class="border-blue-200/80 py-0 shadow-sm dark:border-blue-950">
        <CardHeader class="border-b p-5 sm:p-6">
            <div class="flex items-start gap-3">
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-blue-500/10 text-blue-700 dark:text-blue-300"
                >
                    <ClipboardList class="size-5" aria-hidden="true" />
                </span>
                <div>
                    <CardTitle>Buat disposisi pertama</CardTitle>
                    <p class="mt-1 text-sm leading-6 text-muted-foreground">
                        Tunjuk tepat satu Asisten dan berikan instruksi resmi.
                    </p>
                </div>
            </div>
        </CardHeader>

        <CardContent class="grid gap-6 p-5 sm:p-6">
            <Alert v-if="!canCreate">
                <Info class="size-4" aria-hidden="true" />
                <AlertTitle>Akses baca-saja</AlertTitle>
                <AlertDescription>
                    Surat dapat diperiksa, tetapi jabatan aktif Anda tidak dapat
                    membuat disposisi pertama untuk route ini.
                </AlertDescription>
            </Alert>

            <Alert
                v-else-if="availableAssistantPositions.length === 0"
                variant="destructive"
            >
                <UsersRound class="size-4" aria-hidden="true" />
                <AlertTitle>Asisten penerima belum tersedia</AlertTitle>
                <AlertDescription>
                    Belum ada jabatan Asisten dengan satu pemegang aktif yang
                    dapat diverifikasi. Disposisi belum dapat dikirim.
                </AlertDescription>
            </Alert>

            <div class="space-y-2">
                <Label for="disposition-recipient">
                    Asisten penerima <span aria-hidden="true">*</span>
                </Label>
                <Select
                    :model-value="
                        selectedPositionId === null
                            ? undefined
                            : String(selectedPositionId)
                    "
                    :disabled="!canCreate || processing"
                    @update:model-value="updateTarget"
                >
                    <SelectTrigger
                        id="disposition-recipient"
                        class="min-h-11 w-full"
                        :aria-invalid="
                            Boolean(mergedErrors.recipient_position_id)
                        "
                        aria-describedby="disposition-recipient-help disposition-recipient-error"
                    >
                        <SelectValue placeholder="Pilih satu jabatan Asisten" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="position in assistantPositions"
                            :key="position.id"
                            :value="String(position.id)"
                            :disabled="
                                !position.is_available || !position.holder_name
                            "
                        >
                            {{ position.name }}
                            <template v-if="position.holder_name">
                                · {{ position.holder_name }}
                            </template>
                            <template v-else> · Jabatan kosong</template>
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p
                    id="disposition-recipient-help"
                    class="text-xs leading-5 text-muted-foreground"
                >
                    Hanya Position level Asisten yang ditampilkan. Jabatan Wali
                    Kota atau Sekda milik pengirim tidak menjadi pilihan.
                </p>
                <InputError
                    id="disposition-recipient-error"
                    :message="mergedErrors.recipient_position_id"
                />
            </div>

            <fieldset class="space-y-3">
                <legend class="text-sm font-medium">
                    Instruksi disposisi <span aria-hidden="true">*</span>
                </legend>
                <p class="text-xs leading-5 text-muted-foreground">
                    Pilih satu atau beberapa label. Instruksi aktif berasal dari
                    katalog workflow yang dikelola administrator.
                </p>

                <div
                    class="grid gap-2"
                    :aria-invalid="Boolean(mergedErrors.instruction_label_ids)"
                >
                    <label
                        v-for="label in instructionLabels"
                        :key="label.id"
                        :for="`instruction-label-${label.id}`"
                        class="group flex min-h-12 cursor-pointer items-start gap-3 rounded-xl border p-3 transition-colors duration-200 hover:border-blue-300 hover:bg-blue-50/55 motion-reduce:transition-none dark:hover:border-blue-800 dark:hover:bg-blue-950/20"
                        :class="
                            selectedInstructionIds.includes(label.id)
                                ? 'border-blue-400 bg-blue-50/75 dark:border-blue-700 dark:bg-blue-950/30'
                                : 'border-border'
                        "
                    >
                        <Checkbox
                            :id="`instruction-label-${label.id}`"
                            :model-value="
                                selectedInstructionIds.includes(label.id)
                            "
                            :disabled="!canCreate || processing"
                            class="mt-0.5"
                            @update:model-value="
                                toggleInstruction(label.id, $event === true)
                            "
                        />
                        <span class="min-w-0">
                            <span class="block text-sm font-semibold">
                                {{ label.name }}
                            </span>
                            <span
                                v-if="label.description"
                                class="mt-1 block text-xs leading-5 text-muted-foreground"
                            >
                                {{ label.description }}
                            </span>
                        </span>
                    </label>
                </div>
                <InputError :message="mergedErrors.instruction_label_ids" />
            </fieldset>

            <div class="space-y-2">
                <div class="flex items-center justify-between gap-3">
                    <Label for="disposition-note">Catatan tambahan</Label>
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
                    id="disposition-note"
                    v-model="instructionNote"
                    rows="5"
                    maxlength="2000"
                    :disabled="!canCreate || processing"
                    :aria-invalid="Boolean(mergedErrors.instruction_note)"
                    aria-describedby="disposition-note-help disposition-note-error"
                    class="min-h-28 w-full resize-y rounded-xl border border-input bg-background px-3 py-2 text-sm leading-6 shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                    placeholder="Tambahkan konteks khusus jika diperlukan..."
                    @input="delete localErrors.instruction_note"
                />
                <p
                    id="disposition-note-help"
                    class="text-xs leading-5 text-muted-foreground"
                >
                    Opsional. Jangan menuliskan kredensial atau data
                    autentikasi.
                </p>
                <InputError
                    id="disposition-note-error"
                    :message="mergedErrors.instruction_note"
                />
            </div>

            <div
                v-if="selectedPosition"
                class="flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/65 p-4 dark:border-emerald-900 dark:bg-emerald-950/25"
                aria-live="polite"
            >
                <UserRoundCheck
                    class="mt-0.5 size-5 shrink-0 text-emerald-700 dark:text-emerald-300"
                    aria-hidden="true"
                />
                <div>
                    <p class="text-xs text-muted-foreground">
                        Penerima yang ditunjuk
                    </p>
                    <p class="mt-1 font-semibold">
                        {{ selectedPosition.name }}
                    </p>
                    <p class="mt-1 text-sm leading-6 text-muted-foreground">
                        {{ selectedPosition.holder_name }}
                    </p>
                </div>
            </div>

            <div
                class="flex items-start gap-3 rounded-2xl bg-muted/55 p-4 text-sm"
            >
                <ShieldCheck
                    class="mt-0.5 size-5 shrink-0 text-blue-700 dark:text-blue-300"
                    aria-hidden="true"
                />
                <p class="leading-6 text-muted-foreground">
                    Disposisi dicatat atas jabatan aktif, bukan Role. Setelah
                    dikirim, route eksekutif selesai dan tidak dapat dikirim
                    ulang pada tahap ini.
                </p>
            </div>

            <Button
                type="button"
                class="min-h-11 w-full bg-blue-700 hover:bg-blue-800"
                :disabled="
                    !canCreate ||
                    processing ||
                    availableAssistantPositions.length === 0
                "
                @click="openConfirmation"
            >
                <Send class="size-4" aria-hidden="true" />
                Tinjau dan kirim disposisi
            </Button>
        </CardContent>
    </Card>

    <FirstDispositionConfirmationDialog
        v-model:open="confirmationOpen"
        :recipient="selectedPosition"
        :instructions="selectedInstructions"
        :note="instructionNote"
        :processing="processing"
        @confirm="confirmDisposition"
    />
</template>
