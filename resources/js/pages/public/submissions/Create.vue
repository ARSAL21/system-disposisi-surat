<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { FileText, LockKeyhole, ShieldCheck } from '@lucide/vue';
import SubmissionMetadataForm from '@/components/public/SubmissionMetadataForm.vue';
import SubmissionProgress from '@/components/public/SubmissionProgress.vue';
</script>

<template>
    <Head title="Buat Submission" />

    <div class="mx-auto max-w-7xl px-5 py-14 sm:px-8 md:py-20">
        <div class="max-w-4xl">
            <p class="text-sm font-semibold text-brand-teal-foreground">
                Submission baru
            </p>
            <h1
                class="mt-3 text-4xl font-semibold tracking-[-0.045em] text-balance sm:text-6xl"
            >
                Mulai dari informasi yang tercantum pada surat.
            </h1>
            <p
                class="mt-6 max-w-2xl text-base leading-relaxed text-muted-foreground sm:text-lg"
            >
                Data ini disimpan sebagai draft. Anda dapat menambahkan PDF dan
                memeriksanya kembali sebelum dikirim.
            </p>
        </div>

        <div class="mt-10">
            <SubmissionProgress create-step />
        </div>

        <div
            class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_19rem] lg:items-start"
        >
            <section
                class="rounded-[1.75rem] border bg-card p-5 shadow-[0_24px_80px_-58px_rgba(17,62,56,0.55)] sm:p-8"
            >
                <SubmissionMetadataForm />
            </section>

            <aside class="space-y-4 lg:sticky lg:top-28">
                <div class="rounded-[1.5rem] border bg-card p-5">
                    <span
                        class="flex size-11 items-center justify-center rounded-2xl bg-brand-teal-soft text-brand-teal-foreground"
                    >
                        <ShieldCheck class="size-5" />
                    </span>
                    <h2 class="mt-5 font-semibold">Identitas dari akun</h2>
                    <p
                        class="mt-2 text-sm leading-relaxed text-muted-foreground"
                    >
                        Nama dan email kontak ditentukan server dari akun
                        terverifikasi.
                    </p>
                </div>
                <div class="rounded-[1.5rem] border bg-card p-5">
                    <span
                        class="flex size-11 items-center justify-center rounded-2xl bg-brand-amber-soft text-brand-amber-foreground"
                    >
                        <FileText class="size-5" />
                    </span>
                    <h2 class="mt-5 font-semibold">PDF menyusul</h2>
                    <p
                        class="mt-2 text-sm leading-relaxed text-muted-foreground"
                    >
                        Setelah draft tersimpan, Anda akan diarahkan ke tahap
                        upload dokumen.
                    </p>
                </div>
                <div class="rounded-[1.5rem] border bg-card p-5">
                    <span
                        class="flex size-11 items-center justify-center rounded-2xl bg-brand-orange-soft text-brand-orange-foreground"
                    >
                        <LockKeyhole class="size-5" />
                    </span>
                    <h2 class="mt-5 font-semibold">
                        Belum menjadi surat masuk
                    </h2>
                    <p
                        class="mt-2 text-sm leading-relaxed text-muted-foreground"
                    >
                        Submission baru menjadi surat resmi setelah registrasi
                        oleh Bagian Umum.
                    </p>
                </div>
            </aside>
        </div>
    </div>
</template>
