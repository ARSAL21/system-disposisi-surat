<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { FileUp, UploadCloud } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { publicSubmissionRoutes } from '@/lib/publicSubmissionRoutes';
import { cn } from '@/lib/utils';
import type { LetterSubmission } from '@/types';

const props = defineProps<{ submission: LetterSubmission }>();
const input = ref<HTMLInputElement | null>(null);
const isDragging = ref(false);
const form = useForm<{ _method: 'put'; document: File | null }>({
    _method: 'put',
    document: null,
});
const selectedFileName = computed(() => form.document?.name ?? null);

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

function handleDrop(event: DragEvent): void {
    isDragging.value = false;
    selectFile(event.dataTransfer?.files?.[0]);
}

function handleInput(event: Event): void {
    selectFile((event.target as HTMLInputElement).files?.[0]);
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
    <form @submit.prevent="upload">
        <label
            for="submission_document"
            :class="
                cn(
                    'flex min-h-44 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed px-5 py-7 text-center transition-colors outline-none focus-within:border-ring focus-within:ring-3 focus-within:ring-ring/25',
                    isDragging
                        ? 'border-primary bg-secondary'
                        : 'border-border bg-muted/30 hover:border-primary/50 hover:bg-secondary/60',
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
                class="flex size-12 items-center justify-center rounded-xl bg-info text-info-foreground"
                aria-hidden="true"
            >
                <UploadCloud class="size-5" />
            </span>
            <span class="mt-4 text-sm font-semibold">{{
                selectedFileName ||
                'Tarik PDF ke sini atau pilih dari perangkat'
            }}</span>
            <span id="document_hint" class="mt-2 text-xs text-muted-foreground"
                >Hanya PDF, maksimal 20 MB.</span
            >
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
                <span>Mengunggah dokumen</span
                ><span class="tabular-nums"
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
                class="min-h-11 cursor-pointer rounded-xl px-5"
                :disabled="form.processing || !form.document"
            >
                <FileUp class="size-4" />{{
                    form.processing
                        ? 'Mengunggah…'
                        : submission.document
                          ? 'Ganti dokumen'
                          : 'Unggah dokumen'
                }}
            </Button>
        </div>
    </form>
</template>
