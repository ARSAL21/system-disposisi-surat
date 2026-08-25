<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    ClipboardCheck,
    FileCheck2,
    FilePenLine,
    FileText,
    LockKeyhole,
    Send,
    ShieldCheck,
} from '@lucide/vue';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { onMounted, onUnmounted, ref } from 'vue';
import SubmissionCard from '@/components/public/SubmissionCard.vue';
import { Button } from '@/components/ui/button';
import { publicSubmissionRoutes } from '@/lib/publicSubmissionRoutes';
import { cn } from '@/lib/utils';
import type { LetterSubmission, PublicDashboardSummary } from '@/types';

gsap.registerPlugin(ScrollTrigger);

defineProps<{
    summary: PublicDashboardSummary;
    recentSubmissions: LetterSubmission[];
}>();

const pageRoot = ref<HTMLElement | null>(null);
const processSection = ref<HTMLElement | null>(null);
const processHeading = ref<HTMLElement | null>(null);
const activeProcess = ref(0);
let context: gsap.Context | undefined;
let media: gsap.MatchMedia | undefined;

const processSteps = [
    {
        title: 'Simpan metadata',
        description:
            'Isi informasi surat sesuai dokumen asli. Sistem membuat draft yang masih dapat Anda koreksi.',
        icon: FilePenLine,
        accent: 'bg-brand-teal-soft text-brand-teal-foreground',
    },
    {
        title: 'Unggah PDF',
        description:
            'Tambahkan satu PDF maksimal 20 MB. Dokumen disimpan privat dan aksesnya melewati authorization.',
        icon: LockKeyhole,
        accent: 'bg-brand-amber-soft text-brand-amber-foreground',
    },
    {
        title: 'Periksa dan kirim',
        description:
            'Setelah dikirim, submission dikunci dan masuk ke antrean pemeriksaan administratif Bagian Umum.',
        icon: Send,
        accent: 'bg-brand-orange-soft text-brand-orange-foreground',
    },
];

const revealCopy =
    'Satu alur yang jelas membuat setiap surat tetap terlacak sejak draft hingga diterima Bagian Umum.';

onMounted(() => {
    if (!pageRoot.value) {
        return;
    }

    context = gsap.context(() => {
        media = gsap.matchMedia();
        media.add(
            {
                desktop: '(min-width: 1024px)',
                reduceMotion: '(prefers-reduced-motion: reduce)',
            },
            (matchContext) => {
                const { desktop, reduceMotion } = matchContext.conditions as {
                    desktop: boolean;
                    reduceMotion: boolean;
                };

                if (reduceMotion) {
                    return;
                }

                gsap.from('.hero-reveal', {
                    y: 24,
                    autoAlpha: 0,
                    duration: 0.65,
                    stagger: 0.08,
                    ease: 'power2.out',
                });

                gsap.from('.bento-card', {
                    y: 18,
                    autoAlpha: 0,
                    duration: 0.5,
                    stagger: 0.06,
                    ease: 'power2.out',
                    scrollTrigger: {
                        trigger: '.bento-grid',
                        start: 'top 82%',
                    },
                });

                if (desktop && processSection.value && processHeading.value) {
                    ScrollTrigger.create({
                        trigger: processSection.value,
                        start: 'top 112px',
                        end: 'bottom 58%',
                        pin: processHeading.value,
                        pinSpacing: false,
                    });

                    gsap.fromTo(
                        '.reveal-word',
                        { opacity: 0.14 },
                        {
                            opacity: 1,
                            stagger: 0.08,
                            ease: 'none',
                            scrollTrigger: {
                                trigger: '.reveal-copy',
                                start: 'top 82%',
                                end: 'bottom 42%',
                                scrub: 0.8,
                            },
                        },
                    );
                }
            },
        );
    }, pageRoot.value);

    document.fonts.ready.then(() => ScrollTrigger.refresh());
});

onUnmounted(() => {
    media?.revert();
    context?.revert();
});
</script>

<template>
    <Head title="Dashboard Publik" />

    <main ref="pageRoot" class="w-full max-w-full overflow-x-hidden">
        <section
            class="mx-auto grid max-w-7xl gap-14 px-5 pt-20 pb-28 sm:px-8 md:pt-28 md:pb-40 lg:grid-cols-[1.12fr_0.88fr] lg:items-center"
        >
            <div>
                <p
                    class="hero-reveal max-w-xl text-base leading-relaxed text-muted-foreground sm:text-lg"
                >
                    Kanal resmi untuk mengirim surat kepada kantor secara aman
                    dan terarah.
                </p>
                <h1
                    class="hero-reveal mt-6 max-w-6xl text-[clamp(3rem,6vw,5.8rem)] leading-[0.94] font-semibold tracking-[-0.065em] text-balance"
                >
                    <span class="block">Surat resmi,</span>
                    <span class="mt-2 block">
                        <span
                            class="mr-[0.14em] inline-flex h-[0.72em] w-[1.18em] translate-y-[0.05em] items-center justify-center rounded-full bg-brand-orange align-baseline text-white shadow-[0_12px_32px_-14px_rgba(252,74,26,0.8)]"
                            aria-hidden="true"
                        >
                            <FileText class="size-[0.38em]" :stroke-width="2" />
                        </span>
                        sampai dengan pasti.
                    </span>
                </h1>
                <p
                    class="hero-reveal mt-8 max-w-2xl text-base leading-relaxed text-muted-foreground sm:text-lg"
                >
                    Buat draft, unggah PDF ke penyimpanan privat, lalu pantau
                    submission milik Anda dalam satu alur yang mudah dipahami.
                </p>
                <div class="hero-reveal mt-9 flex flex-col gap-3 sm:flex-row">
                    <Button
                        size="lg"
                        class="min-h-12 cursor-pointer rounded-xl px-6 text-base"
                        as-child
                    >
                        <Link :href="publicSubmissionRoutes.create">
                            Buat submission
                            <ArrowRight class="size-4" />
                        </Link>
                    </Button>
                    <Button
                        variant="outline"
                        size="lg"
                        class="min-h-12 cursor-pointer rounded-xl bg-background/75 px-6 text-base backdrop-blur"
                        as-child
                    >
                        <Link :href="publicSubmissionRoutes.index"
                            >Lihat submission saya</Link
                        >
                    </Button>
                </div>
            </div>

            <div
                class="hero-reveal relative mx-auto w-full max-w-lg lg:ml-auto"
            >
                <div
                    class="absolute -inset-8 -z-10 rounded-full bg-brand-teal/14 blur-3xl"
                    aria-hidden="true"
                />
                <div
                    class="relative rotate-[1.5deg] overflow-hidden rounded-[2rem] border bg-card p-5 shadow-[0_36px_100px_-50px_rgba(11,51,46,0.6)] transition-transform duration-700 ease-out hover:scale-[1.01] hover:rotate-0 sm:p-7"
                >
                    <div
                        class="flex items-center justify-between border-b pb-5"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex size-11 items-center justify-center rounded-2xl bg-brand-teal-soft text-brand-teal-foreground"
                            >
                                <ShieldCheck class="size-5" />
                            </span>
                            <div>
                                <p class="text-sm font-semibold">
                                    Submission online
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Ruang dokumen privat
                                </p>
                            </div>
                        </div>
                        <span
                            class="size-2.5 rounded-full bg-brand-teal shadow-[0_0_0_6px_rgba(74,189,172,0.14)]"
                        />
                    </div>
                    <div class="space-y-5 py-7">
                        <div class="h-3 w-2/5 rounded-full bg-brand-lavender" />
                        <div class="space-y-3">
                            <div
                                class="h-5 w-full rounded-full bg-foreground/90"
                            />
                            <div
                                class="h-5 w-4/5 rounded-full bg-foreground/90"
                            />
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="h-20 rounded-2xl bg-muted" />
                            <div class="h-20 rounded-2xl bg-brand-amber-soft" />
                        </div>
                    </div>
                    <div
                        class="flex items-center gap-3 rounded-2xl bg-primary p-4 text-primary-foreground"
                    >
                        <Check class="size-5 shrink-0" />
                        <p class="text-sm font-medium">
                            Siap diperiksa sebelum dikirim
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section class="border-y bg-card/55 py-28 md:py-40">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="max-w-3xl">
                    <h2
                        class="text-3xl font-semibold tracking-tight text-balance sm:text-5xl"
                    >
                        Ruang kerja yang menjelaskan apa yang harus dilakukan
                        berikutnya.
                    </h2>
                    <p
                        class="mt-5 text-base leading-relaxed text-muted-foreground sm:text-lg"
                    >
                        Status, dokumen, dan tindakan penting disajikan tanpa
                        menyembunyikan konteks proses administratif.
                    </p>
                </div>

                <div
                    class="bento-grid mt-14 grid grid-flow-dense auto-rows-[minmax(10rem,1fr)] gap-px overflow-hidden rounded-[2rem] border bg-border md:grid-cols-4 md:grid-rows-2"
                >
                    <div
                        class="bento-card min-h-80 bg-primary p-6 text-primary-foreground sm:p-8 md:col-span-2 md:row-span-2"
                    >
                        <div class="flex h-full flex-col">
                            <span
                                class="flex size-12 items-center justify-center rounded-2xl bg-white/10"
                                aria-hidden="true"
                            >
                                <ClipboardCheck class="size-5" />
                            </span>
                            <div class="mt-auto pt-12">
                                <p
                                    class="text-sm font-medium text-primary-foreground/65"
                                >
                                    Aktivitas terbaru
                                </p>
                                <template v-if="recentSubmissions[0]">
                                    <h3
                                        class="mt-3 max-w-md text-2xl leading-tight font-semibold tracking-tight sm:text-3xl"
                                    >
                                        {{ recentSubmissions[0].subject }}
                                    </h3>
                                    <p
                                        class="mt-4 text-sm text-primary-foreground/70"
                                    >
                                        {{
                                            recentSubmissions[0]
                                                .sender_organization_name
                                        }}
                                    </p>
                                    <Link
                                        :href="
                                            publicSubmissionRoutes.show(
                                                recentSubmissions[0].public_id,
                                            )
                                        "
                                        class="mt-7 inline-flex min-h-11 items-center gap-2 rounded-xl bg-white px-4 text-sm font-semibold text-primary transition-transform outline-none hover:scale-[1.02] focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-primary"
                                    >
                                        Buka submission
                                        <ArrowRight class="size-4" />
                                    </Link>
                                </template>
                                <template v-else>
                                    <h3
                                        class="mt-3 max-w-md text-3xl font-semibold tracking-tight"
                                    >
                                        Belum ada submission.
                                    </h3>
                                    <p
                                        class="mt-4 max-w-sm text-sm leading-relaxed text-primary-foreground/70"
                                    >
                                        Mulai dengan menyimpan metadata surat
                                        sebagai draft.
                                    </p>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="bento-card bg-card p-6 sm:p-8 md:col-span-2">
                        <div
                            class="flex h-full items-start justify-between gap-6"
                        >
                            <div>
                                <p
                                    class="text-sm font-medium text-muted-foreground"
                                >
                                    Seluruh submission
                                </p>
                                <p
                                    class="mt-3 text-5xl font-semibold tracking-[-0.06em] tabular-nums"
                                >
                                    {{ summary.total }}
                                </p>
                            </div>
                            <span
                                class="flex size-12 items-center justify-center rounded-2xl bg-brand-lavender/65 text-foreground"
                            >
                                <FileText class="size-5" />
                            </span>
                        </div>
                    </div>

                    <div
                        class="bento-card bg-brand-amber-soft p-6 text-brand-amber-foreground sm:p-8"
                    >
                        <FilePenLine class="size-5" />
                        <p class="mt-8 text-sm font-medium opacity-75">
                            Draft aktif
                        </p>
                        <p
                            class="mt-1 text-4xl font-semibold tracking-tight tabular-nums"
                        >
                            {{ summary.draft }}
                        </p>
                    </div>

                    <div
                        class="bento-card bg-brand-orange-soft p-6 text-brand-orange-foreground sm:p-8"
                    >
                        <FileCheck2 class="size-5" />
                        <p class="mt-8 text-sm font-medium opacity-75">
                            Sudah terkirim
                        </p>
                        <p
                            class="mt-1 text-4xl font-semibold tracking-tight tabular-nums"
                        >
                            {{ summary.submitted }}
                        </p>
                    </div>
                </div>

                <div
                    v-if="recentSubmissions.length > 1"
                    class="mt-12 grid gap-4 md:grid-cols-3"
                >
                    <SubmissionCard
                        v-for="submission in recentSubmissions.slice(1)"
                        :key="submission.public_id"
                        :submission="submission"
                        compact
                    />
                </div>
            </div>
        </section>

        <div
            class="overflow-hidden border-b bg-foreground py-4 text-background"
            aria-hidden="true"
        >
            <div
                class="public-marquee flex w-max items-center gap-12 text-sm font-semibold tracking-wide whitespace-nowrap"
            >
                <template v-for="copy in 2" :key="copy">
                    <span>PDF MAKSIMAL 20 MB</span
                    ><span class="text-brand-teal">DOKUMEN PRIVAT</span>
                    <span>AKSES TEROTORISASI</span
                    ><span class="text-brand-amber"
                        >DRAFT DAPAT DIPERBARUI</span
                    >
                    <span>SUBMISSION TERLACAK</span>
                </template>
            </div>
            <span class="sr-only">
                PDF maksimal 20 MB, dokumen privat, akses terotorisasi, dan
                submission terlacak.
            </span>
        </div>

        <section
            ref="processSection"
            class="mx-auto grid max-w-7xl gap-14 px-5 py-32 sm:px-8 md:py-48 lg:grid-cols-[0.82fr_1.18fr] lg:gap-24"
        >
            <div ref="processHeading" class="h-fit">
                <h2
                    class="text-4xl leading-[1.02] font-semibold tracking-[-0.045em] text-balance sm:text-6xl"
                >
                    Tiga langkah. Satu jejak yang jelas.
                </h2>
                <p
                    class="mt-6 max-w-lg text-base leading-relaxed text-muted-foreground sm:text-lg"
                >
                    Tidak ada status tersembunyi dan tidak ada dokumen yang
                    berubah tanpa tindakan eksplisit.
                </p>
            </div>

            <div class="space-y-20 lg:space-y-32">
                <p
                    class="reveal-copy text-3xl leading-[1.28] font-medium tracking-[-0.035em] text-balance sm:text-5xl"
                >
                    <span
                        v-for="(word, index) in revealCopy.split(' ')"
                        :key="`${word}-${index}`"
                        class="reveal-word mr-[0.22em] inline-block"
                        >{{ word }}</span
                    >
                </p>

                <div class="space-y-5">
                    <article
                        v-for="(step, index) in processSteps"
                        :key="step.title"
                        class="rounded-[1.75rem] border bg-card p-6 shadow-[0_24px_80px_-58px_rgba(17,62,56,0.55)] sm:p-8"
                    >
                        <span
                            :class="
                                cn(
                                    'flex size-12 items-center justify-center rounded-2xl',
                                    step.accent,
                                )
                            "
                        >
                            <component :is="step.icon" class="size-5" />
                        </span>
                        <p
                            class="mt-8 text-sm font-medium text-muted-foreground"
                        >
                            Langkah {{ index + 1 }}
                        </p>
                        <h3 class="mt-2 text-2xl font-semibold tracking-tight">
                            {{ step.title }}
                        </h3>
                        <p
                            class="mt-4 max-w-xl text-base leading-relaxed text-muted-foreground"
                        >
                            {{ step.description }}
                        </p>
                    </article>
                </div>
            </div>
        </section>

        <section class="bg-card py-28 md:py-40">
            <div class="mx-auto max-w-7xl px-5 sm:px-8">
                <div class="max-w-3xl">
                    <h2
                        class="text-3xl font-semibold tracking-tight text-balance sm:text-5xl"
                    >
                        Pahami proses sebelum mengirim.
                    </h2>
                    <p
                        class="mt-5 text-base leading-relaxed text-muted-foreground"
                    >
                        Pilih setiap tahap untuk melihat tanggung jawab Anda dan
                        batas sistem.
                    </p>
                </div>

                <div class="mt-12 flex flex-col gap-3 lg:h-[25rem] lg:flex-row">
                    <button
                        v-for="(step, index) in processSteps"
                        :key="step.title"
                        type="button"
                        :aria-expanded="activeProcess === index"
                        :class="[
                            'group flex min-h-40 cursor-pointer flex-col overflow-hidden rounded-[1.75rem] border bg-background p-6 text-left transition-[flex] duration-500 ease-out outline-none focus-visible:ring-3 focus-visible:ring-ring/35 lg:min-h-0',
                            activeProcess === index
                                ? 'lg:flex-[2.2]'
                                : 'lg:flex-[0.8]',
                        ]"
                        @click="activeProcess = index"
                        @mouseenter="activeProcess = index"
                        @focus="activeProcess = index"
                    >
                        <span
                            :class="
                                cn(
                                    'flex size-11 items-center justify-center rounded-2xl',
                                    step.accent,
                                )
                            "
                        >
                            <component :is="step.icon" class="size-5" />
                        </span>
                        <div class="mt-auto pt-10">
                            <h3
                                class="text-xl font-semibold tracking-tight sm:text-2xl"
                            >
                                {{ step.title }}
                            </h3>
                            <p
                                :class="[
                                    'mt-3 max-w-lg text-sm leading-relaxed text-muted-foreground transition-opacity duration-300',
                                    activeProcess === index
                                        ? 'opacity-100'
                                        : 'lg:opacity-0',
                                ]"
                            >
                                {{ step.description }}
                            </p>
                        </div>
                    </button>
                </div>
            </div>
        </section>

        <section class="px-5 py-28 sm:px-8 md:py-44">
            <div
                class="mx-auto max-w-7xl overflow-hidden rounded-[2.25rem] bg-primary px-6 py-16 text-primary-foreground shadow-[0_36px_100px_-55px_rgba(12,67,60,0.8)] sm:px-10 md:px-16 md:py-24"
            >
                <div class="grid gap-10 lg:grid-cols-[1fr_auto] lg:items-end">
                    <div>
                        <h2
                            class="max-w-4xl text-4xl leading-[1.02] font-semibold tracking-[-0.05em] text-balance sm:text-6xl"
                        >
                            Siapkan surat Anda. Sistem menjaga jalurnya.
                        </h2>
                        <p
                            class="mt-6 max-w-2xl text-base leading-relaxed text-primary-foreground/72 sm:text-lg"
                        >
                            Mulai sebagai draft, periksa dengan tenang, lalu
                            kirim ketika seluruh informasi sudah benar.
                        </p>
                    </div>
                    <Button
                        size="lg"
                        class="min-h-12 cursor-pointer rounded-xl bg-white px-6 text-base text-brand-deep-teal hover:bg-white/90"
                        as-child
                    >
                        <Link :href="publicSubmissionRoutes.create">
                            Mulai submission
                            <ArrowRight class="size-4" />
                        </Link>
                    </Button>
                </div>
            </div>
        </section>
    </main>
</template>
