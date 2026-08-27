<script setup lang="ts">
import { CheckCircle2, FilePenLine, LockKeyhole, Send } from '@lucide/vue';

const steps = [
    {
        title: 'Lengkapi informasi',
        description: 'Isi metadata surat sesuai dokumen asli.',
        icon: FilePenLine,
    },
    {
        title: 'Unggah PDF',
        description: 'Dokumen maksimal 20 MB disimpan secara privat.',
        icon: LockKeyhole,
    },
    {
        title: 'Periksa dan kirim',
        description: 'Pastikan data benar sebelum draft dikunci.',
        icon: Send,
    },
];
</script>

<template>
    <aside class="rounded-2xl border bg-card p-5 shadow-sm sm:p-6">
        <div class="flex items-start gap-3">
            <span
                class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-accent text-accent-foreground"
                aria-hidden="true"
            >
                <CheckCircle2 class="size-5" />
            </span>
            <div>
                <h2 class="font-semibold tracking-tight">Alur pengiriman</h2>
                <p class="mt-1 text-xs leading-5 text-muted-foreground">
                    Tiga langkah sebelum surat diperiksa Bagian Umum.
                </p>
            </div>
        </div>

        <ol class="mt-6 space-y-5">
            <li
                v-for="(step, index) in steps"
                :key="step.title"
                class="flex gap-3"
            >
                <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-muted text-primary"
                    aria-hidden="true"
                >
                    <component :is="step.icon" class="size-4" />
                </span>
                <div>
                    <p class="text-sm font-semibold">
                        <span class="mr-1 text-muted-foreground"
                            >{{ index + 1 }}.</span
                        >{{ step.title }}
                    </p>
                    <p class="mt-1 text-xs leading-5 text-muted-foreground">
                        {{ step.description }}
                    </p>
                </div>
            </li>
        </ol>

        <div class="mt-6 rounded-xl bg-info p-4 text-info-foreground">
            <p class="text-xs leading-5">
                Akses dokumen selalu diperiksa kembali oleh server. Tautan
                dokumen tidak membuat file menjadi publik.
            </p>
        </div>
    </aside>
</template>
