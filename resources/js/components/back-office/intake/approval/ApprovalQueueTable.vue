<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Building2, CalendarClock, FileText } from '@lucide/vue';
import ApprovalStatusBadge from '@/components/back-office/intake/approval/ApprovalStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { shortSubmissionId } from '@/lib/intakePresentation';
import {
    formatFileSize,
    formatSubmissionDateTime,
} from '@/lib/submissionPresentation';
import type { ApprovalSubmission } from '@/types';

defineProps<{ submissions: ApprovalSubmission[] }>();
</script>

<template>
    <div class="hidden overflow-x-auto lg:block">
        <table class="w-full min-w-5xl text-left text-sm">
            <caption class="sr-only">
                Daftar surat untuk keputusan Kepala Bagian Umum
            </caption>
            <thead class="border-b bg-slate-50/75 dark:bg-slate-900/50">
                <tr
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    <th scope="col" class="px-5 py-3.5">Surat</th>
                    <th scope="col" class="px-5 py-3.5">Pengirim</th>
                    <th scope="col" class="px-5 py-3.5">Pemeriksaan petugas</th>
                    <th scope="col" class="px-5 py-3.5">Status</th>
                    <th scope="col" class="px-5 py-3.5 text-right">Tindakan</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr
                    v-for="submission in submissions"
                    :key="submission.public_id"
                    class="transition-colors hover:bg-blue-50/45 motion-reduce:transition-none dark:hover:bg-blue-950/15"
                >
                    <td class="max-w-sm px-5 py-4 align-top">
                        <div class="flex gap-3">
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-blue-700 dark:text-blue-300"
                            >
                                <FileText class="size-4" aria-hidden="true" />
                            </span>
                            <div class="min-w-0">
                                <p class="line-clamp-2 leading-5 font-semibold">
                                    {{ submission.subject }}
                                </p>
                                <p
                                    class="mt-1 font-mono text-xs text-muted-foreground"
                                >
                                    {{
                                        shortSubmissionId(submission.public_id)
                                    }}
                                </p>
                                <p
                                    v-if="submission.document"
                                    class="mt-2 text-xs text-muted-foreground"
                                >
                                    {{
                                        formatFileSize(
                                            submission.document.size_bytes,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 align-top">
                        <div class="flex min-w-52 gap-3">
                            <Building2
                                class="mt-0.5 size-4 shrink-0 text-violet-600 dark:text-violet-300"
                                aria-hidden="true"
                            />
                            <div>
                                <p class="leading-5 font-semibold">
                                    {{ submission.sender_organization_name }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{
                                        submission.external_letter_number ??
                                        'Tanpa nomor surat'
                                    }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 align-top">
                        <div class="flex min-w-48 gap-3">
                            <CalendarClock
                                class="mt-0.5 size-4 shrink-0 text-blue-600 dark:text-blue-300"
                                aria-hidden="true"
                            />
                            <div>
                                <p class="font-medium">
                                    {{
                                        submission.screening_review.reviewed_by
                                    }}
                                </p>
                                <p
                                    class="mt-1 text-xs text-muted-foreground tabular-nums"
                                >
                                    {{
                                        formatSubmissionDateTime(
                                            submission.screening_review
                                                .reviewed_at,
                                        )
                                    }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 align-top">
                        <ApprovalStatusBadge :status="submission.status" />
                    </td>
                    <td class="px-5 py-4 text-right align-top">
                        <Button as-child variant="outline" class="min-h-11">
                            <Link :href="submission.links.show">
                                {{
                                    submission.capabilities.can_decide
                                        ? 'Tinjau dan putuskan'
                                        : 'Lihat riwayat'
                                }}
                                <ArrowRight class="size-4" aria-hidden="true" />
                            </Link>
                        </Button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
