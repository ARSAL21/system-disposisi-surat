<script setup lang="ts">
import { ShieldCheck } from '@lucide/vue';
import SubmissionDocumentSummary from '@/components/public/submission-document/SubmissionDocumentSummary.vue';
import SubmissionDocumentUploader from '@/components/public/submission-document/SubmissionDocumentUploader.vue';
import type { LetterSubmission } from '@/types';

defineProps<{
    submission: LetterSubmission;
    readonly?: boolean;
}>();
</script>

<template>
    <section class="rounded-2xl border bg-card p-5 shadow-sm sm:p-6">
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
        >
            <div>
                <h2 class="text-lg font-semibold tracking-tight">
                    Dokumen surat
                </h2>
                <p
                    class="mt-1 max-w-2xl text-sm leading-6 text-muted-foreground"
                >
                    PDF disimpan secara privat dan hanya dapat diakses setelah
                    authorization server.
                </p>
            </div>
            <span
                class="inline-flex w-fit items-center gap-2 rounded-full bg-success px-3 py-1.5 text-xs font-semibold text-success-foreground"
            >
                <ShieldCheck class="size-3.5" />Akses privat
            </span>
        </div>

        <div class="mt-6 space-y-6">
            <SubmissionDocumentSummary :submission="submission" />
            <SubmissionDocumentUploader
                v-if="!readonly && submission.capabilities.can_replace_document"
                :submission="submission"
            />
        </div>
    </section>
</template>
