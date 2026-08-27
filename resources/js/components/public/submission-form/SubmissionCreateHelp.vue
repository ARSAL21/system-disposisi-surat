<script setup lang="ts">
import { FileText, LockKeyhole, ShieldCheck } from '@lucide/vue';

const items = [
    {
        title: 'Identitas dari akun',
        description:
            'Nama dan email kontak ditentukan server dari akun terverifikasi.',
        icon: ShieldCheck,
        tone: 'bg-success text-success-foreground',
    },
    {
        title: 'PDF menyusul',
        description:
            'Setelah draft tersimpan, Anda akan diarahkan ke tahap upload dokumen.',
        icon: FileText,
        tone: 'bg-info text-info-foreground',
    },
    {
        title: 'Belum menjadi surat masuk',
        description:
            'Submission baru menjadi surat resmi setelah registrasi oleh Bagian Umum.',
        icon: LockKeyhole,
        tone: 'bg-accent text-accent-foreground',
    },
];
</script>

<template>
    <aside class="space-y-4 lg:sticky lg:top-20">
        <article
            v-for="item in items"
            :key="item.title"
            class="rounded-2xl border bg-card p-5 shadow-sm"
        >
            <span
                :class="[
                    'flex size-10 items-center justify-center rounded-xl',
                    item.tone,
                ]"
                aria-hidden="true"
            >
                <component :is="item.icon" class="size-5" />
            </span>
            <h2 class="mt-4 text-sm font-semibold">{{ item.title }}</h2>
            <p class="mt-2 text-xs leading-5 text-muted-foreground">
                {{ item.description }}
            </p>
        </article>
    </aside>
</template>
