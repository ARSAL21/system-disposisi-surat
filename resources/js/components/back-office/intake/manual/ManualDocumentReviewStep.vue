<script setup lang="ts">
import {
    CheckCircle2,
    ClipboardCheck,
    FileCheck2,
    FileText,
    RefreshCcw,
    UploadCloud,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';
import type { ScreeningChecklistItem } from '@/types';

const props = defineProps<{
    document: File | null;
    existingDocument?: {
        original_filename: string;
        size_bytes: number;
    } | null;
    checklist: ScreeningChecklistItem[];
    note: string;
    documentError?: string;
    checklistError?: string;
    noteError?: string;
    progress?: number;
}>();

const emit = defineEmits<{
    'select-document': [file?: File];
    'toggle-checklist': [id: string, checked: boolean];
    'update:note': [value: string];
}>();

const isDragging = ref(false);
const completedCount = computed(
    () => props.checklist.filter((item) => item.checked).length,
);

function handleInput(event: Event): void {
    emit('select-document', (event.target as HTMLInputElement).files?.[0]);
}

function handleDrop(event: DragEvent): void {
    isDragging.value = false;
    emit('select-document', event.dataTransfer?.files?.[0]);
}

function formatBytes(bytes: number): string {
    return `${(bytes / (1024 * 1024)).toLocaleString('id-ID', {
        maximumFractionDigits: 1,
    })} MB`;
}
</script>

<template>
    <div class="space-y-7">
        <div>
            <h2 class="text-xl font-semibold tracking-tight">
                Apakah scan sudah siap diajukan?
            </h2>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                Unggah satu PDF lengkap, lalu konfirmasi bahwa data pada form
                sudah cocok dengan surat fisik.
            </p>
        </div>

        <section aria-labelledby="manual-document-heading">
            <div class="mb-3 flex items-center justify-between gap-4">
                <div>
                    <h3
                        id="manual-document-heading"
                        class="text-sm font-semibold"
                    >
                        Hasil scan PDF
                        <span class="text-destructive" aria-hidden="true"
                            >*</span
                        >
                    </h3>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Hanya PDF, maksimal 20 MB.
                    </p>
                </div>
                <span
                    v-if="document || existingDocument"
                    class="inline-flex items-center gap-1.5 rounded-full bg-success px-3 py-1.5 text-xs font-semibold text-success-foreground"
                >
                    <CheckCircle2 class="size-3.5" aria-hidden="true" />
                    Siap diperiksa
                </span>
            </div>

            <label
                for="manual_document"
                :class="
                    cn(
                        'group flex min-h-52 cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-5 py-7 text-center transition-colors outline-none focus-within:border-ring focus-within:ring-3 focus-within:ring-ring/25 motion-reduce:transition-none',
                        isDragging
                            ? 'border-emerald-600 bg-emerald-50 dark:bg-emerald-950/30'
                            : document || existingDocument
                              ? 'border-emerald-300 bg-emerald-50/60 hover:bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950/20 dark:hover:bg-emerald-950/35'
                              : 'border-border bg-muted/25 hover:border-emerald-500/60 hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20',
                        documentError && 'border-destructive bg-destructive/5',
                    )
                "
                @dragenter.prevent="isDragging = true"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop"
            >
                <input
                    id="manual_document"
                    type="file"
                    name="document"
                    accept=".pdf,application/pdf"
                    class="sr-only"
                    :aria-invalid="Boolean(documentError)"
                    aria-describedby="manual_document_hint manual_document_error"
                    @change="handleInput"
                />

                <span
                    :class="[
                        'flex size-14 items-center justify-center rounded-2xl transition-colors',
                        document || existingDocument
                            ? 'bg-emerald-700 text-white dark:bg-emerald-500 dark:text-emerald-950'
                            : 'bg-emerald-100 text-emerald-800 group-hover:bg-emerald-200 dark:bg-emerald-950 dark:text-emerald-300',
                    ]"
                    aria-hidden="true"
                >
                    <FileCheck2
                        v-if="document || existingDocument"
                        class="size-6"
                    />
                    <UploadCloud v-else class="size-6" />
                </span>

                <template v-if="document">
                    <span
                        class="mt-4 max-w-full truncate text-sm font-semibold"
                    >
                        {{ document.name }}
                    </span>
                    <span class="mt-1 text-xs text-muted-foreground">
                        {{ formatBytes(document.size) }} · PDF dipilih
                    </span>
                    <span
                        class="mt-4 inline-flex min-h-11 items-center gap-2 rounded-xl border bg-background px-4 text-sm font-medium shadow-xs transition-colors group-hover:bg-muted"
                    >
                        <RefreshCcw class="size-4" aria-hidden="true" />
                        Pilih scan lain
                    </span>
                </template>
                <template v-else>
                    <span class="mt-4 text-sm font-semibold">
                        {{
                            existingDocument
                                ? 'Pilih PDF hanya jika scan perlu diganti'
                                : 'Tarik PDF ke sini atau pilih dari perangkat'
                        }}
                    </span>
                    <span
                        id="manual_document_hint"
                        class="mt-2 max-w-sm text-xs leading-5 text-muted-foreground"
                    >
                        Pastikan halaman tidak terpotong, tidak terbalik, dan
                        seluruh lampiran berada dalam satu berkas.
                    </span>
                    <span
                        v-if="existingDocument"
                        class="mt-4 max-w-full rounded-xl border bg-background px-4 py-3 text-left text-xs"
                    >
                        <strong class="block truncate text-foreground">
                            {{ existingDocument.original_filename }}
                        </strong>
                        <span class="mt-1 block text-muted-foreground">
                            {{ formatBytes(existingDocument.size_bytes) }} · PDF
                            saat ini tetap digunakan
                        </span>
                    </span>
                </template>
            </label>
            <InputError
                id="manual_document_error"
                :message="documentError"
                class="mt-2"
                role="alert"
            />

            <div
                v-if="progress !== undefined"
                class="mt-4 rounded-xl border bg-muted/35 p-3"
                aria-live="polite"
            >
                <div
                    class="mb-2 flex items-center justify-between text-xs font-medium"
                >
                    <span>Mengunggah scan surat</span>
                    <span class="tabular-nums">{{ progress }}%</span>
                </div>
                <progress
                    class="h-2 w-full overflow-hidden rounded-full accent-emerald-700"
                    :value="progress"
                    max="100"
                />
            </div>
        </section>

        <section class="space-y-3" aria-labelledby="manual-checklist-heading">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <h3
                        id="manual-checklist-heading"
                        class="text-sm font-semibold"
                    >
                        Konfirmasi pemeriksaan
                    </h3>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Seluruh poin harus lengkap sebelum diajukan.
                    </p>
                </div>
                <span
                    class="rounded-full bg-emerald-500/10 px-3 py-1.5 text-xs font-semibold text-emerald-800 tabular-nums dark:text-emerald-300"
                >
                    {{ completedCount }}/{{ checklist.length }} selesai
                </span>
            </div>

            <div
                v-for="item in checklist"
                :key="item.id"
                :class="[
                    'flex gap-3 rounded-2xl border p-4 transition-colors motion-reduce:transition-none',
                    item.checked
                        ? 'border-emerald-200 bg-emerald-50/65 dark:border-emerald-900 dark:bg-emerald-950/20'
                        : 'bg-background',
                ]"
            >
                <Checkbox
                    :id="`manual-check-${item.id}`"
                    :model-value="item.checked"
                    class="mt-0.5 size-5"
                    @update:model-value="
                        emit('toggle-checklist', item.id, Boolean($event))
                    "
                />
                <label
                    :for="`manual-check-${item.id}`"
                    class="min-w-0 cursor-pointer"
                >
                    <span class="flex items-center gap-2 text-sm font-semibold">
                        <CheckCircle2
                            v-if="item.checked"
                            class="size-4 text-emerald-700 dark:text-emerald-300"
                            aria-hidden="true"
                        />
                        <ClipboardCheck
                            v-else
                            class="size-4 text-muted-foreground"
                            aria-hidden="true"
                        />
                        {{ item.label }}
                    </span>
                    <span
                        class="mt-1 block text-xs leading-5 text-muted-foreground"
                    >
                        {{ item.description }}
                    </span>
                </label>
            </div>
            <InputError :message="checklistError" role="alert" />
        </section>

        <section class="space-y-2" aria-labelledby="manual-note-heading">
            <div class="flex items-baseline justify-between gap-4">
                <label
                    id="manual-note-heading"
                    for="manual_screening_note"
                    class="text-sm font-semibold"
                >
                    Catatan pengantar untuk Kabag Umum
                    <span class="font-normal text-muted-foreground">
                        (opsional)
                    </span>
                </label>
                <FileText
                    class="size-4 text-muted-foreground"
                    aria-hidden="true"
                />
            </div>
            <textarea
                id="manual_screening_note"
                :value="note"
                name="screening_note"
                rows="4"
                maxlength="2000"
                class="w-full resize-y rounded-xl border border-input bg-background px-4 py-3 text-base leading-relaxed shadow-xs transition-[color,box-shadow] outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/30"
                placeholder="Tuliskan kondisi fisik, jumlah lampiran, atau informasi penerimaan yang perlu diketahui..."
                :aria-invalid="Boolean(noteError)"
                aria-describedby="manual_screening_note_error"
                @input="
                    emit(
                        'update:note',
                        ($event.target as HTMLTextAreaElement).value,
                    )
                "
            />
            <InputError
                id="manual_screening_note_error"
                :message="noteError"
                role="alert"
            />
        </section>
    </div>
</template>
