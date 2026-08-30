<script setup lang="ts">
import { CalendarDays, History, TimerReset } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';

defineProps<{
    dateLabel: string;
    timezoneLabel: string;
}>();
</script>

<template>
    <header
        class="relative overflow-hidden rounded-3xl border border-blue-100 bg-gradient-to-br from-white via-blue-50/75 to-violet-50 p-6 shadow-sm sm:p-8 dark:border-blue-950 dark:from-slate-950 dark:via-blue-950/35 dark:to-violet-950/30"
        aria-labelledby="letter-activity-title"
    >
        <div
            aria-hidden="true"
            class="absolute -top-20 -right-14 size-56 rounded-full bg-violet-500/10 blur-3xl"
        />
        <div
            aria-hidden="true"
            class="absolute -bottom-28 left-1/3 size-48 rounded-full bg-blue-500/10 blur-3xl"
        />

        <div class="relative max-w-3xl">
            <div class="mb-5 flex flex-wrap items-center gap-3">
                <span
                    class="flex size-12 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-sm shadow-indigo-600/20"
                >
                    <History class="size-6" aria-hidden="true" />
                </span>
                <Badge
                    variant="outline"
                    class="gap-1.5 border-blue-200 bg-white/75 px-3 py-1.5 text-blue-700 backdrop-blur-sm dark:border-blue-800 dark:bg-slate-950/60 dark:text-blue-300"
                >
                    <TimerReset class="size-3.5" aria-hidden="true" />
                    {{ timezoneLabel }}
                </Badge>
                <Badge
                    variant="outline"
                    class="gap-1.5 border-violet-200 bg-white/75 px-3 py-1.5 text-violet-700 backdrop-blur-sm dark:border-violet-800 dark:bg-slate-950/60 dark:text-violet-300"
                >
                    <CalendarDays class="size-3.5" aria-hidden="true" />
                    {{ dateLabel }}
                </Badge>
            </div>

            <p
                class="mb-2 text-sm font-semibold tracking-wide text-indigo-700 uppercase dark:text-indigo-300"
            >
                Jejak kerja penerimaan surat
            </p>
            <h1
                id="letter-activity-title"
                class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl dark:text-white"
            >
                Aktivitas Surat
            </h1>
            <p
                class="mt-3 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300"
            >
                Pantau surat yang masuk, siapa yang menanganinya, keputusan yang
                diambil, dan kapan dokumen resmi diregistrasi.
            </p>
        </div>
    </header>
</template>
