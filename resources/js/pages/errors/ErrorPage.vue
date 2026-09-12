<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    status: number;
}>();

interface ErrorCopy {
    standardTitle: string;
    punchyMessage: string;
    actionPrefix: string;
    actionButtonText: string;
}

const errorContents: Record<number, ErrorCopy> = {
    404: {
        standardTitle: 'Not Found',
        punchyMessage: 'SEPERTINYA ANDA TERSESAT',
        actionPrefix: 'Mari saya pandu',
        actionButtonText: 'KEMBALI KE JALAN YANG BENAR',
    },
    403: {
        standardTitle: 'Forbidden',
        punchyMessage: 'LANGKAH ANDA TERHENTI DI SINI',
        actionPrefix: 'Akses ini dilindungi.',
        actionButtonText: 'KEMBALI KE HALAMAN SEBELUMNYA',
    },
    401: {
        standardTitle: 'Unauthorized',
        punchyMessage: 'SIAPA ANDA SEBENARNYA?',
        actionPrefix: 'Identitas belum dikenali.',
        actionButtonText: 'KEMBALI KE HALAMAN SEBELUMNYA',
    },
    419: {
        standardTitle: 'Page Expired',
        punchyMessage: 'WAKTU BERJALAN TERLALU CEPAT',
        actionPrefix: 'Sesi halaman telah kedaluwarsa.',
        actionButtonText: 'KEMBALI KE HALAMAN SEBELUMNYA',
    },
    429: {
        standardTitle: 'Too Many Requests',
        punchyMessage: 'TENANG, TARIK NAPAS SEBENTAR',
        actionPrefix: 'Ketukan terlalu cepat.',
        actionButtonText: 'KEMBALI KE HALAMAN SEBELUMNYA',
    },
    500: {
        standardTitle: 'Internal Server Error',
        punchyMessage: 'ADA SESUATU YANG KELIRU',
        actionPrefix: 'Server tersedak sejenak.',
        actionButtonText: 'KEMBALI KE HALAMAN SEBELUMNYA',
    },
    503: {
        standardTitle: 'Service Unavailable',
        punchyMessage: 'SEDANG BERSOLEK SEBENTAR',
        actionPrefix: 'Sistem sedang dipoles.',
        actionButtonText: 'KEMBALI KE HALAMAN SEBELUMNYA',
    },
    402: {
        standardTitle: 'Payment Required',
        punchyMessage: 'KUNCI KHUSUS DIPERLUKAN',
        actionPrefix: 'Fitur butuh hak istimewa.',
        actionButtonText: 'KEMBALI KE HALAMAN SEBELUMNYA',
    },
};

const currentError = computed<ErrorCopy>(() => {
    return errorContents[props.status] ?? errorContents[404];
});

function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = '/';
    }
}
</script>

<template>
    <Head>
        <title>{{ props.status }} {{ currentError.standardTitle }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link
            rel="preconnect"
            href="https://fonts.gstatic.com"
            crossorigin="anonymous"
        />
        <link
            href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700&family=Syne:wght@700;800&display=swap"
            rel="stylesheet"
        />
    </Head>

    <div
        class="relative flex h-screen items-center justify-center overflow-hidden bg-slate-50 [font-family:'Plus_Jakarta_Sans',system-ui,sans-serif] text-slate-900 select-none selection:bg-indigo-500 selection:text-white dark:bg-slate-950 dark:text-slate-100"
    >
        <!-- Ambient Subtle Mesh (Static, Clean) -->
        <div class="pointer-events-none fixed inset-0 z-0 overflow-hidden">
            <div
                class="absolute inset-0 [background-image:radial-gradient(rgba(99,102,241,0.06)_1px,transparent_1px)] [background-size:32px_32px] opacity-70 dark:[background-image:radial-gradient(rgba(129,140,248,0.05)_1px,transparent_1px)]"
            />
            <div
                class="pointer-events-none absolute top-1/2 left-1/2 h-[550px] w-[550px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-gradient-to-tr from-indigo-500/10 via-purple-500/10 to-pink-500/10 blur-[110px] dark:from-indigo-500/15 dark:via-purple-500/15 dark:to-pink-500/15"
            />
        </div>

        <!-- Composition: Layered Overlapping Typography (High Contrast, No Separate Button) -->
        <main
            class="relative z-10 flex w-full max-w-3xl flex-col items-center justify-center px-4 py-8 text-center"
        >
            <!-- LAYER 0 (BELAKANG): Angka Status Code dengan Warna Kontras Tinggi -->
            <div class="pointer-events-none relative z-0 select-none">
                <h1
                    class="[font-family:'Syne',system-ui,sans-serif] text-7xl leading-none font-extrabold tracking-tighter text-slate-800 drop-shadow-sm select-none sm:text-8xl md:text-9xl lg:text-[10.5rem] dark:text-slate-100"
                >
                    {{ props.status }}
                </h1>
            </div>

            <!-- LAYER 1 (DEPAN): Redaksi Kalimat yang Menutupi Sebagian Angka di Atas -->
            <div
                class="relative z-10 -mt-6 flex flex-col items-center px-4 sm:-mt-8 md:-mt-10 lg:-mt-12"
            >
                <!-- Penamaan Standar (Understated & Elegan) -->
                <span
                    class="mb-2.5 rounded-full border border-slate-200 bg-white/90 px-3.5 py-0.5 font-mono text-[11px] tracking-widest text-slate-500 uppercase shadow-2xs backdrop-blur-sm sm:text-xs dark:border-slate-800 dark:bg-slate-900/90 dark:text-slate-400"
                >
                    {{ props.status }} • {{ currentError.standardTitle }}
                </span>

                <!-- Redaksi Kalimat Utama Berhuruf Syne yang Menimpa Angka -->
                <h2
                    class="max-w-xl [font-family:'Syne',system-ui,sans-serif] text-xl leading-tight font-extrabold tracking-tight text-slate-900 uppercase drop-shadow-xs sm:text-2xl md:text-3xl lg:text-4xl dark:text-white"
                >
                    {{ currentError.punchyMessage }}
                </h2>

                <!-- Redaksi Kalimat dengan Tombol "KEMBALI" yang Menyatu di Dalamnya -->
                <p
                    class="mx-auto mt-4 flex max-w-xl flex-wrap items-center justify-center gap-2 text-sm leading-relaxed font-medium text-slate-600 sm:text-base md:text-lg dark:text-slate-300"
                >
                    <span>{{ currentError.actionPrefix }}</span>
                    <button
                        type="button"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-full bg-slate-900 px-4 py-1.5 text-xs font-bold tracking-wide text-white uppercase shadow-md transition-all duration-200 select-none hover:scale-105 hover:bg-slate-800 active:scale-95 sm:text-sm dark:bg-white dark:text-slate-900 dark:hover:bg-slate-100"
                        @click="goBack"
                    >
                        <span>{{ currentError.actionButtonText }}</span>
                        <ArrowLeft
                            class="size-3.5 transition-transform duration-200 group-hover:-translate-x-0.5"
                        />
                    </button>
                </p>
            </div>
        </main>
    </div>
</template>
