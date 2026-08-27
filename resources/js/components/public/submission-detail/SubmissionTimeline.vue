<script setup lang="ts">
import { CheckCircle2, Clock3, FileText } from '@lucide/vue';
import { formatSubmissionDateTime } from '@/lib/submissionPresentation';
import type { LetterSubmission } from '@/types';

defineProps<{ submission: LetterSubmission }>();
</script>

<template>
    <aside class="rounded-2xl border bg-card p-5 shadow-sm xl:sticky xl:top-20">
        <h2 class="font-semibold tracking-tight">Jejak submission</h2>
        <ol class="mt-6 space-y-0">
            <li class="relative flex gap-4 pb-7">
                <span
                    class="absolute top-9 bottom-0 left-4 w-px bg-border"
                    aria-hidden="true"
                />
                <span
                    class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full bg-success text-success-foreground"
                >
                    <CheckCircle2 class="size-4" />
                </span>
                <div>
                    <p class="text-sm font-semibold">Draft dibuat</p>
                    <p class="mt-1 text-xs leading-5 text-muted-foreground">
                        {{ formatSubmissionDateTime(submission.created_at) }}
                    </p>
                </div>
            </li>
            <li class="relative flex gap-4 pb-7">
                <span
                    class="absolute top-9 bottom-0 left-4 w-px bg-border"
                    aria-hidden="true"
                />
                <span
                    class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full bg-info text-info-foreground"
                >
                    <FileText class="size-4" />
                </span>
                <div>
                    <p class="text-sm font-semibold">Dokumen PDF</p>
                    <p class="mt-1 text-xs leading-5 text-muted-foreground">
                        {{
                            submission.document
                                ? formatSubmissionDateTime(
                                      submission.document.uploaded_at,
                                  )
                                : 'Belum diunggah'
                        }}
                    </p>
                </div>
            </li>
            <li class="flex gap-4">
                <span
                    :class="[
                        'flex size-8 shrink-0 items-center justify-center rounded-full',
                        submission.submitted_at
                            ? 'bg-accent text-accent-foreground'
                            : 'bg-muted text-muted-foreground',
                    ]"
                >
                    <Clock3 class="size-4" />
                </span>
                <div>
                    <p class="text-sm font-semibold">Dikirim ke Bagian Umum</p>
                    <p class="mt-1 text-xs leading-5 text-muted-foreground">
                        {{
                            submission.submitted_at
                                ? formatSubmissionDateTime(
                                      submission.submitted_at,
                                  )
                                : 'Menunggu konfirmasi Anda'
                        }}
                    </p>
                </div>
            </li>
        </ol>
    </aside>
</template>
