<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Inbox,
    Landmark,
    Route as RouteIcon,
    ShieldCheck,
} from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

const props = defineProps<{
    mode: 'routing' | 'inbox';
    preview?: boolean;
}>();

const content = computed(() =>
    props.mode === 'routing'
        ? {
              eyebrow: 'Meja Kepala Bagian Umum',
              title: 'Routing surat ke pimpinan',
              description:
                  'Tinjau surat yang telah teregistrasi, lalu arahkan tepat kepada Wali Kota atau Sekretaris Daerah.',
              assurance:
                  'Satu surat hanya dapat diarahkan kepada satu pimpinan dan keputusan routing tidak dapat diubah pada MVP.',
              icon: RouteIcon,
              switchLabel: 'Lihat preview inbox pimpinan',
              switchHref: '/back-office/previews/executive-inbox',
          }
        : {
              eyebrow: 'Ruang kerja Wali Kota / Sekretaris Daerah',
              title: 'Inbox pimpinan',
              description:
                  'Periksa surat resmi yang diarahkan Bagian Umum, lalu buat disposisi pertama kepada tepat satu Asisten.',
              assurance:
                  'Penerima hanya berasal dari Position level Asisten dengan pejabat aktif. Pimpinan pengirim tidak dapat menunjuk dirinya sendiri.',
              icon: Inbox,
              switchLabel: 'Lihat preview meja routing',
              switchHref: '/back-office/previews/letter-routing',
          },
);
</script>

<template>
    <header
        class="relative overflow-hidden rounded-3xl border border-blue-100 bg-gradient-to-br from-white via-blue-50/75 to-violet-50/80 p-5 shadow-sm sm:p-7 dark:border-blue-950 dark:from-slate-950 dark:via-blue-950/35 dark:to-violet-950/30"
        :aria-labelledby="`${mode}-workspace-title`"
    >
        <div
            class="pointer-events-none absolute -top-20 -right-16 size-56 rounded-full bg-violet-400/15 blur-3xl dark:bg-violet-600/10"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none absolute -bottom-24 left-1/4 size-52 rounded-full bg-blue-400/10 blur-3xl"
            aria-hidden="true"
        />

        <div
            class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
        >
            <div class="max-w-3xl">
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="flex size-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-700 to-violet-700 text-white shadow-sm shadow-blue-700/20"
                    >
                        <component
                            :is="content.icon"
                            class="size-5"
                            aria-hidden="true"
                        />
                    </span>
                    <Badge
                        variant="outline"
                        class="border-blue-200 bg-white/80 text-blue-800 dark:border-blue-800 dark:bg-blue-950/55 dark:text-blue-200"
                    >
                        {{ mode === 'routing' ? 'M5 · Routing awal' : 'M6 · Disposisi berbasis jabatan' }}
                    </Badge>
                    <Badge v-if="preview" variant="secondary">
                        Pratinjau lokal
                    </Badge>
                </div>

                <p
                    class="mt-5 text-xs font-semibold tracking-[0.17em] text-blue-700 uppercase dark:text-blue-300"
                >
                    {{ content.eyebrow }}
                </p>
                <h1
                    :id="`${mode}-workspace-title`"
                    class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl"
                >
                    {{ content.title }}
                </h1>
                <p
                    class="mt-3 max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base"
                >
                    {{ content.description }}
                </p>
            </div>

            <div class="grid max-w-md gap-3">
                <div
                    class="flex items-start gap-3 rounded-2xl border border-white/80 bg-white/75 p-4 text-sm shadow-xs backdrop-blur dark:border-slate-800 dark:bg-slate-950/55"
                >
                    <ShieldCheck
                        class="mt-0.5 size-5 shrink-0 text-emerald-700 dark:text-emerald-300"
                        aria-hidden="true"
                    />
                    <p class="leading-6 text-muted-foreground">
                        {{ content.assurance }}
                    </p>
                </div>

                <Button
                    v-if="preview"
                    as-child
                    variant="outline"
                    class="min-h-11 justify-between bg-background/75"
                >
                    <Link :href="content.switchHref">
                        <span class="inline-flex items-center gap-2">
                            <Landmark class="size-4" aria-hidden="true" />
                            {{ content.switchLabel }}
                        </span>
                        <ArrowRight class="size-4" aria-hidden="true" />
                    </Link>
                </Button>
            </div>
        </div>
    </header>
</template>
