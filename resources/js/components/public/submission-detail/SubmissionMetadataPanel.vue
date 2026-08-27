<script setup lang="ts">
import {
    Building2,
    CalendarDays,
    FileText,
    Mail,
    Phone,
    UserRound,
} from '@lucide/vue';
import { computed } from 'vue';
import { formatSubmissionDate } from '@/lib/submissionPresentation';
import type { LetterSubmission } from '@/types';

const props = defineProps<{ submission: LetterSubmission }>();

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
    <section class="rounded-2xl border bg-card p-5 shadow-sm sm:p-6">
        <h2 class="text-lg font-semibold tracking-tight">Informasi surat</h2>
        <p class="mt-1 text-sm leading-6 text-muted-foreground">
            Metadata yang dikirimkan bersama dokumen surat.
        </p>
        <dl
            class="mt-6 grid gap-px overflow-hidden rounded-xl border bg-border sm:grid-cols-2"
        >
            <div
                v-for="item in metadata"
                :key="item.label"
                class="flex min-w-0 gap-3 bg-card p-4"
            >
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-muted text-muted-foreground"
                    aria-hidden="true"
                >
                    <component :is="item.icon" class="size-4" />
                </span>
                <div class="min-w-0">
                    <dt class="text-xs font-medium text-muted-foreground">
                        {{ item.label }}
                    </dt>
                    <dd class="mt-1 text-sm font-semibold break-words">
                        {{ item.value }}
                    </dd>
                </div>
            </div>
        </dl>
        <div class="mt-6 border-t pt-6">
            <h3 class="text-sm font-semibold">Ringkasan</h3>
            <p
                class="mt-2 text-sm leading-6 whitespace-pre-wrap text-muted-foreground"
            >
                {{
                    submission.summary ||
                    'Tidak ada ringkasan yang dicantumkan.'
                }}
            </p>
        </div>
    </section>
</template>
