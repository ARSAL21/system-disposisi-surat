<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building2,
    CalendarDays,
    CheckCircle2,
    Clock3,
    FilePenLine,
    FileText,
    Mail,
    Phone,
    UserRound,
} from '@lucide/vue';
import { computed } from 'vue';
import SubmissionActionsPanel from '@/components/public/SubmissionActionsPanel.vue';
import SubmissionDocumentPanel from '@/components/public/SubmissionDocumentPanel.vue';
import SubmissionStatusBadge from '@/components/public/SubmissionStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { publicSubmissionRoutes } from '@/lib/publicSubmissionRoutes';
import {
    formatSubmissionDate,
    formatSubmissionDateTime,
    getSubmissionStatusPresentation,
} from '@/lib/submissionPresentation';
import type { LetterSubmission } from '@/types';

const props = defineProps<{
    submission: LetterSubmission;
}>();

const status = computed(() =>
    getSubmissionStatusPresentation(props.submission.status),
);

const metadata = computed(() => [
    {
        label: 'Instansi atau organisasi',
        value: props.submission.sender_organization_name,
        icon: Building2,
    },
    {
        label: 'Nomor surat',
        value: props.submission.external_letter_number || 'Tidak dicantumkan',
        icon: FileText,
    },
    {
        label: 'Tanggal surat',
        value: formatSubmissionDate(props.submission.external_letter_date),
        icon: CalendarDays,
    },
    {
        label: 'Nama kontak',
        value: props.submission.contact_name,
        icon: UserRound,
    },
    {
        label: 'Email kontak',
        value: props.submission.contact_email,
        icon: Mail,
    },
    {
        label: 'Nomor telepon',
        value: props.submission.contact_phone || 'Tidak dicantumkan',
        icon: Phone,
    },
]);
</script>

<template>
    <Head :title="submission.subject" />

    <div class="mx-auto max-w-7xl px-5 py-14 sm:px-8 md:py-20">
        <Button
            variant="ghost"
            class="min-h-11 cursor-pointer rounded-xl"
            as-child
        >
            <Link :href="publicSubmissionRoutes.index">
                <ArrowLeft class="size-4" />
                Submission saya
            </Link>
        </Button>

        <header
            class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-start"
        >
            <div class="max-w-4xl">
                <SubmissionStatusBadge
                    :status="submission.status"
                    show-description
                />
                <h1
                    class="mt-6 text-4xl leading-[1.04] font-semibold tracking-[-0.045em] text-balance sm:text-6xl"
                >
                    {{ submission.subject }}
                </h1>
                <p
                    class="mt-5 text-base leading-relaxed text-muted-foreground sm:text-lg"
                >
                    {{ status.description }}
                </p>
            </div>
            <Button
                v-if="submission.capabilities.can_update"
                size="lg"
                class="min-h-12 cursor-pointer rounded-xl px-6"
                as-child
            >
                <Link :href="publicSubmissionRoutes.edit(submission.public_id)">
                    <FilePenLine class="size-4" />
                    Lengkapi draft
                </Link>
            </Button>
        </header>

        <div
            class="mt-12 grid gap-8 xl:grid-cols-[minmax(0,1fr)_20rem] xl:items-start"
        >
            <div class="space-y-8">
                <section
                    class="rounded-[1.75rem] border bg-card p-5 shadow-[0_24px_80px_-58px_rgba(17,62,56,0.55)] sm:p-8"
                >
                    <div>
                        <h2 class="text-xl font-semibold tracking-tight">
                            Informasi surat
                        </h2>
                        <p
                            class="mt-2 text-sm leading-relaxed text-muted-foreground"
                        >
                            Metadata yang dikirimkan bersama dokumen surat.
                        </p>
                    </div>

                    <dl
                        class="mt-7 grid gap-px overflow-hidden rounded-2xl border bg-border sm:grid-cols-2"
                    >
                        <div
                            v-for="item in metadata"
                            :key="item.label"
                            class="flex min-w-0 gap-3 bg-card p-4 sm:p-5"
                        >
                            <span
                                class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-muted text-muted-foreground"
                            >
                                <component :is="item.icon" class="size-4" />
                            </span>
                            <div class="min-w-0">
                                <dt
                                    class="text-xs font-medium text-muted-foreground"
                                >
                                    {{ item.label }}
                                </dt>
                                <dd
                                    class="mt-1 text-sm font-semibold break-words"
                                >
                                    {{ item.value }}
                                </dd>
                            </div>
                        </div>
                    </dl>

                    <div class="mt-7 border-t pt-7">
                        <h3 class="text-sm font-semibold">Ringkasan</h3>
                        <p
                            class="mt-3 text-sm leading-relaxed whitespace-pre-wrap text-muted-foreground"
                        >
                            {{
                                submission.summary ||
                                'Tidak ada ringkasan yang dicantumkan.'
                            }}
                        </p>
                    </div>
                </section>

                <SubmissionDocumentPanel :submission="submission" readonly />
                <SubmissionActionsPanel
                    v-if="submission.status === 'DRAFT'"
                    :submission="submission"
                    show-delete
                />
            </div>

            <aside
                class="rounded-[1.75rem] border bg-card p-5 xl:sticky xl:top-28"
            >
                <h2 class="text-lg font-semibold tracking-tight">
                    Jejak submission
                </h2>
                <ol class="mt-6 space-y-0">
                    <li class="relative flex gap-4 pb-7">
                        <span
                            class="absolute top-9 bottom-0 left-4 w-px bg-border"
                            aria-hidden="true"
                        />
                        <span
                            class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-teal-soft text-brand-teal-foreground"
                        >
                            <CheckCircle2 class="size-4" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold">Draft dibuat</p>
                            <p
                                class="mt-1 text-xs leading-relaxed text-muted-foreground"
                            >
                                {{
                                    formatSubmissionDateTime(
                                        submission.created_at,
                                    )
                                }}
                            </p>
                        </div>
                    </li>
                    <li class="relative flex gap-4 pb-7">
                        <span
                            class="absolute top-9 bottom-0 left-4 w-px bg-border"
                            aria-hidden="true"
                        />
                        <span
                            class="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-amber-soft text-brand-amber-foreground"
                        >
                            <FileText class="size-4" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold">Dokumen PDF</p>
                            <p
                                class="mt-1 text-xs leading-relaxed text-muted-foreground"
                            >
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
                                    ? 'bg-brand-orange-soft text-brand-orange-foreground'
                                    : 'bg-muted text-muted-foreground',
                            ]"
                        >
                            <Clock3 class="size-4" />
                        </span>
                        <div>
                            <p class="text-sm font-semibold">
                                Dikirim ke Bagian Umum
                            </p>
                            <p
                                class="mt-1 text-xs leading-relaxed text-muted-foreground"
                            >
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
        </div>
    </div>
</template>
