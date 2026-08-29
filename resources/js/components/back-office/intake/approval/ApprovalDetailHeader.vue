<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Building2, Clock3, UserRoundCheck } from '@lucide/vue';
import ApprovalStatusBadge from '@/components/back-office/intake/approval/ApprovalStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { approvalRoutes } from '@/lib/intakeApprovalPresentation';
import { shortSubmissionId } from '@/lib/intakePresentation';
import { formatSubmissionDateTime } from '@/lib/submissionPresentation';
import type { ApprovalSubmission } from '@/types';

defineProps<{ submission: ApprovalSubmission; preview?: boolean }>();
</script>

<template>
    <header class="rounded-3xl border bg-card p-5 shadow-sm sm:p-7">
        <Button as-child variant="ghost" class="mb-4 -ml-3 min-h-11">
            <Link :href="approvalRoutes.index">
                <ArrowLeft class="size-4" aria-hidden="true" />
                Kembali ke persetujuan surat
            </Link>
        </Button>

        <div
            class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
        >
            <div class="max-w-3xl">
                <div class="flex flex-wrap items-center gap-2">
                    <ApprovalStatusBadge :status="submission.status" />
                    <span
                        v-if="preview"
                        class="rounded-full bg-violet-500/10 px-2.5 py-1 text-xs font-semibold text-violet-700 dark:text-violet-300"
                    >
                        Pratinjau lokal
                    </span>
                    <span class="font-mono text-xs text-muted-foreground">
                        {{ shortSubmissionId(submission.public_id) }}
                    </span>
                </div>
                <h1
                    class="mt-4 text-2xl leading-tight font-semibold tracking-tight sm:text-3xl"
                >
                    {{ submission.subject }}
                </h1>
                <div
                    class="mt-4 flex flex-col gap-2 text-sm text-muted-foreground sm:flex-row sm:flex-wrap sm:gap-x-5"
                >
                    <span class="flex items-center gap-2">
                        <Building2 class="size-4" aria-hidden="true" />
                        {{ submission.sender_organization_name }}
                    </span>
                    <span class="flex items-center gap-2 tabular-nums">
                        <Clock3 class="size-4" aria-hidden="true" />
                        Diajukan
                        {{ formatSubmissionDateTime(submission.submitted_at) }}
                    </span>
                </div>
            </div>

            <div
                class="rounded-2xl border border-violet-200 bg-violet-50/70 px-4 py-3 text-sm text-violet-950 dark:border-violet-900 dark:bg-violet-950/25 dark:text-violet-100"
            >
                <p class="flex items-center gap-2 font-semibold">
                    <UserRoundCheck class="size-4" aria-hidden="true" />
                    Telah diperiksa petugas
                </p>
                <p class="mt-1 text-violet-800 dark:text-violet-200">
                    {{ submission.screening_review.reviewed_by }} ·
                    {{
                        formatSubmissionDateTime(
                            submission.screening_review.reviewed_at,
                        )
                    }}
                </p>
            </div>
        </div>
    </header>
</template>
