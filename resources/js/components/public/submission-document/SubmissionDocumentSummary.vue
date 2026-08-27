<script setup lang="ts">
import { Download, FileCheck2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { publicSubmissionRoutes } from '@/lib/publicSubmissionRoutes';
import {
    formatFileSize,
    formatSubmissionDateTime,
} from '@/lib/submissionPresentation';
import type { LetterSubmission } from '@/types';

defineProps<{ submission: LetterSubmission }>();
</script>

<template>
    <div
        v-if="submission.document"
        class="flex flex-col gap-4 rounded-xl border bg-secondary/70 p-4 sm:flex-row sm:items-center"
    >
        <span
            class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground"
            aria-hidden="true"
        >
            <FileCheck2 class="size-5" />
        </span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold">
                {{ submission.document.original_filename }}
            </p>
            <p class="mt-1 text-xs text-muted-foreground">
                {{ formatFileSize(submission.document.size_bytes) }} · Diunggah
                {{ formatSubmissionDateTime(submission.document.uploaded_at) }}
            </p>
        </div>
        <Button
            v-if="submission.capabilities.can_download_document"
            variant="outline"
            class="min-h-11 cursor-pointer rounded-xl bg-card"
            as-child
        >
            <a :href="publicSubmissionRoutes.document(submission.public_id)">
                <Download class="size-4" />Unduh
            </a>
        </Button>
    </div>
</template>
