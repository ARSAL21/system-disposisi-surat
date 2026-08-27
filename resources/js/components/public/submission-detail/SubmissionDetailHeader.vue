<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, FilePenLine } from '@lucide/vue';
import SubmissionStatusBadge from '@/components/public/SubmissionStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { getSubmissionStatusPresentation } from '@/lib/submissionPresentation';
import publicRoutes from '@/routes/public';
import type { LetterSubmission } from '@/types';

defineProps<{ submission: LetterSubmission }>();
</script>

<template>
    <header>
        <Button
            variant="ghost"
            class="min-h-11 cursor-pointer rounded-xl"
            as-child
        >
            <Link :href="publicRoutes.submissions.index()">
                <ArrowLeft class="size-4" />Surat saya
            </Link>
        </Button>
        <div
            class="mt-6 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"
        >
            <div class="max-w-3xl">
                <SubmissionStatusBadge
                    :status="submission.status"
                    show-description
                />
                <h1
                    class="mt-5 text-2xl leading-tight font-semibold tracking-tight sm:text-3xl"
                >
                    {{ submission.subject }}
                </h1>
                <p
                    class="mt-3 text-sm leading-6 text-muted-foreground sm:text-base"
                >
                    {{
                        getSubmissionStatusPresentation(submission.status)
                            .description
                    }}
                </p>
            </div>
            <Button
                v-if="submission.capabilities.can_update"
                class="min-h-11 cursor-pointer rounded-xl px-5"
                as-child
            >
                <Link
                    :href="publicRoutes.submissions.edit(submission.public_id)"
                >
                    <FilePenLine class="size-4" />Lengkapi draft
                </Link>
            </Button>
        </div>
    </header>
</template>
