<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, FilePlus2, Inbox } from '@lucide/vue';
import SubmissionStatusBadge from '@/components/public/SubmissionStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { formatSubmissionDateTime } from '@/lib/submissionPresentation';
import publicRoutes from '@/routes/public';
import type { LetterSubmission } from '@/types';

defineProps<{ submissions: LetterSubmission[] }>();
</script>

<template>
    <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
        <header
            class="flex items-center justify-between gap-4 border-b px-5 py-4 sm:px-6"
        >
            <div>
                <h2 class="font-semibold tracking-tight">Surat terbaru</h2>
                <p class="mt-1 text-xs text-muted-foreground">
                    Aktivitas submission terakhir Anda.
                </p>
            </div>
            <Button
                variant="ghost"
                class="min-h-11 cursor-pointer rounded-xl"
                as-child
            >
                <Link :href="publicRoutes.submissions.index()">
                    Lihat semua
                    <ArrowRight class="size-4" />
                </Link>
            </Button>
        </header>

        <div v-if="submissions.length" class="divide-y">
            <Link
                v-for="submission in submissions"
                :key="submission.public_id"
                :href="publicRoutes.submissions.show(submission.public_id)"
                class="group flex min-h-20 items-center gap-4 px-5 py-4 transition-colors hover:bg-muted/60 focus-visible:bg-muted focus-visible:outline-none sm:px-6"
            >
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-secondary text-secondary-foreground"
                    aria-hidden="true"
                >
                    <Inbox class="size-4" />
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold">{{
                        submission.subject
                    }}</span>
                    <span
                        class="mt-1 block truncate text-xs text-muted-foreground"
                    >
                        {{ submission.sender_organization_name }} ·
                        {{ formatSubmissionDateTime(submission.created_at) }}
                    </span>
                </span>
                <SubmissionStatusBadge
                    class="hidden sm:inline-flex"
                    :status="submission.status"
                />
                <ArrowRight
                    class="size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5"
                    aria-hidden="true"
                />
            </Link>
        </div>

        <div
            v-else
            class="flex min-h-64 flex-col items-center justify-center px-6 py-10 text-center"
        >
            <span
                class="flex size-12 items-center justify-center rounded-2xl bg-muted text-muted-foreground"
            >
                <Inbox class="size-5" />
            </span>
            <h3 class="mt-4 font-semibold">Belum ada surat</h3>
            <p class="mt-2 max-w-sm text-sm leading-6 text-muted-foreground">
                Buat draft pertama untuk memulai proses pengiriman surat.
            </p>
            <Button class="mt-5 min-h-11 cursor-pointer rounded-xl" as-child>
                <Link :href="publicRoutes.submissions.create()">
                    <FilePlus2 class="size-4" />
                    Buat surat
                </Link>
            </Button>
        </div>
    </section>
</template>
