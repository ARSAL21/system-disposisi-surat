<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Cpu,
    Pause,
    Play,
    RotateCcw,
    Search,
    ShieldCheck,
} from '@lucide/vue';
import gsap from 'gsap';
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { register } from '@/routes';

// Hero entrance elements
const heroHeadlineRef = ref<HTMLElement | null>(null);
const heroSubtextRef = ref<HTMLElement | null>(null);
const heroCtaRef = ref<HTMLElement | null>(null);
const heroMockupRef = ref<HTMLElement | null>(null);

// Interactive Engine State
const activeStage = ref<number>(1);
const isAutoSimulating = ref<boolean>(false);
let simulationTimer: ReturnType<typeof setInterval> | null = null;

interface EngineTier {
    id: number;
    code: string;
    stepNumber: string;
    tierName: string;
    actor: string;
    badge: string;
    badgeStyle: string;
    actionSummary: string;
    invariantRule: string;
    telemetryData: { label: string; value: string }[];
}

const engineTiers: EngineTier[] = [
    {
        id: 1,
        code: 'INTAKE',
        stepNumber: '01',
        tierName: 'Registrasi & SHA-256',
        actor: 'Forum UMKM & Petugas Surat',
        badge: 'Naskah Dikunci',
        badgeStyle:
            'bg-sky-500/10 text-sky-700 border-sky-500/30 dark:text-sky-300',
        actionSummary:
            'Unggah naskah PDF asli. Sistem mengunci integritas fisik & digital dengan sidik jari kriptografi.',
        invariantRule: 'SHA256_INTEGRITY_VERIFIED',
        telemetryData: [
            { label: 'Kode Naskah', value: '014/FUMKM/IX/2026' },
            { label: 'Sidik Jari Asli', value: 'e3b0c442...991b7852' },
            { label: 'Status Dokumen', value: 'Tervalidasi & Terkunci' },
        ],
    },
    {
        id: 2,
        code: 'EKSEKUTIF',
        stepNumber: '02',
        tierName: 'Disposisi Pimpinan',
        actor: 'Sekretaris Daerah (Level 10)',
        badge: 'Disposisi Primer',
        badgeStyle:
            'bg-amber-500/10 text-amber-700 border-amber-500/30 dark:text-amber-300',
        actionSummary:
            'Pimpinan menelaah permohonan, menetapkan arahan kebijakan, dan mendelegasikan tugas ke Asisten.',
        invariantRule: 'STRICT_DOWNWARD_FLOW',
        telemetryData: [
            { label: 'Pemberi Mandat', value: 'Sekretaris Daerah' },
            { label: 'Instruksi Utama', value: 'Pelajari, Telaah & Laporkan' },
            { label: 'Tujuan Delegasi', value: 'Asisten Perekonomian (L20)' },
        ],
    },
    {
        id: 3,
        code: 'KOORDINASI',
        stepNumber: '03',
        tierName: 'Multi-Cabang Teknis',
        actor: 'Asisten II & Kepala Bagian',
        badge: 'Paralel 2 Cabang',
        badgeStyle:
            'bg-indigo-500/10 text-indigo-700 border-indigo-500/30 dark:text-indigo-300',
        actionSummary:
            'Koordinasi lintas bidang berjalan serentak ke Bagian Hukum & Bagian Ekonomi tanpa tumpang tindih.',
        invariantRule: 'PARALLEL_BRANCH_CONCURRENT',
        telemetryData: [
            { label: 'Cabang A', value: 'Bagian Hukum (Telaah v2)' },
            { label: 'Cabang B', value: 'Bagian Ekonomi (Kajian Sentra)' },
            { label: 'Sinkronisasi', value: 'Agregasi Otomatis' },
        ],
    },
    {
        id: 4,
        code: 'MANDAT',
        stepNumber: '04',
        tierName: 'Balasan Resmi Terbit',
        actor: 'Sekda & Petugas Surat Keluar',
        badge: 'Mandat Terpenuhi',
        badgeStyle:
            'bg-emerald-500/10 text-emerald-700 border-emerald-500/30 dark:text-emerald-300',
        actionSummary:
            'Surat balasan resmi bernomor diterbitkan. Pemohon dapat langsung mengunduh naskah PDF asli.',
        invariantRule: 'MANDATE_FULFILLED_PUBLIC',
        telemetryData: [
            { label: 'Nomor Keluar', value: '0001/BALASAN/SETDA/IX/2026' },
            { label: 'Penandatangan', value: 'Sekretaris Daerah' },
            { label: 'Akses Pemohon', value: 'Tersedia di Portal' },
        ],
    },
];

function setTier(tierId: number) {
    activeStage.value = tierId;
}

function nextTier() {
    activeStage.value = activeStage.value >= 4 ? 1 : activeStage.value + 1;
}

function resetEngine() {
    activeStage.value = 1;

    if (isAutoSimulating.value) {
        toggleSimulation();
    }
}

function toggleSimulation() {
    if (isAutoSimulating.value) {
        if (simulationTimer) {
            clearInterval(simulationTimer);
            simulationTimer = null;
        }

        isAutoSimulating.value = false;
    } else {
        isAutoSimulating.value = true;
        simulationTimer = setInterval(() => {
            nextTier();
        }, 2600);
    }
}

onMounted(() => {
    nextTick(() => {
        const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });

        if (heroHeadlineRef.value) {
            tl.fromTo(
                heroHeadlineRef.value,
                { opacity: 0, y: 30 },
                { opacity: 1, y: 0, duration: 0.85 },
            );
        }

        if (heroSubtextRef.value) {
            tl.fromTo(
                heroSubtextRef.value,
                { opacity: 0, y: 20 },
                { opacity: 1, y: 0, duration: 0.75 },
                '-=0.55',
            );
        }

        if (heroCtaRef.value) {
            tl.fromTo(
                heroCtaRef.value,
                { opacity: 0, y: 15 },
                { opacity: 1, y: 0, duration: 0.65 },
                '-=0.45',
            );
        }

        if (heroMockupRef.value) {
            tl.fromTo(
                heroMockupRef.value,
                { opacity: 0, y: 35, scale: 0.98 },
                { opacity: 1, y: 0, scale: 1, duration: 0.9 },
                '-=0.35',
            );
        }
    });
});

onBeforeUnmount(() => {
    if (simulationTimer) {
        clearInterval(simulationTimer);
    }
});
</script>

<template>
    <section
        class="relative mx-auto max-w-6xl px-3 pt-12 pb-16 sm:px-6 sm:pt-20 sm:pb-24"
    >
        <!-- Attention Hero Header (Mobile-Friendly Typography) -->
        <div class="mx-auto flex flex-col items-center text-center">
            <!-- Fluid 2-Line Commanding Heading -->
            <h1
                ref="heroHeadlineRef"
                class="max-w-5xl font-['Syne',sans-serif] text-3xl font-extrabold tracking-tight text-slate-900 sm:text-5xl lg:text-6xl lg:leading-[1.1] dark:text-white"
            >
                Tata Kelola Disposisi Presisi, Transparan Dari Hulu Ke Hilir
            </h1>

            <!-- Editorial Tagline (No Bloat) -->
            <p
                ref="heroSubtextRef"
                class="mt-4 max-w-2xl px-2 text-xs leading-relaxed text-slate-600 sm:text-base sm:leading-relaxed dark:text-slate-300"
            >
                Sistem persuratan resmi dengan validasi kriptografis SHA-256,
                penegakan alur hierarki bertingkat tanpa bypass, dan
                transparansi publik real-time.
            </p>

            <!-- Dual High-Contrast Action CTAs (Mobile Responsive, >=44px touch targets) -->
            <div
                ref="heroCtaRef"
                class="mt-6 flex w-full max-w-md flex-col items-stretch justify-center gap-2.5 sm:w-auto sm:max-w-none sm:flex-row sm:items-center sm:gap-3.5"
            >
                <Link
                    :href="register()"
                    class="group flex h-12 items-center justify-center gap-2 rounded-2xl bg-indigo-600 px-6 text-xs font-bold text-white shadow-lg shadow-indigo-600/25 transition-all hover:bg-indigo-700 active:scale-[0.98] sm:text-sm"
                >
                    <span>Ajukan Surat Online</span>
                    <ArrowRight
                        class="size-4 transition-transform group-hover:translate-x-1"
                    />
                </Link>

                <a
                    href="#tracking"
                    class="flex h-12 items-center justify-center gap-2 rounded-2xl border border-slate-300 bg-white/90 px-6 text-xs font-bold text-slate-800 shadow-xs transition-all hover:bg-slate-50 active:scale-[0.98] sm:text-sm dark:border-slate-700 dark:bg-slate-900/90 dark:text-slate-100 dark:hover:bg-slate-800"
                >
                    <Search
                        class="size-4 text-indigo-600 dark:text-indigo-400"
                    />
                    <span>Lacak Status Surat</span>
                </a>
            </div>
        </div>

        <!-- Total Redesign: The Disposisi Engine Deck (Mobile-Friendly Interactive Mockup) -->
        <div
            ref="heroMockupRef"
            class="relative mx-auto mt-10 max-w-5xl rounded-3xl border border-slate-200/80 bg-white/90 p-4 shadow-2xl shadow-indigo-500/10 backdrop-blur-2xl sm:p-6 lg:p-7 dark:border-slate-800/90 dark:bg-slate-900/90"
        >
            <!-- Control Header -->
            <div
                class="flex flex-col gap-3 border-b border-slate-200/70 pb-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800"
            >
                <div class="flex items-center gap-2.5">
                    <div
                        class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-indigo-600 font-bold text-white shadow-xs"
                    >
                        <Cpu class="size-5" />
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h3
                                class="truncate font-['Syne',sans-serif] text-xs font-bold text-slate-900 sm:text-sm dark:text-white"
                            >
                                Disposisi Engine Sandbox
                            </h3>
                            <span
                                class="inline-flex items-center gap-1 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 font-mono text-[9px] font-bold text-emerald-700 dark:border-emerald-400/30 dark:bg-emerald-400/10 dark:text-emerald-300"
                            >
                                <span
                                    class="size-1.5 animate-pulse rounded-full bg-emerald-500"
                                />
                                <span>LIVE</span>
                            </span>
                        </div>
                        <p
                            class="truncate font-mono text-[10px] text-muted-foreground"
                        >
                            DOKUMEN: 014/FUMKM/IX/2026 &bull; Forum UMKM
                        </p>
                    </div>
                </div>

                <!-- Touch Control Buttons -->
                <div class="flex items-center justify-end gap-2">
                    <button
                        type="button"
                        class="flex h-9 items-center gap-1.5 rounded-xl border border-slate-300/80 bg-white px-3 text-xs font-semibold text-slate-700 shadow-2xs hover:bg-slate-50 active:scale-[0.97] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                        @click="toggleSimulation"
                    >
                        <Pause
                            v-if="isAutoSimulating"
                            class="size-3.5 text-indigo-600 dark:text-indigo-400"
                        />
                        <Play
                            v-else
                            class="size-3.5 fill-indigo-600 text-indigo-600 dark:text-indigo-400"
                        />
                        <span class="text-[11px]">{{
                            isAutoSimulating ? 'Jeda Alur' : 'Simulasi Otomatis'
                        }}</span>
                    </button>

                    <button
                        type="button"
                        title="Reset Alur"
                        class="flex size-9 items-center justify-center rounded-xl border border-slate-300/80 bg-white text-slate-600 hover:bg-slate-50 active:scale-[0.97] dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700"
                        @click="resetEngine"
                    >
                        <RotateCcw class="size-3.5" />
                    </button>
                </div>
            </div>

            <!-- Interactive Stage Rail (Touch-Friendly Responsive Grid) -->
            <div class="mt-5 grid grid-cols-2 gap-2 sm:grid-cols-4 sm:gap-3">
                <button
                    v-for="tier in engineTiers"
                    :key="tier.id"
                    type="button"
                    class="group relative flex flex-col items-start rounded-2xl border p-3 text-left transition-all duration-200 active:scale-[0.98] sm:p-4"
                    :class="[
                        activeStage === tier.id
                            ? 'border-indigo-600 bg-indigo-600 text-white shadow-md shadow-indigo-600/20'
                            : 'border-slate-200/80 bg-white/70 text-slate-700 hover:border-slate-300 dark:border-slate-800 dark:bg-slate-900/60 dark:text-slate-300 dark:hover:border-slate-700',
                    ]"
                    @click="setTier(tier.id)"
                >
                    <div class="flex w-full items-center justify-between">
                        <span
                            class="font-mono text-[10px] font-bold tracking-wider uppercase"
                            :class="
                                activeStage === tier.id
                                    ? 'text-indigo-200'
                                    : 'text-muted-foreground'
                            "
                        >
                            {{ tier.code }}
                        </span>
                        <div
                            class="size-2 rounded-full"
                            :class="
                                activeStage === tier.id
                                    ? 'animate-pulse bg-white'
                                    : 'bg-transparent'
                            "
                        />
                    </div>
                    <span
                        class="mt-1.5 line-clamp-1 text-xs leading-tight font-bold"
                    >
                        {{ tier.tierName }}
                    </span>
                    <span
                        class="mt-1 w-full truncate font-mono text-[9px]"
                        :class="
                            activeStage === tier.id
                                ? 'text-indigo-100'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ tier.stepNumber }}. {{ tier.actor }}
                    </span>
                </button>
            </div>

            <!-- Active Station Telemetry Box -->
            <div
                v-for="tier in engineTiers"
                v-show="activeStage === tier.id"
                :key="tier.id"
                class="mt-5 rounded-2xl border border-slate-200/80 bg-slate-50/70 p-4 transition-all sm:p-5 dark:border-slate-800 dark:bg-slate-950/70"
            >
                <!-- Top Row: Role, Badge, Invariant -->
                <div
                    class="flex flex-col gap-2 border-b border-slate-200/60 pb-3 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800"
                >
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex items-center rounded-full border px-2.5 py-0.5 font-mono text-[10px] font-bold"
                            :class="tier.badgeStyle"
                        >
                            {{ tier.badge }}
                        </span>
                        <span
                            class="text-xs font-semibold text-slate-900 dark:text-white"
                        >
                            Aktor: {{ tier.actor }}
                        </span>
                    </div>

                    <div
                        class="flex items-center gap-1.5 font-mono text-[10px] text-muted-foreground"
                    >
                        <ShieldCheck
                            class="size-3.5 shrink-0 text-emerald-500"
                        />
                        <span>{{ tier.invariantRule }}</span>
                    </div>
                </div>

                <!-- Middle: 1-Line Action Summary -->
                <p
                    class="mt-3 text-xs leading-relaxed text-slate-600 dark:text-slate-300"
                >
                    {{ tier.actionSummary }}
                </p>

                <!-- Bottom: 3 Telemetry Data Chips (Collapses cleanly to 1 col on mobile) -->
                <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <div
                        v-for="data in tier.telemetryData"
                        :key="data.label"
                        class="rounded-xl border border-slate-200 bg-white p-2.5 dark:border-slate-800 dark:bg-slate-900"
                    >
                        <span
                            class="block font-mono text-[9px] text-muted-foreground uppercase"
                        >
                            {{ data.label }}
                        </span>
                        <p
                            class="mt-0.5 truncate font-mono text-xs font-bold text-slate-900 dark:text-white"
                        >
                            {{ data.value }}
                        </p>
                    </div>
                </div>

                <!-- Interactive Next Step Trigger -->
                <div
                    class="mt-4 flex items-center justify-between border-t border-slate-200/60 pt-3 text-xs dark:border-slate-800"
                >
                    <span class="font-mono text-[10px] text-muted-foreground">
                        TAHAPAN AKTIF: {{ tier.stepNumber }} DARI 04
                    </span>

                    <button
                        type="button"
                        class="inline-flex items-center gap-1 font-semibold text-indigo-600 hover:text-indigo-700 active:scale-[0.98] dark:text-indigo-400"
                        @click="nextTier"
                    >
                        <span>{{
                            activeStage >= 4
                                ? 'Ulangi Dari Awal'
                                : 'Langkah Berikutnya'
                        }}</span>
                        <ArrowRight class="size-3.5" />
                    </button>
                </div>
            </div>
        </div>
    </section>
</template>
