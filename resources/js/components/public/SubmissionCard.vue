<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowUpRight, FileCheck2, FileClock, FileText } from '@lucide/vue';
import SubmissionStatusBadge from '@/components/public/SubmissionStatusBadge.vue';
import { publicSubmissionRoutes } from '@/lib/publicSubmissionRoutes';
import {
    formatFileSize,
    formatSubmissionDateTime,
} from '@/lib/submissionPresentation';
import type { LetterSubmission } from '@/types';

defineProps<{
    submission: LetterSubmission;
    compact?: boolean;
}>();
</script>

<template>
    <article
        class="group relative overflow-hidden rounded-[1.5rem] border bg-card p-5 shadow-[0_20px_60px_-48px_rgba(18,48,44,0.55)] transition duration-300 hover:-translate-y-0.5 hover:border-brand-teal/40 hover:shadow-[0_24px_70px_-44px_rgba(34,126,115,0.55)]"
    >
        <div class="flex items-start justify-between gap-4">
            <div
                class="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-brand-teal-soft text-brand-teal-foreground transition-transform duration-500 ease-out group-hover:scale-105"
                aria-hidden="true"
            >
                <FileCheck2 v-if="submission.document" class="size-5" />
                <FileClock v-else class="size-5" />
            </div>
            <SubmissionStatusBadge :status="submission.status" />
        </div>

        <div class="mt-5">
            <p class="text-xs font-medium text-muted-foreground">
                {{ submission.external_letter_number || 'Tanpa nomor surat' }}
            </p>
            <h3
                class="mt-2 text-lg leading-snug font-semibold tracking-tight text-balance"
            >
                {{ submission.subject }}
            </h3>
            <p
                v-if="!compact"
                class="mt-2 line-clamp-2 text-sm text-muted-foreground"
            >
                {{ submission.sender_organization_name }}
            </p>
        </div>

        <div
            class="mt-5 flex flex-wrap items-center gap-x-4 gap-y-2 border-t pt-4 text-xs text-muted-foreground"
        >
            <span>{{ formatSubmissionDateTime(submission.created_at) }}</span>
            <span
                v-if="submission.document"
                class="inline-flex items-center gap-1.5"
            >
                <FileText class="size-3.5" aria-hidden="true" />
                {{ formatFileSize(submission.document.size_bytes) }}
            </span>
        </div>

        <Link
            :href="publicSubmissionRoutes.show(submission.public_id)"
            class="mt-5 inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-primary transition-colors outline-none hover:text-brand-teal focus-visible:rounded-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
        >
            Lihat detail
            <ArrowUpRight
                class="size-4 transition-transform duration-300 group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                aria-hidden="true"
            />
        </Link>
    </article>
</template>
