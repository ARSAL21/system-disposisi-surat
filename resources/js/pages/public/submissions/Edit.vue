<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import SubmissionActionsPanel from '@/components/public/SubmissionActionsPanel.vue';
import SubmissionDocumentPanel from '@/components/public/SubmissionDocumentPanel.vue';
import SubmissionMetadataForm from '@/components/public/SubmissionMetadataForm.vue';
import SubmissionProgress from '@/components/public/SubmissionProgress.vue';
import { Button } from '@/components/ui/button';
import { publicSubmissionRoutes } from '@/lib/publicSubmissionRoutes';
import publicRoutes from '@/routes/public';
import type { LetterSubmission } from '@/types';

defineProps<{
    submission: LetterSubmission;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Surat Saya', href: publicRoutes.submissions.index() },
            { title: 'Lengkapi Draft', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Lengkapi Draft" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <Button
            variant="ghost"
            class="min-h-11 cursor-pointer rounded-xl"
            as-child
        >
            <Link :href="publicSubmissionRoutes.show(submission.public_id)">
                <ArrowLeft class="size-4" />
                Kembali ke detail
            </Link>
        </Button>

        <div class="max-w-4xl">
            <p class="text-sm font-semibold text-primary">Lengkapi draft</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">
                Periksa data, dokumen, lalu kirim
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                Setiap bagian disimpan secara eksplisit agar Anda selalu
                mengetahui perubahan yang dilakukan.
            </p>
        </div>

        <SubmissionProgress :submission="submission" />

        <div class="space-y-6">
            <section class="rounded-2xl border bg-card p-5 shadow-sm sm:p-6">
                <SubmissionMetadataForm :submission="submission" />
            </section>
            <SubmissionDocumentPanel :submission="submission" />
            <SubmissionActionsPanel :submission="submission" show-delete />
        </div>
    </div>
</template>
