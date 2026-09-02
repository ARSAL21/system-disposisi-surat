<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Crown,
    Landmark,
    Route as RouteIcon,
    ShieldCheck,
    Sparkles,
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
              eyebrow: 'Meja Kepala Bagian Umum · Level 10',
              title: 'Routing Surat Masuk ke Pimpinan',
              description:
                  'Tinjau berkas surat yang telah teregistrasi dan terverifikasi keasliannya, lalu tetapkan routing awal terarah kepada Wali Kota atau Sekretaris Daerah.',
              assurance:
                  'Satu surat hanya dapat diarahkan kepada satu pimpinan eksekutif. Integritas naskah diverifikasi otomatis melalui sidik jari SHA-256.',
              icon: RouteIcon,
              accentColor: 'from-blue-600 via-indigo-600 to-violet-700',
              badgeText: 'M5 · Routing Awal Kedinasan',
              switchLabel: 'Lihat preview inbox pimpinan',
              switchHref: '/back-office/previews/executive-inbox',
          }
        : {
              eyebrow: 'Ruang Kerja Eksekutif Pimpinan · Level 20',
              title: 'Inbox & Lembar Disposisi Pimpinan',
              description:
                  'Periksa surat dinas masuk yang telah diarahkan oleh Bagian Umum, tetapkan instruksi kebijakan, dan buat disposisi primer kepada Asisten Koordinator.',
              assurance:
                  'Penerima disposisi primer berasal dari pejabat aktif level Asisten. Alur penugasan dijamin strictly downward tanpa risiko bypass.',
              icon: Crown,
              accentColor: 'from-amber-600 via-indigo-600 to-purple-700',
              badgeText: 'M6 · Disposisi Eksekutif Primer',
              switchLabel: 'Lihat preview meja routing',
              switchHref: '/back-office/previews/letter-routing',
          },
);
</script>

<template>
    <header
        class="relative overflow-hidden rounded-3xl border border-border/80 bg-gradient-to-br from-card via-card/90 to-muted/40 p-6 shadow-lg shadow-black/5 backdrop-blur-2xl sm:p-8 dark:border-border/60 dark:from-slate-900 dark:via-slate-900/90 dark:to-slate-950/80"
        :aria-labelledby="`${mode}-workspace-title`"
    >
        <!-- Ambient Decorative Glow -->
        <div
            class="pointer-events-none absolute -right-16 -top-20 size-72 rounded-full bg-gradient-to-br from-indigo-500/15 via-violet-500/10 to-transparent blur-3xl dark:from-indigo-600/20"
            aria-hidden="true"
        />
        <div
            class="pointer-events-none absolute -bottom-24 left-1/3 size-64 rounded-full bg-amber-500/10 blur-3xl dark:bg-amber-600/10"
            aria-hidden="true"
        />

        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="max-w-3xl space-y-3">
                <!-- Top Badges Row -->
                <div class="flex flex-wrap items-center gap-2">
                    <div
                        class="flex size-11 items-center justify-center rounded-2xl bg-gradient-to-tr text-white shadow-md shadow-indigo-500/20"
                        :class="content.accentColor"
                    >
                        <component :is="content.icon" class="size-5.5" />
                    </div>

                    <Badge
                        variant="outline"
                        class="border-indigo-500/30 bg-indigo-500/10 px-3 py-1 font-mono text-xs font-bold text-indigo-700 dark:border-indigo-400/30 dark:bg-indigo-400/10 dark:text-indigo-300"
                    >
                        {{ content.badgeText }}
                    </Badge>

                    <Badge
                        v-if="preview"
                        variant="secondary"
                        class="px-2.5 py-0.5 text-xs font-medium"
                    >
                        <Sparkles class="mr-1 size-3 text-amber-500" />
                        <span>Pratinjau Lokal</span>
                    </Badge>
                </div>

                <!-- Eyebrow & Title -->
                <div>
                    <p class="font-mono text-xs font-bold tracking-widest text-indigo-600 dark:text-indigo-400 uppercase">
                        {{ content.eyebrow }}
                    </p>
                    <h1
                        :id="`${mode}-workspace-title`"
                        class="mt-1 font-['Syne',sans-serif] text-2xl font-extrabold tracking-tight text-foreground sm:text-3xl lg:text-4xl"
                    >
                        {{ content.title }}
                    </h1>
                </div>

                <p class="max-w-2xl text-xs leading-relaxed text-muted-foreground sm:text-sm sm:leading-relaxed">
                    {{ content.description }}
                </p>
            </div>

            <!-- Right Invariant Assurance & Switcher -->
            <div class="flex flex-col gap-3 lg:max-w-md">
                <div
                    class="flex items-start gap-3.5 rounded-2xl border border-border/80 bg-background/80 p-4 text-xs shadow-xs backdrop-blur-md dark:bg-slate-950/60"
                >
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-xl bg-emerald-500/15 text-emerald-600 dark:text-emerald-400">
                        <ShieldCheck class="size-4.5" />
                    </div>
                    <div class="space-y-0.5">
                        <span class="font-mono text-[10px] font-bold text-emerald-700 dark:text-emerald-300 uppercase tracking-wider">
                            Sistem Penegakan Invarian
                        </span>
                        <p class="text-xs leading-relaxed text-muted-foreground">
                            {{ content.assurance }}
                        </p>
                    </div>
                </div>

                <Button
                    v-if="preview"
                    as-child
                    variant="outline"
                    class="h-10 justify-between rounded-xl bg-background/80 text-xs font-semibold"
                >
                    <Link :href="content.switchHref">
                        <span class="inline-flex items-center gap-2">
                            <Landmark class="size-4" />
                            <span>{{ content.switchLabel }}</span>
                        </span>
                        <ArrowRight class="size-4" />
                    </Link>
                </Button>
            </div>
        </div>
    </header>
</template>
