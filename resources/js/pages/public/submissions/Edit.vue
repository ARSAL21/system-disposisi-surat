<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import SubmissionActionsPanel from '@/components/public/SubmissionActionsPanel.vue';
import SubmissionDocumentPanel from '@/components/public/SubmissionDocumentPanel.vue';
import SubmissionMetadataForm from '@/components/public/SubmissionMetadataForm.vue';
import SubmissionProgress from '@/components/public/SubmissionProgress.vue';
import { Button } from '@/components/ui/button';
import { publicSubmissionRoutes } from '@/lib/publicSubmissionRoutes';
import type { LetterSubmission } from '@/types';

defineProps<{
    submission: LetterSubmission;
}>();
</script>

<template>
    <Head title="Lengkapi Draft" />

    <div class="mx-auto max-w-7xl px-5 py-14 sm:px-8 md:py-20">
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

        <div class="mt-8 max-w-4xl">
            <p class="text-sm font-semibold text-brand-teal-foreground">
                Lengkapi draft
            </p>
            <h1
                class="mt-3 text-4xl font-semibold tracking-[-0.045em] text-balance sm:text-6xl"
            >
                Periksa data, dokumen, lalu kirim.
            </h1>
            <p
                class="mt-6 max-w-2xl text-base leading-relaxed text-muted-foreground sm:text-lg"
            >
                Setiap bagian disimpan secara eksplisit agar Anda selalu
                mengetahui perubahan yang dilakukan.
            </p>
        </div>

        <div class="mt-10">
            <SubmissionProgress :submission="submission" />
        </div>

        <div class="mt-8 space-y-8">
            <section
                class="rounded-[1.75rem] border bg-card p-5 shadow-[0_24px_80px_-58px_rgba(17,62,56,0.55)] sm:p-8"
            >
                <SubmissionMetadataForm :submission="submission" />
            </section>
            <SubmissionDocumentPanel :submission="submission" />
            <SubmissionActionsPanel :submission="submission" show-delete />
        </div>
    </div>
</template>
