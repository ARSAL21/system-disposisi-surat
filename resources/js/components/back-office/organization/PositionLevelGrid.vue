<script setup lang="ts">
import {
    Activity,
    ArrowRight,
    CheckCircle2,
    Compass,
    Crown,
    FileCheck,
    FileInput,
    GitFork,
    Lock,
    Play,
    RotateCcw,
    Shield,
    ShieldAlert,
    Sparkles,
    Workflow,
} from '@lucide/vue';
import gsap from 'gsap';
import { computed, onMounted, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { PositionLevel } from '@/types';

const props = defineProps<{
    levels: PositionLevel[];
}>();

// Detailed domain specifications for the 4 immutable workflow tiers
interface LevelWorkflowMeta {
    code: string;
    stepNumber: number;
    phase: string;
    badgeColor: string;
    glowClass: string;
    activeBorderClass: string;
    icon: typeof FileInput;
    shortDesc: string;
    responsibilities: string[];
    capabilities: {
        canReceivePublic: boolean;
        canCreatePrimaryDisposition: boolean;
        canBranchDisposition: boolean;
        canExecuteTerminalAction: boolean;
    };
    guardrails: string[];
    sampleLetterAction: string;
}

const levelMetaMap: Record<string, LevelWorkflowMeta> = {
    GENERAL_AFFAIRS: {
        code: 'GENERAL_AFFAIRS',
        stepNumber: 1,
        phase: 'Fase Penerimaan & Registrasi',
        badgeColor:
            'bg-sky-500/10 text-sky-700 border-sky-500/20 dark:bg-sky-400/10 dark:text-sky-300 dark:border-sky-400/20',
        glowClass: 'from-sky-500/20 to-blue-600/10',
        activeBorderClass: 'border-sky-500 shadow-sky-500/15',
        icon: FileInput,
        shortDesc:
            'Pintu gerbang registrasi fisik/online, verifikasi berkas, dan penomoran agenda resmi.',
        responsibilities: [
            'Menerima dan memeriksa kelengkapan fisik atau digital surat masuk.',
            'Menerbitkan nomor agenda dan membubuhkan stempel tanda terima resmi.',
            'Mengunggah berkas asli PDF dengan verifikasi hash integritas SHA-256.',
            'Meneruskan berkas yang telah tervalidasi ke meja Pimpinan Eksekutif.',
        ],
        capabilities: {
            canReceivePublic: true,
            canCreatePrimaryDisposition: false,
            canBranchDisposition: false,
            canExecuteTerminalAction: false,
        },
        guardrails: [
            'Level hierarki terendah dalam alur pendaftaran (Order 10).',
            'Tidak berwenang membuat instruksi disposisi substansial.',
            'Setiap pengunggahan dokumen menghasilkan riwayat versi immutable.',
        ],
        sampleLetterAction:
            'Petugas Bagian Umum memverifikasi surat masuk dari instansi luar, mencatat nomor agenda, dan meneruskan berkas ke Sekda/Wali Kota.',
    },
    EXECUTIVE_ENTRY: {
        code: 'EXECUTIVE_ENTRY',
        stepNumber: 2,
        phase: 'Fase Penelaahan Kebijakan & Disposisi Primer',
        badgeColor:
            'bg-amber-500/10 text-amber-700 border-amber-500/20 dark:bg-amber-400/10 dark:text-amber-300 dark:border-amber-400/20',
        glowClass: 'from-amber-500/20 to-orange-600/10',
        activeBorderClass: 'border-amber-500 shadow-amber-500/15',
        icon: Crown,
        shortDesc:
            'Pemegang otoritas penentu kebijakan dan perumus instruksi disposisi pertama.',
        responsibilities: [
            'Menelaah urgensi, perihal, dan derajat kerahasiaan surat masuk.',
            'Menentukan arah kebijakan dan instruksi disposisi primer.',
            'Mendelegasikan mandat penanganan surat ke Asisten / Pembantu Pimpinan.',
            'Memberikan tenggat waktu dan petunjuk khusus tindak lanjut surat.',
        ],
        capabilities: {
            canReceivePublic: false,
            canCreatePrimaryDisposition: true,
            canBranchDisposition: false,
            canExecuteTerminalAction: false,
        },
        guardrails: [
            'Level tertinggi penentu arah kebijakan disposisi (Order 20).',
            'Satu-satunya level yang dapat menerbitkan disposisi primer.',
            'Wajib mengisi catatan arahan sebelum surat diteruskan ke level di bawahnya.',
        ],
        sampleLetterAction:
            'Wali Kota / Sekda menelaah proposal kerja sama, memberikan arahan "Pelajari dan koordinasikan draf telaah", lalu mendisposisikan ke Asisten I.',
    },
    ASSISTANT: {
        code: 'ASSISTANT',
        stepNumber: 3,
        phase: 'Fase Koordinasi Taktis & Percabangan',
        badgeColor:
            'bg-indigo-500/10 text-indigo-700 border-indigo-500/20 dark:bg-indigo-400/10 dark:text-indigo-300 dark:border-indigo-400/20',
        glowClass: 'from-indigo-500/20 to-purple-600/10',
        activeBorderClass: 'border-indigo-500 shadow-indigo-500/15',
        icon: GitFork,
        shortDesc:
            'Koordinator lintas bidang yang membedah arahan eksekutif menjadi tugas sektoral.',
        responsibilities: [
            'Menerjemahkan instruksi primer pimpinan menjadi target kerja teknis.',
            'Membentuk cabang disposisi (branching) ke beberapa Kepala Bagian terkait.',
            'Mengawasi keselarasan tindak lanjut antar unit kerja pendukung.',
            'Melakukan reviu awal atas hasil telaahan staf sebelum diajukan ke pimpinan.',
        ],
        capabilities: {
            canReceivePublic: false,
            canCreatePrimaryDisposition: false,
            canBranchDisposition: true,
            canExecuteTerminalAction: false,
        },
        guardrails: [
            'Berada tepat di bawah Pimpinan Eksekutif (Order 30).',
            'Dapat memecah alur surat menjadi beberapa cabang paralel independen.',
            'Seluruh cabang harus selesai sebelum status surat dapat ditutup.',
        ],
        sampleLetterAction:
            'Asisten membedah arahan pimpinan dan mendisposisikan cabang A ke Kabag Hukum serta cabang B ke Kabag Pemerintahan.',
    },
    SECTION_HEAD: {
        code: 'SECTION_HEAD',
        stepNumber: 4,
        phase: 'Fase Eksekusi Operasional & Penyelesaian',
        badgeColor:
            'bg-emerald-500/10 text-emerald-700 border-emerald-500/20 dark:bg-emerald-400/10 dark:text-emerald-300 dark:border-emerald-400/20',
        glowClass: 'from-emerald-500/20 to-teal-600/10',
        activeBorderClass: 'border-emerald-500 shadow-emerald-500/15',
        icon: FileCheck,
        shortDesc:
            'Ujung tombak teknis yang mengeksekusi instruksi hingga status disposisi selesai.',
        responsibilities: [
            'Menyusun telaahan staf, kajian teknis, atau draf surat balasan.',
            'Melakukan koordinasi lapangan dan verifikasi teknis substantif.',
            'Mengunggah dokumen hasil tindak lanjut sebagai arsip final.',
            'Menandai cabang disposisi sebagai selesai (completed terminal state).',
        ],
        capabilities: {
            canReceivePublic: false,
            canCreatePrimaryDisposition: false,
            canBranchDisposition: false,
            canExecuteTerminalAction: true,
        },
        guardrails: [
            'Level eksekusi teknis paling akhir dalam alur disposisi (Order 40).',
            'Penyelesaian tugas di level ini menyumbang status agregat penyelesaian surat.',
            'Tidak dapat meneruskan disposisi ke tingkat yang lebih rendah lagi.',
        ],
        sampleLetterAction:
            'Kepala Bagian menyelesaikan telaahan staf, mengunggah draf keputusan, dan menekan tombol "Selesaikan Disposisi".',
    },
};

// Sorted levels by hierarchy order
const sortedLevels = computed(() => {
    return [...props.levels].sort(
        (a, b) => a.hierarchy_order - b.hierarchy_order,
    );
});

// Selected Level for Deep Dive Inspector
const selectedCode = ref<string>(
    props.levels[0]?.code || 'GENERAL_AFFAIRS',
);

const activeMeta = computed<LevelWorkflowMeta>(() => {
    return (
        levelMetaMap[selectedCode.value] ||
        levelMetaMap.GENERAL_AFFAIRS
    );
});

const activeLevelData = computed(() => {
    return props.levels.find((l) => l.code === selectedCode.value);
});

function selectLevel(code: string) {
    selectedCode.value = code;
}

// Live Workflow Simulator State
const simulationStep = ref<number>(0);
const isSimulating = ref<boolean>(false);
let simulationTimer: ReturnType<typeof setInterval> | null = null;

const simulationStages = [
    {
        title: 'Surat Masuk Tiba',
        levelCode: 'GENERAL_AFFAIRS',
        levelTitle: 'Bagian Umum / TU',
        status: 'SURAT MASUK DITERIMA',
        desc: 'Surat fisik/online masuk ke loket. Bagian Umum mengunggah scan dokumen dan mencatat nomor agenda resmi.',
    },
    {
        title: 'Penelaahan Pimpinan',
        levelCode: 'EXECUTIVE_ENTRY',
        levelTitle: 'Wali Kota / Sekda',
        status: 'DISPOSISI PRIMER DITERBITKAN',
        desc: 'Pimpinan membaca surat, merumuskan arahan kebijakan strategis, dan menentukan Asisten pembina teknis.',
    },
    {
        title: 'Delegasi & Percabangan',
        levelCode: 'ASSISTANT',
        levelTitle: 'Asisten',
        status: 'DISPOSISI DIBAGI (BRANCHING)',
        desc: 'Asisten membagi instruksi ke satu atau lebih Kepala Bagian teknis secara simultan.',
    },
    {
        title: 'Tindak Lanjut & Penyelesaian',
        levelCode: 'SECTION_HEAD',
        levelTitle: 'Kepala Bagian',
        status: 'DISPOSISI SELESAI (COMPLETED)',
        desc: 'Kepala Bagian menyusun telaahan staf / konsep jawaban surat, mengunggah berkas tindak lanjut, dan menutup tugas.',
    },
];

function setSimulationStep(index: number) {
    simulationStep.value = index;
    const stage = simulationStages[index];

    if (stage) {
        selectedCode.value = stage.levelCode;
    }
}

function toggleSimulationPlay() {
    if (isSimulating.value) {
        stopSimulation();
    } else {
        startSimulation();
    }
}

function startSimulation() {
    isSimulating.value = true;

    if (simulationStep.value >= simulationStages.length - 1) {
        simulationStep.value = 0;
    }

    simulationTimer = setInterval(() => {
        if (simulationStep.value < simulationStages.length - 1) {
            setSimulationStep(simulationStep.value + 1);
        } else {
            stopSimulation();
        }
    }, 2400);
}

function stopSimulation() {
    isSimulating.value = false;

    if (simulationTimer) {
        clearInterval(simulationTimer);
        simulationTimer = null;
    }
}

function resetSimulation() {
    stopSimulation();
    setSimulationStep(0);
}

// GSAP Animations
const containerRef = ref<HTMLElement | null>(null);

onMounted(() => {
    if (!containerRef.value) {
return;
}

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
return;
}

    const ctx = gsap.context(() => {
        gsap.from('.workflow-node-card', {
            opacity: 0,
            y: 24,
            duration: 0.6,
            stagger: 0.1,
            ease: 'power3.out',
        });
        gsap.from('.workflow-detail-panel', {
            opacity: 0,
            scale: 0.98,
            duration: 0.5,
            delay: 0.3,
            ease: 'power2.out',
        });
    }, containerRef.value);

    return () => ctx.revert();
});

watch(selectedCode, () => {
    if (!containerRef.value) {
return;
}

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
return;
}

    gsap.fromTo(
        '.inspector-content',
        { opacity: 0, y: 10 },
        { opacity: 1, y: 0, duration: 0.35, ease: 'power2.out' },
    );
});
</script>

<template>
    <div ref="containerRef" class="space-y-8">
        <!-- ======================================================== -->
        <!-- 1. HERO WORKFLOW PIPELINE ARCHITECTURE                   -->
        <!-- ======================================================== -->
        <section
            class="relative overflow-hidden rounded-3xl border border-border/80 bg-gradient-to-br from-card via-card/95 to-muted/30 p-6 shadow-xl shadow-indigo-500/5 backdrop-blur-2xl sm:p-8 lg:p-10 dark:border-border/60 dark:from-slate-900/90 dark:via-slate-900/80 dark:to-slate-950"
        >
            <!-- Ambient Background Mesh Glows -->
            <div
                aria-hidden="true"
                class="pointer-events-none absolute -top-24 right-0 size-96 rounded-full bg-indigo-500/10 blur-[100px] dark:bg-indigo-600/15"
            />
            <div
                aria-hidden="true"
                class="pointer-events-none absolute -bottom-24 left-0 size-80 rounded-full bg-emerald-500/10 blur-[100px] dark:bg-emerald-600/15"
            />

            <div class="relative z-10">
                <!-- Eyebrow & Headline -->
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div>
                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-indigo-500/20 bg-indigo-500/10 px-3.5 py-1 text-xs font-semibold text-indigo-700 dark:border-indigo-400/20 dark:bg-indigo-400/10 dark:text-indigo-300"
                        >
                            <Workflow class="size-3.5" />
                            <span>Arsitektur Siklus Surat Masuk & Disposisi</span>
                        </div>
                        <h2
                            class="mt-3 text-2xl font-bold tracking-tight text-foreground sm:text-3xl lg:text-4xl"
                        >
                            Alur 4 Level Workflow Hierarkis
                        </h2>
                        <p
                            class="mt-1.5 max-w-3xl text-sm leading-relaxed text-muted-foreground sm:text-base"
                        >
                            Alur disposisi surat diatur secara berjenjang dan terkontrol ketat dari penerimaan hingga eksekusi akhir. Klik pada tiap tahap untuk memeriksa wewenang dan batasan sistem.
                        </p>
                    </div>

                    <!-- Lock Badge -->
                    <div
                        class="flex items-center gap-2 rounded-2xl border border-border/80 bg-background/80 px-4 py-2.5 shadow-sm backdrop-blur-md dark:bg-slate-800/80"
                    >
                        <Lock class="size-4 text-amber-500" />
                        <div>
                            <p class="text-xs font-semibold text-foreground">
                                Sistem Terproteksi
                            </p>
                            <p class="text-[11px] text-muted-foreground">
                                Level Invariant Tidak Dapat Dimutasi
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ======================================================== -->
                <!-- 2. INTERACTIVE PIPELINE STAGES (STEP NODES)             -->
                <!-- ======================================================== -->
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div
                        v-for="(level, index) in sortedLevels"
                        :key="level.id"
                        class="workflow-node-card group relative cursor-pointer"
                        @click="selectLevel(level.code)"
                    >
                        <!-- Card Container -->
                        <div
                            class="relative flex h-full flex-col justify-between overflow-hidden rounded-2xl border bg-card/90 p-5 shadow-sm transition-all duration-300 hover:shadow-lg dark:bg-slate-900/90"
                            :class="[
                                selectedCode === level.code
                                    ? `ring-2 ring-indigo-500/40 dark:ring-indigo-400/50 ${levelMetaMap[level.code]?.activeBorderClass || 'border-indigo-500'}`
                                    : 'border-border/80 hover:border-indigo-500/40',
                            ]"
                        >
                            <!-- Top Ambient Gradient Bar on Active -->
                            <div
                                class="absolute inset-x-0 top-0 h-1 transition-all duration-300"
                                :class="[
                                    selectedCode === level.code
                                        ? 'bg-gradient-to-r from-indigo-500 via-teal-500 to-emerald-500 opacity-100'
                                        : 'bg-transparent opacity-0 group-hover:bg-muted opacity-50',
                                ]"
                            />

                            <!-- Step Number & Icon -->
                            <div>
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="flex size-9 items-center justify-center rounded-xl font-bold text-xs shadow-inner"
                                            :class="[
                                                selectedCode === level.code
                                                    ? 'bg-indigo-600 text-white dark:bg-indigo-500'
                                                    : 'bg-muted text-muted-foreground group-hover:bg-indigo-500/10 group-hover:text-indigo-600',
                                            ]"
                                        >
                                            0{{ index + 1 }}
                                        </div>
                                        <span class="font-mono text-[11px] font-semibold text-muted-foreground">
                                            Order {{ level.hierarchy_order }}
                                        </span>
                                    </div>

                                    <Badge
                                        variant="outline"
                                        class="text-[10px] font-medium"
                                        :class="levelMetaMap[level.code]?.badgeColor"
                                    >
                                        {{ level.is_protected ? 'Protected' : 'Custom' }}
                                    </Badge>
                                </div>

                                <!-- Code & Name -->
                                <div class="mt-4">
                                    <p class="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                                        {{ level.code }}
                                    </p>
                                    <h3 class="mt-1 text-base font-bold text-foreground">
                                        {{ level.name }}
                                    </h3>
                                    <p class="mt-1.5 text-xs text-muted-foreground line-clamp-2">
                                        {{ levelMetaMap[level.code]?.shortDesc }}
                                    </p>
                                </div>
                            </div>

                            <!-- Bottom Meta: Position Count & Arrow Indicator -->
                            <div class="mt-5 flex items-center justify-between border-t border-border/60 pt-3 text-xs">
                                <span class="font-medium text-foreground">
                                    {{ level.position_count }} Jabatan Aktif
                                </span>
                                <span
                                    class="inline-flex items-center gap-1 font-semibold transition-transform duration-200"
                                    :class="[
                                        selectedCode === level.code
                                            ? 'text-indigo-600 dark:text-indigo-400 translate-x-0.5'
                                            : 'text-muted-foreground group-hover:text-foreground',
                                    ]"
                                >
                                    <span>Inspeksi</span>
                                    <ArrowRight class="size-3.5" />
                                </span>
                            </div>
                        </div>

                        <!-- Connector Arrow between items (desktop) -->
                        <div
                            v-if="index < sortedLevels.length - 1"
                            class="pointer-events-none absolute -right-3 top-1/2 z-20 hidden -translate-y-1/2 lg:flex size-6 items-center justify-center rounded-full border border-border/80 bg-background text-muted-foreground shadow-sm dark:bg-slate-800"
                        >
                            <ArrowRight class="size-3" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======================================================== -->
        <!-- 3. STAGE INSPECTOR DEEP DIVE PANEL                       -->
        <!-- ======================================================== -->
        <section
            class="workflow-detail-panel relative overflow-hidden rounded-3xl border border-border/80 bg-card p-6 shadow-xl shadow-black/5 sm:p-8 dark:border-border/60 dark:bg-slate-900/90"
            aria-labelledby="inspector-heading"
        >
            <div class="inspector-content space-y-6">
                <!-- Inspector Header -->
                <div class="flex flex-col gap-4 border-b border-border/70 pb-6 md:flex-row md:items-center md:justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 via-indigo-600 to-violet-700 text-white shadow-lg shadow-indigo-500/20"
                        >
                            <component :is="activeMeta.icon" class="size-7 text-white" />
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs font-bold text-indigo-600 dark:text-indigo-400">
                                    LEVEL 0{{ activeMeta.stepNumber }} · {{ activeMeta.code }}
                                </span>
                                <Badge
                                    variant="outline"
                                    class="text-[10px]"
                                    :class="activeMeta.badgeColor"
                                >
                                    {{ activeMeta.phase }}
                                </Badge>
                            </div>
                            <h3 id="inspector-heading" class="mt-0.5 text-xl font-bold text-foreground sm:text-2xl">
                                {{ activeLevelData?.name || activeMeta.code }}
                            </h3>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="rounded-xl border border-border/70 bg-muted/40 px-3.5 py-2 text-right text-xs">
                            <span class="block text-muted-foreground">Posisi Hirarki</span>
                            <span class="font-bold text-foreground">Urutan {{ activeLevelData?.hierarchy_order || 10 }}</span>
                        </div>
                        <div class="rounded-xl border border-border/70 bg-muted/40 px-3.5 py-2 text-right text-xs">
                            <span class="block text-muted-foreground">Total Pejabat</span>
                            <span class="font-bold text-foreground">{{ activeLevelData?.position_count || 0 }} Jabatan</span>
                        </div>
                    </div>
                </div>

                <!-- 3-Column Bento Grid for Stage Intelligence -->
                <div class="grid gap-6 md:grid-cols-3">
                    <!-- Column 1: Tanggung Jawab Operasional -->
                    <div class="rounded-2xl border border-border/70 bg-muted/30 p-5 space-y-3">
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-foreground">
                            <Activity class="size-4 text-indigo-600 dark:text-indigo-400" />
                            <span>Tanggung Jawab Operasional</span>
                        </div>
                        <ul class="space-y-2.5 text-xs text-muted-foreground">
                            <li
                                v-for="(task, idx) in activeMeta.responsibilities"
                                :key="idx"
                                class="flex items-start gap-2 leading-relaxed"
                            >
                                <CheckCircle2 class="size-3.5 shrink-0 text-emerald-500 mt-0.5" />
                                <span>{{ task }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Column 2: Matriks Kewenangan Disposisi -->
                    <div class="rounded-2xl border border-border/70 bg-muted/30 p-5 space-y-3">
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-foreground">
                            <Shield class="size-4 text-indigo-600 dark:text-indigo-400" />
                            <span>Matriks Hak & Kewenangan</span>
                        </div>
                        <div class="space-y-2 text-xs">
                            <div class="flex items-center justify-between rounded-xl bg-background/80 p-2.5 border border-border/50">
                                <span class="text-muted-foreground">Terima Surat Publik</span>
                                <Badge
                                    :variant="activeMeta.capabilities.canReceivePublic ? 'default' : 'secondary'"
                                    class="text-[10px]"
                                >
                                    {{ activeMeta.capabilities.canReceivePublic ? 'Diizinkan' : 'Dilarang' }}
                                </Badge>
                            </div>
                            <div class="flex items-center justify-between rounded-xl bg-background/80 p-2.5 border border-border/50">
                                <span class="text-muted-foreground">Disposisi Primer</span>
                                <Badge
                                    :variant="activeMeta.capabilities.canCreatePrimaryDisposition ? 'default' : 'secondary'"
                                    class="text-[10px]"
                                >
                                    {{ activeMeta.capabilities.canCreatePrimaryDisposition ? 'Otoritas Tunggal' : 'Tidak Berhak' }}
                                </Badge>
                            </div>
                            <div class="flex items-center justify-between rounded-xl bg-background/80 p-2.5 border border-border/50">
                                <span class="text-muted-foreground">Bagi Cabang (Branching)</span>
                                <Badge
                                    :variant="activeMeta.capabilities.canBranchDisposition ? 'default' : 'secondary'"
                                    class="text-[10px]"
                                >
                                    {{ activeMeta.capabilities.canBranchDisposition ? 'Diizinkan' : 'Tidak Berhak' }}
                                </Badge>
                            </div>
                            <div class="flex items-center justify-between rounded-xl bg-background/80 p-2.5 border border-border/50">
                                <span class="text-muted-foreground">Selesaikan Disposisi</span>
                                <Badge
                                    :variant="activeMeta.capabilities.canExecuteTerminalAction ? 'default' : 'secondary'"
                                    class="text-[10px]"
                                >
                                    {{ activeMeta.capabilities.canExecuteTerminalAction ? 'Terminal Node' : 'Bukan Terminal' }}
                                </Badge>
                            </div>
                        </div>
                    </div>

                    <!-- Column 3: Invarian Keamanan Sistem -->
                    <div class="rounded-2xl border border-border/70 bg-muted/30 p-5 space-y-3">
                        <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-foreground">
                            <Lock class="size-4 text-amber-600 dark:text-amber-400" />
                            <span>Invarian & Guardrails</span>
                        </div>
                        <ul class="space-y-2.5 text-xs text-muted-foreground">
                            <li
                                v-for="(guard, idx) in activeMeta.guardrails"
                                :key="idx"
                                class="flex items-start gap-2 leading-relaxed"
                            >
                                <ShieldAlert class="size-3.5 shrink-0 text-amber-500 mt-0.5" />
                                <span>{{ guard }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Action Example Banner -->
                <div class="rounded-2xl border border-indigo-500/20 bg-indigo-500/5 p-4 text-xs text-foreground dark:border-indigo-400/20 dark:bg-indigo-400/5">
                    <div class="flex items-start gap-2.5">
                        <Sparkles class="size-4 shrink-0 text-indigo-600 dark:text-indigo-400 mt-0.5" />
                        <div>
                            <span class="font-semibold text-indigo-700 dark:text-indigo-300">Contoh Skenario Lapangan:</span>
                            <p class="mt-0.5 text-muted-foreground leading-relaxed">
                                {{ activeMeta.sampleLetterAction }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======================================================== -->
        <!-- 4. LIVE WORKFLOW SIMULATION SANDBOX                      -->
        <!-- ======================================================== -->
        <section
            class="relative overflow-hidden rounded-3xl border border-border/80 bg-gradient-to-br from-card via-card/95 to-muted/20 p-6 shadow-xl shadow-black/5 backdrop-blur-2xl sm:p-8 dark:border-border/60 dark:bg-slate-900/90"
            aria-labelledby="simulation-heading"
        >
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-border/70 pb-5">
                <div>
                    <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                        <Play class="size-3.5" />
                        <span>Simulasi Interaktif</span>
                    </div>
                    <h3 id="simulation-heading" class="text-xl font-bold text-foreground">
                        Simulasi Alur Surat Masuk & Disposisi
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        Uji coba bagaimana sebuah berkas berpindah status antar level dari awal hingga tuntas.
                    </p>
                </div>

                <!-- Simulation Controls -->
                <div class="flex items-center gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        class="gap-1.5 rounded-xl text-xs"
                        @click="resetSimulation"
                    >
                        <RotateCcw class="size-3.5" />
                        <span>Reset</span>
                    </Button>
                    <Button
                        size="sm"
                        class="gap-1.5 rounded-xl bg-indigo-600 text-xs font-semibold text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600"
                        @click="toggleSimulationPlay"
                    >
                        <Play v-if="!isSimulating" class="size-3.5" />
                        <span v-if="!isSimulating">Putar Alur Otomatis</span>
                        <span v-else>Jeda Simulasi</span>
                    </Button>
                </div>
            </div>

            <!-- Simulation Step Tracker -->
            <div class="mt-6">
                <div class="grid gap-3 sm:grid-cols-4">
                    <button
                        v-for="(stage, idx) in simulationStages"
                        :key="idx"
                        type="button"
                        class="flex flex-col items-start rounded-2xl border p-3.5 text-left transition-all duration-200"
                        :class="[
                            simulationStep === idx
                                ? 'border-indigo-600 bg-indigo-50/70 dark:border-indigo-400 dark:bg-indigo-950/40 shadow-sm'
                                : simulationStep > idx
                                  ? 'border-emerald-500/30 bg-emerald-50/40 dark:border-emerald-500/20 dark:bg-emerald-950/20'
                                  : 'border-border/60 bg-muted/20 hover:border-border',
                        ]"
                        @click="setSimulationStep(idx)"
                    >
                        <div class="flex w-full items-center justify-between text-[11px]">
                            <span
                                class="font-bold font-mono"
                                :class="[
                                    simulationStep === idx
                                        ? 'text-indigo-600 dark:text-indigo-400'
                                        : simulationStep > idx
                                          ? 'text-emerald-600 dark:text-emerald-400'
                                          : 'text-muted-foreground',
                                ]"
                            >
                                TAHAP 0{{ idx + 1 }}
                            </span>
                            <Badge
                                v-if="simulationStep === idx"
                                class="bg-indigo-600 text-white text-[9px] px-1.5 py-0"
                            >
                                Aktif
                            </Badge>
                            <CheckCircle2
                                v-else-if="simulationStep > idx"
                                class="size-3.5 text-emerald-600 dark:text-emerald-400"
                            />
                        </div>

                        <span class="mt-2 text-xs font-bold text-foreground">
                            {{ stage.title }}
                        </span>
                        <span class="mt-0.5 font-mono text-[10px] text-muted-foreground">
                            {{ stage.levelTitle }}
                        </span>
                    </button>
                </div>

                <!-- Simulation Active Step Card Box -->
                <div
                    class="mt-6 rounded-2xl border border-border/80 bg-muted/40 p-5 backdrop-blur-sm"
                >
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <Badge class="bg-emerald-600 text-white text-[10px]">
                                    {{ simulationStages[simulationStep]?.status }}
                                </Badge>
                                <span class="text-xs font-semibold text-foreground">
                                    Diproses oleh: {{ simulationStages[simulationStep]?.levelTitle }}
                                </span>
                            </div>
                            <p class="text-xs leading-relaxed text-muted-foreground">
                                {{ simulationStages[simulationStep]?.desc }}
                            </p>
                        </div>

                        <Button
                            v-if="simulationStep < simulationStages.length - 1"
                            size="sm"
                            class="shrink-0 gap-1.5 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 text-xs font-semibold text-white shadow-md shadow-indigo-600/20"
                            @click="setSimulationStep(simulationStep + 1)"
                        >
                            <span>Lanjutkan ke Tahap Berikutnya</span>
                            <ArrowRight class="size-3.5" />
                        </Button>
                        <Button
                            v-else
                            size="sm"
                            variant="secondary"
                            class="shrink-0 gap-1.5 rounded-xl text-xs font-semibold"
                            @click="resetSimulation"
                        >
                            <RotateCcw class="size-3.5" />
                            <span>Mulai Ulang Alur</span>
                        </Button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======================================================== -->
        <!-- 5. INVARIANTS & CLI GUARANTEE BENTO MATRIX               -->
        <!-- ======================================================== -->
        <section
            class="grid grid-flow-dense gap-4 md:grid-cols-3"
            aria-label="Jaminan Integritas Sistem"
        >
            <div class="rounded-2xl border border-border/70 bg-card p-5 shadow-sm space-y-2">
                <div class="flex items-center gap-2 text-xs font-bold text-foreground">
                    <Compass class="size-4 text-indigo-600 dark:text-indigo-400" />
                    <span>Hierarki Menurun (Strict Downward Flow)</span>
                </div>
                <p class="text-xs leading-relaxed text-muted-foreground">
                    Disposisi hanya dapat mengalir dari level dengan hirarki lebih tinggi (Order 20) menuju level yang lebih rendah (Order 30 & 40). Tidak diperkenankan loncatan berlawanan arah.
                </p>
            </div>

            <div class="rounded-2xl border border-border/70 bg-card p-5 shadow-sm space-y-2">
                <div class="flex items-center gap-2 text-xs font-bold text-foreground">
                    <GitFork class="size-4 text-purple-600 dark:text-purple-400" />
                    <span>Integritas Percabangan (Branch Resolution)</span>
                </div>
                <p class="text-xs leading-relaxed text-muted-foreground">
                    Ketika sebuah surat memiliki banyak cabang disposisi, status surat agregat hanya akan ditandai Selesai (Completed) setelah seluruh cabang diselesaikan oleh Kepala Bagian terkait.
                </p>
            </div>

            <div class="rounded-2xl border border-border/70 bg-card p-5 shadow-sm space-y-2">
                <div class="flex items-center gap-2 text-xs font-bold text-foreground">
                    <Lock class="size-4 text-amber-600 dark:text-amber-400" />
                    <span>Sinkronisasi Baris Perintah (CLI Invariant)</span>
                </div>
                <p class="text-xs leading-relaxed text-muted-foreground">
                    Empat level dasar ini merupakan katalog yang dilindungi sistem. Pembaruan kode dan urutan level hanya dapat dilakukan melalui perintah aman:
                    <code class="mt-1 block rounded-lg bg-muted px-2 py-1 font-mono text-[11px] text-foreground">
                        php artisan organization:sync-levels
                    </code>
                </p>
            </div>
        </section>
    </div>
</template>
