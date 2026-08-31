<script setup lang="ts">
import {
    FileText,
    FileUp,
    Loader2,
    Plus,
    Shield,
    Upload,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { formatBytes } from '@/lib/documentVersionPreview';
import type { CreateDocumentVersionPayload } from '@/types';

const props = defineProps<{
    open: boolean;
    nextVersionNumber: number;
    processing?: boolean;
    errors?: Record<string, string>;
}>();

const emit = defineEmits<{
    'update:open': [open: boolean];
    submit: [payload: CreateDocumentVersionPayload];
}>();

const fileInputRef = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const fileError = ref<string>('');
const correctionReason = ref<string>('');
const isDragging = ref<boolean>(false);

const canSubmit = computed(() => {
    return (
        selectedFile.value !== null &&
        correctionReason.value.trim().length >= 10 &&
        correctionReason.value.trim().length <= 2000 &&
        !props.processing
    );
});

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            selectedFile.value = null;
            fileError.value = '';
            correctionReason.value = '';
            isDragging.value = false;
        }
    },
);

function handleFileChange(event: Event): void {
    const target = event.target as HTMLInputElement;

    if (target.files && target.files.length > 0) {
        validateAndSetFile(target.files[0]);
    }
}

function handleDrop(event: DragEvent): void {
    isDragging.value = false;

    if (event.dataTransfer?.files && event.dataTransfer.files.length > 0) {
        validateAndSetFile(event.dataTransfer.files[0]);
    }
}

function validateAndSetFile(file: File): void {
    fileError.value = '';

    if (
        !file.name.toLowerCase().endsWith('.pdf') &&
        file.type !== 'application/pdf'
    ) {
        fileError.value = 'Berkas wajib berformat PDF (.pdf).';
        selectedFile.value = null;

        return;
    }

    const maxBytes = 20 * 1024 * 1024; // 20 MB

    if (file.size > maxBytes) {
        fileError.value = `Ukuran berkas melebihi batas maksimal 20 MB (Ukuran: ${formatBytes(file.size)}).`;
        selectedFile.value = null;

        return;
    }

    if (file.size === 0) {
        fileError.value = 'Berkas PDF kosong (0 Bytes) tidak diizinkan.';
        selectedFile.value = null;

        return;
    }

    selectedFile.value = file;
}

function removeFile(): void {
    selectedFile.value = null;
    fileError.value = '';

    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
}

function handleSubmit(): void {
    if (!canSubmit.value) {
        return;
    }

    emit('submit', {
        document: selectedFile.value,
        correction_reason: correctionReason.value.trim(),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <div class="flex items-center gap-2">
                    <span
                        class="inline-flex size-7 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <FileUp class="size-4" aria-hidden="true" />
                    </span>
                    <DialogTitle
                        class="text-lg font-bold tracking-tight sm:text-xl"
                    >
                        Unggah Versi Dokumen Koreksi (v{{ nextVersionNumber }})
                    </DialogTitle>
                </div>
                <DialogDescription
                    class="text-xs leading-relaxed text-muted-foreground"
                >
                    Unggah berkas pengganti resmi sebelum surat diagendakan ke
                    pimpinan. Seluruh berkas versi terdahulu tetap dipertahankan
                    utuh.
                </DialogDescription>
            </DialogHeader>

            <!-- Immutability Invariant Banner -->
            <div
                class="rounded-lg border border-primary/30 bg-primary/5 p-3 text-xs text-foreground"
            >
                <div class="flex items-start gap-2">
                    <Shield
                        class="mt-0.5 size-4 shrink-0 text-primary"
                        aria-hidden="true"
                    />
                    <div class="space-y-1">
                        <p class="font-semibold text-primary">
                            Jaminan Keamanan & Immutabilitas:
                        </p>
                        <p class="leading-relaxed text-muted-foreground">
                            Versi baru ini (<strong
                                >v{{ nextVersionNumber }}</strong
                            >) akan dicatat sebagai dokumen acuan disposisi.
                            Versi terdahulu tidak akan dihapus atau
                            dimodifikasi, dan tetap tersimpan sebagai bukti
                            audit.
                        </p>
                    </div>
                </div>
            </div>

            <form class="space-y-4 py-1" @submit.prevent="handleSubmit">
                <!-- PDF File Upload Section -->
                <div class="space-y-2">
                    <Label class="text-xs font-semibold text-foreground">
                        Berkas Dokumen PDF Baru
                        <span class="text-destructive">*</span>
                    </Label>

                    <input
                        id="document-version-file"
                        ref="fileInputRef"
                        type="file"
                        accept="application/pdf,.pdf"
                        class="hidden"
                        @change="handleFileChange"
                    />

                    <!-- Drag & Drop / Upload Box -->
                    <button
                        v-if="!selectedFile"
                        type="button"
                        class="flex cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed p-6 text-center transition-colors"
                        :class="[
                            isDragging
                                ? 'border-primary bg-primary/5'
                                : 'border-border/90 bg-muted/30 hover:border-primary/60 hover:bg-muted/50',
                        ]"
                        @click="fileInputRef?.click()"
                        @dragover.prevent="isDragging = true"
                        @dragleave.prevent="isDragging = false"
                        @drop.prevent="handleDrop"
                        :aria-invalid="Boolean(fileError || errors?.document)"
                        aria-describedby="document-version-file-help document-version-file-error"
                    >
                        <div
                            class="rounded-full bg-primary/10 p-3 text-primary"
                        >
                            <Upload class="size-5" aria-hidden="true" />
                        </div>
                        <p class="mt-2 text-xs font-medium text-foreground">
                            Tarik dan lepas berkas PDF ke sini, atau
                            <span class="text-primary underline"
                                >klik untuk memilih</span
                            >
                        </p>
                        <p class="mt-1 text-[11px] text-muted-foreground">
                            Format wajib PDF (.pdf), ukuran maksimal 20 MB
                        </p>
                    </button>

                    <!-- Selected File Preview Card -->
                    <div
                        v-else
                        class="flex items-center justify-between rounded-lg border border-border bg-muted/40 p-3"
                    >
                        <div class="flex items-center gap-2.5 overflow-hidden">
                            <div
                                class="shrink-0 rounded-md bg-primary/10 p-2 text-primary"
                            >
                                <FileText class="size-4" aria-hidden="true" />
                            </div>
                            <div class="truncate text-xs">
                                <p
                                    class="truncate font-semibold text-foreground"
                                >
                                    {{ selectedFile.name }}
                                </p>
                                <p class="text-[11px] text-muted-foreground">
                                    {{ formatBytes(selectedFile.size) }}
                                </p>
                            </div>
                        </div>

                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="size-8 p-0 text-muted-foreground hover:text-destructive"
                            title="Hapus berkas"
                            :disabled="processing"
                            @click="removeFile"
                        >
                            <X class="size-4" aria-hidden="true" />
                            <span class="sr-only">Hapus berkas</span>
                        </Button>
                    </div>

                    <p id="document-version-file-help" class="sr-only">
                        Pilih satu berkas PDF dengan ukuran maksimal 20 MB.
                    </p>
                    <div id="document-version-file-error" aria-live="polite">
                        <InputError :message="fileError || errors?.document" />
                    </div>
                </div>

                <!-- Correction Reason Textarea -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <Label
                            for="correction-reason"
                            class="text-xs font-semibold text-foreground"
                        >
                            Alasan Koreksi Resmi
                            <span class="text-destructive">*</span>
                        </Label>
                        <span
                            class="text-[11px]"
                            :class="[
                                correctionReason.trim().length < 10
                                    ? 'text-muted-foreground'
                                    : correctionReason.trim().length > 2000
                                      ? 'font-semibold text-destructive'
                                      : 'text-muted-foreground',
                            ]"
                        >
                            {{ correctionReason.trim().length }} / 2000 karakter
                            (min. 10)
                        </span>
                    </div>

                    <textarea
                        id="correction-reason"
                        v-model="correctionReason"
                        rows="4"
                        maxlength="2000"
                        class="w-full resize-y rounded-xl border border-border bg-background px-3 py-2.5 text-xs leading-relaxed outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-2 focus-visible:ring-ring/40"
                        placeholder="Jelaskan alasan administratif penggantian versi dokumen resmi (contoh: berkas awal belum memuat tanda tangan basah pimpinan)..."
                        :disabled="processing"
                        :aria-invalid="Boolean(errors?.correction_reason)"
                        aria-describedby="correction-reason-help correction-reason-error"
                    />

                    <p
                        id="correction-reason-help"
                        class="text-[11px] text-muted-foreground"
                    >
                        Alasan koreksi akan dicatat secara permanen di log audit
                        sistem dan terlihat oleh pejabat internal yang
                        berwenang.
                    </p>

                    <div id="correction-reason-error" aria-live="polite">
                        <InputError :message="errors?.correction_reason" />
                    </div>
                </div>

                <DialogFooter
                    class="flex flex-wrap items-center justify-between gap-2 border-t border-border pt-4"
                >
                    <Button
                        type="button"
                        variant="outline"
                        class="min-h-10 text-xs"
                        :disabled="processing"
                        @click="emit('update:open', false)"
                    >
                        Batal
                    </Button>

                    <Button
                        type="submit"
                        class="min-h-10 gap-1.5 bg-primary text-xs font-medium text-primary-foreground hover:bg-primary/90"
                        :disabled="!canSubmit"
                    >
                        <Loader2
                            v-if="processing"
                            class="size-3.5 animate-spin motion-reduce:animate-none"
                            aria-hidden="true"
                        />
                        <Plus v-else class="size-3.5" aria-hidden="true" />
                        <span>{{
                            processing
                                ? 'Menyimpan & Memverifikasi...'
                                : 'Simpan Versi Koreksi'
                        }}</span>
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
