<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Building2, FileText } from '@lucide/vue';
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
    <div class="hidden overflow-x-auto lg:block">
        <table class="w-full min-w-5xl text-left text-sm">
            <caption class="sr-only">
                Antrean pengajuan surat untuk pemeriksaan awal Bagian Umum
            </caption>
            <thead class="border-b bg-slate-50/75 dark:bg-slate-900/50">
                <tr
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    <th scope="col" class="px-5 py-3.5">Pengajuan surat</th>
                    <th scope="col" class="px-5 py-3.5">Pengirim</th>
                    <th scope="col" class="px-5 py-3.5">Dokumen</th>
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
                    <td class="max-w-md px-5 py-4 align-top">
                        <p class="leading-6 font-semibold">
                            {{ submission.subject }}
                        </p>
                        <p class="mt-1 font-mono text-xs text-muted-foreground">
                            {{ shortSubmissionId(submission.public_id) }}
                        </p>
                        <p
                            class="mt-2 text-xs text-muted-foreground tabular-nums"
                        >
                            Masuk
                            {{
                                formatSubmissionDateTime(
                                    submission.submitted_at,
                                )
                            }}
                        </p>
                    </td>
                    <td class="px-5 py-4 align-top">
                        <div class="flex min-w-52 gap-3">
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-violet-500/10 text-violet-700 dark:text-violet-300"
                            >
                                <Building2 class="size-4" aria-hidden="true" />
                            </span>
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
                        <div class="flex min-w-44 items-start gap-2">
                            <FileText
                                class="mt-0.5 size-4 shrink-0 text-blue-700 dark:text-blue-300"
                                aria-hidden="true"
                            />
                            <div>
                                <p
                                    v-if="submission.document"
                                    class="max-w-48 truncate font-medium"
                                >
                                    {{ submission.document.original_filename }}
                                </p>
                                <p
                                    v-if="submission.document"
                                    class="mt-1 text-xs text-muted-foreground"
                                >
                                    {{
                                        formatFileSize(
                                            submission.document.size_bytes,
                                        )
                                    }}
                                </p>
                                <p
                                    v-else
                                    class="text-xs font-medium text-destructive"
                                >
                                    Dokumen tidak tersedia
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 align-top">
                        <IntakeStatusBadge :status="submission.status" />
                    </td>
                    <td class="px-5 py-4 text-right align-top">
                        <Button as-child variant="outline" class="min-h-11">
                            <Link :href="submission.links.show">
                                Tinjau
                                <ArrowRight class="size-4" aria-hidden="true" />
                            </Link>
                        </Button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
