<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Building2, Clock3 } from '@lucide/vue';
import IntakeStatusBadge from '@/components/back-office/intake/IntakeStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { intakeRoutes, shortSubmissionId } from '@/lib/intakePresentation';
import { formatSubmissionDateTime } from '@/lib/submissionPresentation';
import type { IntakeSubmission } from '@/types';

defineProps<{ submission: IntakeSubmission }>();
</script>

<template>
    <header class="rounded-3xl border bg-card p-5 shadow-sm sm:p-7">
        <Button as-child variant="ghost" class="mb-4 -ml-3 min-h-11">
            <Link :href="intakeRoutes.index">
                <ArrowLeft class="size-4" aria-hidden="true" />
                Kembali ke antrean
            </Link>
        </Button>

        <div
            class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
        >
            <div class="max-w-3xl">
                <div class="flex flex-wrap items-center gap-2">
                    <IntakeStatusBadge :status="submission.status" />
                    <span class="font-mono text-xs text-muted-foreground">
                        {{ shortSubmissionId(submission.public_id) }}
                    </span>
                </div>
                <h1
                    class="mt-4 text-2xl font-semibold tracking-tight sm:text-3xl"
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
                        Masuk
                        {{ formatSubmissionDateTime(submission.submitted_at) }}
                    </span>
                </div>
            </div>

            <div
                class="rounded-2xl border border-blue-200 bg-blue-50/70 px-4 py-3 text-sm text-blue-950 dark:border-blue-900 dark:bg-blue-950/25 dark:text-blue-100"
            >
                <p class="font-semibold">Tahap saat ini</p>
                <p class="mt-1 text-blue-800 dark:text-blue-200">
                    Pemeriksaan awal oleh petugas Bagian Umum
                </p>
            </div>
        </div>
    </header>
</template>
