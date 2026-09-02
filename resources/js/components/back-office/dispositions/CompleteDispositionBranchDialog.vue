<script setup lang="ts">
import { CheckCircle2, CircleAlert, LockKeyhole } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import type { CompleteDispositionBranchPayload } from '@/types';

const minimumNoteLength = 10;
const maximumNoteLength = 2000;

const props = defineProps<{
    open: boolean;
    directCompletion?: boolean;
    processing?: boolean;
    error?: string;
}>();

const emit = defineEmits<{
    'update:open': [open: boolean];
    confirm: [payload: CompleteDispositionBranchPayload];
}>();

const completionNote = ref('');
const localError = ref('');
const noteLength = computed(() => completionNote.value.length);
const mergedError = computed(() => props.error || localError.value);

watch(
    () => props.open,
    (open) => {
        if (!open) {
            completionNote.value = '';
            localError.value = '';
        }
    },
);

function confirmCompletion(): void {
    if (props.processing) {
        return;
    }

    const normalizedNote = completionNote.value.trim();

    if (normalizedNote.length < minimumNoteLength) {
        localError.value = `Catatan penyelesaian minimal ${minimumNoteLength} karakter.`;

        return;
    }

    if (normalizedNote.length > maximumNoteLength) {
        localError.value = 'Catatan penyelesaian maksimal 2.000 karakter.';

        return;
    }

    localError.value = '';
    emit('confirm', { completion_note: normalizedNote });
}
</script>

<template>
    <Dialog
        :open="open"
        @update:open="!processing ? emit('update:open', $event) : undefined"
    >
        <DialogContent class="max-h-[90dvh] overflow-y-auto sm:max-w-xl">
            <DialogHeader>
                <span
                    class="mb-2 flex size-12 items-center justify-center rounded-2xl bg-emerald-600 text-white shadow-sm"
                >
                    <CheckCircle2 class="size-6" aria-hidden="true" />
                </span>
                <DialogTitle>Selesaikan cabang disposisi</DialogTitle>
                <DialogDescription class="leading-6">
                    Catat hasil akhir yang dapat dipahami Asisten sebelum
                    menutup jalur kerja ini.
                </DialogDescription>
            </DialogHeader>

            <Alert
                v-if="directCompletion"
                class="border-amber-200 bg-amber-50/80 dark:border-amber-900 dark:bg-amber-950/25"
            >
                <CircleAlert class="size-4" aria-hidden="true" />
                <AlertTitle>Penyelesaian langsung</AlertTitle>
                <AlertDescription>
                    Tahap “Ditangani” akan dilewati karena cabang belum pernah
                    dimulai. Gunakan hanya jika tindak lanjut memang telah
                    selesai.
                </AlertDescription>
            </Alert>

            <div>
                <label for="branch-completion-note" class="font-semibold">
                    Hasil penyelesaian
                    <span class="text-destructive" aria-hidden="true">*</span>
                </label>
                <p
                    id="branch-completion-help"
                    class="mt-1 text-sm leading-6 text-muted-foreground"
                >
                    Jelaskan keluaran, keputusan, atau tindakan yang sudah
                    diselesaikan. Minimal 10 karakter.
                </p>
                <textarea
                    id="branch-completion-note"
                    v-model="completionNote"
                    rows="6"
                    class="mt-3 flex min-h-36 w-full resize-y rounded-xl border border-input bg-background px-3 py-3 text-base leading-6 shadow-xs ring-offset-background outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50 md:text-sm"
                    placeholder="Tuliskan hasil akhir penanganan cabang ini..."
                    :maxlength="maximumNoteLength"
                    :disabled="processing"
                    :aria-invalid="Boolean(mergedError)"
                    aria-describedby="branch-completion-help branch-completion-counter branch-completion-error"
                    @input="localError = ''"
                />
                <div
                    class="mt-2 flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between"
                >
                    <div id="branch-completion-error" aria-live="polite">
                        <InputError :message="mergedError" />
                    </div>
                    <p
                        id="branch-completion-counter"
                        class="shrink-0 text-xs text-muted-foreground tabular-nums"
                    >
                        {{ noteLength.toLocaleString('id-ID') }}/2.000
                    </p>
                </div>
            </div>

            <div
                class="flex items-start gap-3 rounded-2xl border bg-muted/35 p-4"
            >
                <LockKeyhole
                    class="mt-0.5 size-5 shrink-0 text-muted-foreground"
                    aria-hidden="true"
                />
                <p class="text-sm leading-6 text-muted-foreground">
                    Setelah dikonfirmasi, cabang menjadi read-only dan tidak
                    dapat dibuka kembali pada workflow MVP.
                </p>
            </div>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button
                    type="button"
                    variant="outline"
                    class="min-h-11"
                    :disabled="processing"
                    @click="emit('update:open', false)"
                >
                    Kembali periksa
                </Button>
                <Button
                    type="button"
                    class="min-h-11 bg-emerald-700 hover:bg-emerald-800"
                    :disabled="processing"
                    @click="confirmCompletion"
                >
                    <Spinner v-if="processing" />
                    <CheckCircle2 v-else class="size-4" aria-hidden="true" />
                    {{ processing ? 'Menyelesaikan...' : 'Konfirmasi selesai' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
