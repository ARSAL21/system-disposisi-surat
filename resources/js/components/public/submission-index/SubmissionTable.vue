<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FileText } from '@lucide/vue';
import SubmissionStatusBadge from '@/components/public/SubmissionStatusBadge.vue';
import {
    formatFileSize,
    formatSubmissionDateTime,
} from '@/lib/submissionPresentation';
import publicRoutes from '@/routes/public';
import type { LetterSubmission } from '@/types';

defineProps<{ submissions: LetterSubmission[] }>();
</script>

<template>
    <div
        class="hidden overflow-hidden rounded-2xl border bg-card shadow-sm md:block"
    >
        <table class="w-full table-fixed border-collapse text-left">
            <caption class="sr-only">
                Daftar pengajuan surat milik Anda
            </caption>
            <thead
                class="border-b bg-muted/60 text-xs font-semibold text-muted-foreground"
            >
                <tr>
                    <th scope="col" class="w-[45%] px-6 py-4">Surat</th>
                    <th scope="col" class="w-[18%] px-6 py-4">Status</th>
                    <th scope="col" class="w-[20%] px-6 py-4">Dokumen</th>
                    <th scope="col" class="w-[17%] px-6 py-4 text-right">
                        Dibuat
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr
                    v-for="submission in submissions"
                    :key="submission.public_id"
                    class="group transition-colors hover:bg-muted/50"
                >
                    <td class="px-6 py-5 align-top">
                        <Link
                            :href="
                                publicRoutes.submissions.show(
                                    submission.public_id,
                                )
                            "
                            class="block rounded-lg outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-4"
                        >
                            <span
                                class="line-clamp-2 font-semibold tracking-tight group-hover:text-primary"
                                >{{ submission.subject }}</span
                            >
                            <span
                                class="mt-1.5 block truncate text-sm text-muted-foreground"
                                >{{ submission.sender_organization_name }}</span
                            >
                            <span
                                class="mt-1 block truncate text-xs text-muted-foreground"
                                >{{
                                    submission.external_letter_number ||
                                    'Tanpa nomor surat'
                                }}</span
                            >
                        </Link>
                    </td>
                    <td class="px-6 py-5 align-top">
                        <SubmissionStatusBadge :status="submission.status" />
                    </td>
                    <td class="px-6 py-5 align-top text-sm">
                        <span
                            v-if="submission.document"
                            class="inline-flex min-w-0 items-center gap-2"
                        >
                            <FileText class="size-4 shrink-0 text-primary" />
                            <span>
                                <span
                                    class="block max-w-36 truncate font-medium"
                                    >{{
                                        submission.document.original_filename
                                    }}</span
                                >
                                <span
                                    class="mt-1 block text-xs text-muted-foreground"
                                    >{{
                                        formatFileSize(
                                            submission.document.size_bytes,
                                        )
                                    }}</span
                                >
                            </span>
                        </span>
                        <span v-else class="text-muted-foreground"
                            >Belum diunggah</span
                        >
                    </td>
                    <td
                        class="px-6 py-5 text-right align-top text-sm text-muted-foreground"
                    >
                        {{ formatSubmissionDateTime(submission.created_at) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
