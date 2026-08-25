<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    Download,
    FileCheck2,
    FileUp,
    ShieldCheck,
    UploadCloud,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { publicSubmissionRoutes } from '@/lib/publicSubmissionRoutes';
import {
    formatFileSize,
    formatSubmissionDateTime,
} from '@/lib/submissionPresentation';
import { cn } from '@/lib/utils';
import type { LetterSubmission } from '@/types';

const props = defineProps<{
    submission: LetterSubmission;
    readonly?: boolean;
}>();

const input = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const selectedFileName = computed(() => form.document?.name ?? null);

const form = useForm<{
    _method: 'put';
    document: File | null;
}>({
    _method: 'put',
    document: null,
});

function selectFile(file?: File): void {
    form.clearErrors('document');

    if (!file) {
        form.document = null;

        return;
    }

    if (
        file.type !== 'application/pdf' &&
        !file.name.toLowerCase().endsWith('.pdf')
    ) {
        form.setError('document', 'Pilih dokumen PDF yang valid.');
        form.document = null;

        return;
    }

    if (file.size > 20 * 1024 * 1024) {
        form.setError('document', 'Ukuran dokumen tidak boleh melebihi 20 MB.');
        form.document = null;

        return;
    }

    form.document = file;
}

function handleInput(event: Event): void {
    selectFile((event.target as HTMLInputElement).files?.[0]);
}

function handleDrop(event: DragEvent): void {
    isDragging.value = false;
    selectFile(event.dataTransfer?.files?.[0]);
}

function upload(): void {
    if (!form.document) {
        form.setError('document', 'Pilih dokumen PDF sebelum mengunggah.');
        input.value?.focus();

        return;
    }

    form.post(publicSubmissionRoutes.document(props.submission.public_id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('document');

            if (input.value) {
                input.value.value = '';
            }
        },
    });
}
</script>

<template>
    <section
        class="rounded-[1.75rem] border bg-card p-5 shadow-[0_20px_70px_-52px_rgba(16,58,52,0.55)] sm:p-7"
    >
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div>
                <h2 class="text-xl font-semibold tracking-tight">
                    Dokumen surat
                </h2>
                <p
                    class="mt-2 max-w-2xl text-sm leading-relaxed text-muted-foreground"
                >
                    PDF disimpan di storage privat. Nama file tidak digunakan
                    sebagai lokasi penyimpanan.
                </p>
            </div>
            <span
                class="inline-flex w-fit items-center gap-2 rounded-full bg-brand-teal-soft px-3 py-1.5 text-xs font-semibold text-brand-teal-foreground"
            >
                <ShieldCheck class="size-3.5" />
                Akses privat
            </span>
        </div>

        <div
            v-if="submission.document"
            class="mt-6 flex flex-col gap-4 rounded-2xl border border-brand-teal/25 bg-brand-teal-soft/60 p-4 sm:flex-row sm:items-center"
        >
            <span
                class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground"
                aria-hidden="true"
            >
                <FileCheck2 class="size-5" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold">
                    {{ submission.document.original_filename }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ formatFileSize(submission.document.size_bytes) }} ·
                    Diunggah
                    {{
                        formatSubmissionDateTime(
                            submission.document.uploaded_at,
                        )
                    }}
                </p>
            </div>
            <Button
                v-if="submission.capabilities.can_download_document"
                variant="outline"
                class="min-h-11 cursor-pointer rounded-xl bg-background"
                as-child
            >
                <a
                    :href="
                        publicSubmissionRoutes.document(submission.public_id)
                    "
                >
                    <Download class="size-4" />
                    Unduh
                </a>
            </Button>
        </div>

        <form
            v-if="!readonly && submission.capabilities.can_replace_document"
            class="mt-6"
            @submit.prevent="upload"
        >
            <label
                for="submission_document"
                :class="
                    cn(
                        'flex min-h-48 cursor-pointer flex-col items-center justify-center rounded-2xl border-2 border-dashed px-5 py-8 text-center transition-colors duration-200 outline-none focus-within:border-ring focus-within:ring-3 focus-within:ring-ring/25',
                        isDragging
                            ? 'border-brand-teal bg-brand-teal-soft'
                            : 'border-border bg-muted/25 hover:border-brand-teal/55 hover:bg-brand-teal-soft/35',
                        form.errors.document &&
                            'border-destructive bg-destructive/5',
                    )
                "
                @dragenter.prevent="isDragging = true"
                @dragover.prevent="isDragging = true"
                @dragleave.prevent="isDragging = false"
                @drop.prevent="handleDrop"
            >
                <input
                    id="submission_document"
                    ref="input"
                    type="file"
                    name="document"
                    accept=".pdf,application/pdf"
                    class="sr-only"
                    :aria-invalid="Boolean(form.errors.document)"
                    aria-describedby="document_hint document_error"
                    @change="handleInput"
                />
                <span
                    class="flex size-14 items-center justify-center rounded-2xl bg-brand-amber-soft text-brand-amber-foreground"
                    aria-hidden="true"
                >
                    <UploadCloud class="size-6" />
                </span>
                <span class="mt-4 text-sm font-semibold">
                    {{
                        selectedFileName ||
                        'Tarik PDF ke sini atau pilih dari perangkat'
                    }}
                </span>
                <span
                    id="document_hint"
                    class="mt-2 text-xs text-muted-foreground"
                >
                    Hanya PDF, maksimal 20 MB.
                </span>
            </label>

            <InputError
                id="document_error"
                :message="form.errors.document"
                class="mt-2"
                role="alert"
            />

            <div
                v-if="form.progress"
                class="mt-4"
                aria-live="polite"
                aria-label="Progres upload dokumen"
            >
                <div
                    class="mb-2 flex items-center justify-between text-xs font-medium"
                >
                    <span>Mengunggah dokumen</span>
                    <span class="tabular-nums"
                        >{{ form.progress.percentage }}%</span
                    >
                </div>
                <progress
                    class="h-2 w-full overflow-hidden rounded-full accent-primary"
                    :value="form.progress.percentage"
                    max="100"
                />
            </div>

            <div class="mt-5 flex justify-end">
                <Button
                    type="submit"
                    size="lg"
                    class="min-h-11 cursor-pointer rounded-xl px-6"
                    :disabled="form.processing || !form.document"
                >
                    <FileUp class="size-4" />
                    {{
                        form.processing
                            ? 'Mengunggah…'
                            : submission.document
                              ? 'Ganti dokumen'
                              : 'Unggah dokumen'
                    }}
                </Button>
            </div>
        </form>
    </section>
</template>
