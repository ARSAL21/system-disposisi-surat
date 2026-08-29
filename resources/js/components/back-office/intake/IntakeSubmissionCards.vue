<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Building2, CalendarDays, FileText } from '@lucide/vue';
import IntakeStatusBadge from '@/components/back-office/intake/IntakeStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { shortSubmissionId } from '@/lib/intakePresentation';
import {
    formatFileSize,
    formatSubmissionDateTime,
} from '@/lib/submissionPresentation';
import type { IntakeSubmission } from '@/types';

defineProps<{ submissions: IntakeSubmission[] }>();
</script>

<template>
    <div class="grid gap-3 p-3 lg:hidden">
        <article
            v-for="submission in submissions"
            :key="submission.public_id"
            class="rounded-2xl border bg-card p-4 shadow-xs"
        >
            <div class="flex flex-wrap items-start justify-between gap-3">
                <IntakeStatusBadge :status="submission.status" />
                <span class="font-mono text-xs text-muted-foreground">
                    {{ shortSubmissionId(submission.public_id) }}
                </span>
            </div>
            <h2 class="mt-4 leading-6 font-semibold">
                {{ submission.subject }}
            </h2>

            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                <div class="flex gap-3">
                    <Building2
                        class="mt-0.5 size-4 shrink-0 text-violet-600"
                        aria-hidden="true"
                    />
                    <div>
                        <dt class="text-xs text-muted-foreground">Pengirim</dt>
                        <dd class="mt-1 font-medium">
                            {{ submission.sender_organization_name }}
                        </dd>
                    </div>
                </div>
                <div class="flex gap-3">
                    <CalendarDays
                        class="mt-0.5 size-4 shrink-0 text-blue-600"
                        aria-hidden="true"
                    />
                    <div>
                        <dt class="text-xs text-muted-foreground">Masuk</dt>
                        <dd class="mt-1 font-medium tabular-nums">
                            {{
                                formatSubmissionDateTime(
                                    submission.submitted_at,
                                )
                            }}
                        </dd>
                    </div>
                </div>
                <div class="flex gap-3 sm:col-span-2">
                    <FileText
                        class="mt-0.5 size-4 shrink-0 text-blue-600"
                        aria-hidden="true"
                    />
                    <div class="min-w-0">
                        <dt class="text-xs text-muted-foreground">Dokumen</dt>
                        <dd
                            v-if="submission.document"
                            class="mt-1 truncate font-medium"
                        >
                            {{ submission.document.original_filename }} ·
                            {{ formatFileSize(submission.document.size_bytes) }}
                        </dd>
                        <dd
                            v-else
                            class="mt-1 text-xs font-medium text-destructive"
                        >
                            Dokumen tidak tersedia
                        </dd>
                    </div>
                </div>
            </dl>

            <Button as-child variant="outline" class="mt-5 min-h-11 w-full">
                <Link :href="submission.links.show">
                    Buka pemeriksaan
                    <ArrowRight class="size-4" aria-hidden="true" />
                </Link>
            </Button>
        </article>
    </div>
</template>
