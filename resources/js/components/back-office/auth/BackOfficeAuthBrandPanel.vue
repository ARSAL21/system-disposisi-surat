<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { CheckCircle2, ShieldCheck } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const appName = usePage().props.name;

const securityPoints = [
    'Akses khusus akun internal yang aktif',
    'Setiap kewenangan tetap diverifikasi oleh server',
    'Aktivitas sensitif dirancang agar dapat diaudit',
];
</script>

<template>
    <aside
        class="relative hidden min-h-svh overflow-hidden bg-slate-950 px-12 py-10 text-white lg:flex lg:flex-col"
    >
        <div
            aria-hidden="true"
            class="absolute -top-32 -left-28 size-80 rounded-full bg-blue-500/30 blur-3xl"
        />
        <div
            aria-hidden="true"
            class="absolute right-0 bottom-0 size-96 translate-x-1/3 translate-y-1/3 rounded-full bg-violet-500/30 blur-3xl"
        />
        <div
            aria-hidden="true"
            class="absolute inset-0 bg-[radial-gradient(circle_at_70%_20%,rgba(99,102,241,0.16),transparent_35%)]"
        />

        <Link
            :href="home()"
            class="relative z-10 flex w-fit items-center gap-3 rounded-xl focus-visible:ring-2 focus-visible:ring-white focus-visible:outline-none"
        >
            <span
                class="flex size-10 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/15 backdrop-blur"
            >
                <AppLogoIcon class="size-6" />
            </span>
            <span>
                <span class="block text-sm font-semibold">{{ appName }}</span>
                <span class="block text-xs text-blue-100/70"
                    >Portal Internal</span
                >
            </span>
        </Link>

        <div class="relative z-10 my-auto max-w-xl py-16">
            <div
                class="mb-6 flex w-fit items-center gap-2 rounded-full border border-blue-300/20 bg-blue-400/10 px-3 py-1.5 text-xs font-medium text-blue-100"
            >
                <ShieldCheck class="size-4" />
                Ruang kerja terlindungi
            </div>
            <h1
                class="max-w-lg text-4xl leading-tight font-semibold tracking-tight xl:text-5xl"
            >
                Kelola layanan surat dengan akses yang terukur.
            </h1>
            <p class="mt-5 max-w-lg text-base leading-7 text-slate-300">
                Back-office memisahkan aktivitas staf internal dari layanan
                publik dan menjaga setiap akses tetap sesuai kewenangan.
            </p>

            <ul
                class="mt-9 space-y-4"
                aria-label="Perlindungan portal internal"
            >
                <li
                    v-for="point in securityPoints"
                    :key="point"
                    class="flex items-center gap-3 text-sm text-slate-200"
                >
                    <CheckCircle2 class="size-5 shrink-0 text-blue-300" />
                    {{ point }}
                </li>
            </ul>
        </div>

        <p class="relative z-10 text-xs text-slate-400">
            Gunakan hanya perangkat dan jaringan yang Anda percayai.
        </p>
    </aside>
</template>
