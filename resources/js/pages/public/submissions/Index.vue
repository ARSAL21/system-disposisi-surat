<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, FilePlus2, FileText, Inbox } from '@lucide/vue';
import SubmissionCard from '@/components/public/SubmissionCard.vue';
import SubmissionStatusBadge from '@/components/public/SubmissionStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { publicSubmissionRoutes } from '@/lib/publicSubmissionRoutes';
import {
    formatFileSize,
    formatSubmissionDateTime,
} from '@/lib/submissionPresentation';
import type { PaginatedSubmissions } from '@/types';

defineProps<{
    submissions: PaginatedSubmissions;
}>();
</script>

<template>
    <Head title="Submission Saya" />

    <div class="mx-auto max-w-7xl px-5 py-14 sm:px-8 md:py-20">
        <header
            class="flex flex-col gap-7 lg:flex-row lg:items-end lg:justify-between"
        >
            <div class="max-w-4xl">
                <p class="text-sm font-semibold text-brand-teal-foreground">
                    Submission Saya
                </p>
                <h1
                    class="mt-3 text-4xl font-semibold tracking-[-0.045em] text-balance sm:text-6xl"
                >
                    Seluruh surat yang Anda kirim, dalam satu pandangan.
                </h1>
                <p
                    class="mt-6 max-w-2xl text-base leading-relaxed text-muted-foreground sm:text-lg"
                >
                    Daftar ini dibatasi server hanya untuk submission milik akun
                    Anda.
                </p>
            </div>
            <Button
                size="lg"
                class="min-h-12 cursor-pointer rounded-xl px-6"
                as-child
            >
                <Link :href="publicSubmissionRoutes.create">
                    <FilePlus2 class="size-4" />
                    Buat submission
                </Link>
            </Button>
        </header>

        <section v-if="submissions.data.length" class="mt-12">
            <div class="grid gap-4 md:hidden">
                <SubmissionCard
                    v-for="submission in submissions.data"
                    :key="submission.public_id"
                    :submission="submission"
                />
            </div>

            <div
                class="hidden overflow-hidden rounded-[1.75rem] border bg-card shadow-[0_28px_90px_-64px_rgba(15,60,53,0.6)] md:block"
            >
                <table class="w-full table-fixed border-collapse text-left">
                    <caption class="sr-only">
                        Daftar submission surat milik Anda
                    </caption>
                    <thead
                        class="border-b bg-muted/45 text-xs font-semibold text-muted-foreground"
                    >
                        <tr>
                            <th scope="col" class="w-[45%] px-6 py-4">Surat</th>
                            <th scope="col" class="w-[18%] px-6 py-4">
                                Status
                            </th>
                            <th scope="col" class="w-[20%] px-6 py-4">
                                Dokumen
                            </th>
                            <th
                                scope="col"
                                class="w-[17%] px-6 py-4 text-right"
                            >
                                Dibuat
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="submission in submissions.data"
                            :key="submission.public_id"
                            class="group transition-colors duration-200 hover:bg-brand-teal-soft/35"
                        >
                            <td class="px-6 py-5 align-top">
                                <Link
                                    :href="
                                        publicSubmissionRoutes.show(
                                            submission.public_id,
                                        )
                                    "
                                    class="block rounded-lg outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-4"
                                >
                                    <span
                                        class="line-clamp-2 font-semibold tracking-tight group-hover:text-primary"
                                    >
                                        {{ submission.subject }}
                                    </span>
                                    <span
                                        class="mt-1.5 block truncate text-sm text-muted-foreground"
                                    >
                                        {{
                                            submission.sender_organization_name
                                        }}
                                    </span>
                                    <span
                                        class="mt-1 block truncate text-xs text-muted-foreground"
                                    >
                                        {{
                                            submission.external_letter_number ||
                                            'Tanpa nomor surat'
                                        }}
                                    </span>
                                </Link>
                            </td>
                            <td class="px-6 py-5 align-top">
                                <SubmissionStatusBadge
                                    :status="submission.status"
                                />
                            </td>
                            <td class="px-6 py-5 align-top text-sm">
                                <span
                                    v-if="submission.document"
                                    class="inline-flex min-w-0 items-center gap-2"
                                >
                                    <FileText
                                        class="size-4 shrink-0 text-primary"
                                    />
                                    <span>
                                        <span
                                            class="block max-w-36 truncate font-medium"
                                        >
                                            {{
                                                submission.document
                                                    .original_filename
                                            }}
                                        </span>
                                        <span
                                            class="mt-1 block text-xs text-muted-foreground"
                                        >
                                            {{
                                                formatFileSize(
                                                    submission.document
                                                        .size_bytes,
                                                )
                                            }}
                                        </span>
                                    </span>
                                </span>
                                <span v-else class="text-muted-foreground"
                                    >Belum diunggah</span
                                >
                            </td>
                            <td
                                class="px-6 py-5 text-right align-top text-sm text-muted-foreground"
                            >
                                {{
                                    formatSubmissionDateTime(
                                        submission.created_at,
                                    )
                                }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <nav
                v-if="submissions.meta.last_page > 1"
                class="mt-8 flex flex-col gap-4 rounded-2xl border bg-card px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                aria-label="Paginasi submission"
            >
                <p class="text-sm text-muted-foreground">
                    Menampilkan
                    <span class="font-semibold text-foreground"
                        >{{ submissions.meta.from }}–{{
                            submissions.meta.to
                        }}</span
                    >
                    dari
                    <span class="font-semibold text-foreground">{{
                        submissions.meta.total
                    }}</span>
                    submission
                </p>
                <div class="flex items-center gap-2">
                    <Button
                        variant="outline"
                        class="min-h-11 flex-1 cursor-pointer rounded-xl sm:flex-none"
                        :disabled="!submissions.links.prev"
                        as-child
                    >
                        <Link
                            v-if="submissions.links.prev"
                            :href="submissions.links.prev"
                            preserve-scroll
                        >
                            <ArrowLeft class="size-4" />
                            Sebelumnya
                        </Link>
                        <span v-else>
                            <ArrowLeft class="size-4" />
                            Sebelumnya
                        </span>
                    </Button>
                    <span class="px-2 text-sm font-medium tabular-nums">
                        {{ submissions.meta.current_page }} /
                        {{ submissions.meta.last_page }}
                    </span>
                    <Button
                        variant="outline"
                        class="min-h-11 flex-1 cursor-pointer rounded-xl sm:flex-none"
                        :disabled="!submissions.links.next"
                        as-child
                    >
                        <Link
                            v-if="submissions.links.next"
                            :href="submissions.links.next"
                            preserve-scroll
                        >
                            Berikutnya
                            <ArrowRight class="size-4" />
                        </Link>
                        <span v-else>
                            Berikutnya
                            <ArrowRight class="size-4" />
                        </span>
                    </Button>
                </div>
            </nav>
        </section>

        <section
            v-else
            class="mt-12 flex min-h-[28rem] flex-col items-center justify-center rounded-[2rem] border border-dashed bg-card px-6 py-16 text-center"
        >
            <span
                class="flex size-16 items-center justify-center rounded-[1.4rem] bg-brand-teal-soft text-brand-teal-foreground"
            >
                <Inbox class="size-7" />
            </span>
            <h2 class="mt-6 text-2xl font-semibold tracking-tight">
                Belum ada submission
            </h2>
            <p
                class="mt-3 max-w-md text-sm leading-relaxed text-muted-foreground"
            >
                Buat draft pertama Anda. PDF dapat ditambahkan setelah informasi
                surat tersimpan.
            </p>
            <Button
                size="lg"
                class="mt-7 min-h-12 cursor-pointer rounded-xl px-6"
                as-child
            >
                <Link :href="publicSubmissionRoutes.create">
                    <FilePlus2 class="size-4" />
                    Buat submission pertama
                </Link>
            </Button>
        </section>
    </div>
</template>
