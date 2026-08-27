<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SubmissionCreateHelp from '@/components/public/submission-form/SubmissionCreateHelp.vue';
import SubmissionMetadataForm from '@/components/public/SubmissionMetadataForm.vue';
import SubmissionProgress from '@/components/public/SubmissionProgress.vue';
import publicRoutes from '@/routes/public';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Surat Saya', href: publicRoutes.submissions.index() },
            { title: 'Buat Surat', href: publicRoutes.submissions.create() },
        ],
    },
});
</script>

<template>
    <Head title="Buat Submission" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <div class="max-w-4xl">
            <p class="text-sm font-semibold text-primary">Submission baru</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">
                Mulai dari informasi pada surat
            </h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                Data ini disimpan sebagai draft. Anda dapat menambahkan PDF dan
                memeriksanya kembali sebelum dikirim.
            </p>
        </div>

        <SubmissionProgress create-step />

        <div
            class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_19rem] lg:items-start"
        >
            <section class="rounded-2xl border bg-card p-5 shadow-sm sm:p-6">
                <SubmissionMetadataForm />
            </section>
            <SubmissionCreateHelp />
        </div>
    </div>
</template>
