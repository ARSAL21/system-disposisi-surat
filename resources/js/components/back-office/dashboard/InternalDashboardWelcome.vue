<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    Archive,
    ArrowRight,
    ClipboardCheck,
    History,
    Inbox,
    Landmark,
    ListChecks,
    Lock,
    MailOpen,
    Network,
    Route as RouteIcon,
    Shield,
    ShieldCheck,
    UserRoundCog,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import IntakeQueueStatsCards from '@/components/back-office/dashboard/IntakeQueueStatsCards.vue';
import IntakeQueueTabbedTable from '@/components/back-office/dashboard/IntakeQueueTabbedTable.vue';
import IntakeQuickReviewDialog from '@/components/back-office/dashboard/IntakeQuickReviewDialog.vue';
import { Badge } from '@/components/ui/badge';
import { previewIntakeDashboardData } from '@/lib/intakeDashboardPreview';
import type { IntakeDashboardData, IntakeQueueItem } from '@/types';

const props = defineProps<{
    userName: string;
    dashboardData?: IntakeDashboardData | null;
    preview?: boolean;
}>();

const page = usePage();
const capabilities = computed(() => page.props.auth.capabilities || {});
const user = computed(() => page.props.auth.user);

const intakeData = computed<IntakeDashboardData | null>(() => {
    if (props.preview) {
        return props.dashboardData ?? previewIntakeDashboardData;
    }

    return props.dashboardData ?? null;
});

const activeTab = ref<string>('ALL');
const selectedSubmission = ref<IntakeQueueItem | null>(null);
const isQuickReviewOpen = ref<boolean>(false);

function handleOpenReview(submission: IntakeQueueItem): void {
    selectedSubmission.value = submission;
    isQuickReviewOpen.value = true;
}

function handleSelectFilter(filterKey: string): void {
    activeTab.value = filterKey;
}

const letterOperationModules = computed(() => {
    const list = [];

    if (capabilities.value.can_view_intake) {
        list.push({
            title: 'Penerimaan Surat Masuk',
            description:
                'Screening dan verifikasi administrasi pengajuan surat dari publik dan loket manual.',
            href: '/back-office/intake/submissions',
            icon: Inbox,
            color: 'from-blue-500 to-indigo-600',
            bgLight: 'bg-blue-50 dark:bg-blue-950/50',
            textLight: 'text-blue-600 dark:text-blue-300',
            badge: 'Administrasi',
        });
    }

    if (capabilities.value.can_decide_intake) {
        list.push({
            title: 'Persetujuan & Registrasi',
            description:
                'Meja keputusan resmi Kepala Bagian Umum untuk mengesahkan atau mengembalikan surat.',
            href: '/back-office/intake/approvals',
            icon: ClipboardCheck,
            color: 'from-indigo-500 to-violet-600',
            bgLight: 'bg-indigo-50 dark:bg-indigo-950/50',
            textLight: 'text-indigo-600 dark:text-indigo-300',
            badge: 'Keputusan',
        });
    }

    if (capabilities.value.can_view_document_versions) {
        list.push({
            title: 'Arsip Dokumen',
            description:
                'Pencarian dan peninjauan riwayat integritas dokumen resmi serta versi koreksi.',
            href: '/back-office/documents',
            icon: Archive,
            color: 'from-cyan-500 to-blue-600',
            bgLight: 'bg-cyan-50 dark:bg-cyan-950/50',
            textLight: 'text-cyan-600 dark:text-cyan-300',
            badge: 'Arsip & Integritas',
        });
    }

    if (capabilities.value.can_view_letter_routing) {
        list.push({
            title: 'Routing Surat',
            description:
                'Tinjau dokumen resmi terkini dan arahkan surat terdaftar kepada satu pimpinan tujuan.',
            href: '/back-office/letter-routing',
            icon: RouteIcon,
            color: 'from-violet-500 to-fuchsia-600',
            bgLight: 'bg-violet-50 dark:bg-violet-950/50',
            textLight: 'text-violet-600 dark:text-violet-300',
            badge: 'Routing Awal',
        });
    }

    if (capabilities.value.can_view_executive_inbox) {
        list.push({
            title: 'Inbox Pimpinan',
            description:
                'Periksa surat resmi dan buat disposisi pertama kepada satu jabatan Asisten yang sah.',
            href: '/back-office/executive/inbox',
            icon: Landmark,
            color: 'from-amber-500 to-orange-600',
            bgLight: 'bg-amber-50 dark:bg-amber-950/50',
            textLight: 'text-amber-700 dark:text-amber-300',
            badge: 'Meja Pimpinan',
        });
    }

    if (capabilities.value.can_view_dispositions) {
        list.push({
            title: 'Inbox Disposisi',
            description:
                'Tinjau surat dan instruksi resmi yang ditujukan kepada jabatan Asisten aktif Anda.',
            href: '/back-office/dispositions/inbox',
            icon: MailOpen,
            color: 'from-blue-600 to-emerald-600',
            bgLight: 'bg-blue-50 dark:bg-blue-950/50',
            textLight: 'text-blue-700 dark:text-blue-300',
            badge: 'Meja Asisten',
        });
    }

    if (capabilities.value.can_view_letter_activities) {
        list.push({
            title: 'Aktivitas Surat',
            description:
                'Jejak audit dan rekaman aktivitas penerimaan serta registrasi surat masuk.',
            href: '/back-office/audits/letters',
            icon: Activity,
            color: 'from-teal-500 to-emerald-600',
            bgLight: 'bg-teal-50 dark:bg-teal-950/50',
            textLight: 'text-teal-600 dark:text-teal-300',
            badge: 'Audit Surat',
        });
    }

    return list;
});

const systemGovernanceModules = computed(() => {
    const list = [];

    if (capabilities.value.can_view_authorization) {
        list.push({
            title: 'Manajemen Role & Akses',
            description:
                'Konfigurasi role, permission, dan matriks wewenang operasional aplikasi.',
            href: '/back-office/authorization/roles',
            icon: ShieldCheck,
            color: 'from-violet-500 to-purple-600',
            bgLight: 'bg-violet-50 dark:bg-violet-950/50',
            textLight: 'text-violet-600 dark:text-violet-300',
            badge: 'Security',
        });
    }

    if (capabilities.value.can_view_organization) {
        list.push(
            {
                title: 'Struktur Organisasi',
                description:
                    'Master unit kerja dan jabatan organisasi Pemerintah Kota.',
                href: '/back-office/organization/structure',
                icon: Network,
                color: 'from-emerald-500 to-teal-600',
                bgLight: 'bg-emerald-50 dark:bg-emerald-950/50',
                textLight: 'text-emerald-600 dark:text-emerald-300',
                badge: 'Master Data',
            },
            {
                title: 'Penugasan Jabatan',
                description:
                    'Pengelolaan pejabat aktif dan histori penetapan pemegang posisi.',
                href: '/back-office/organization/assignments',
                icon: UserRoundCog,
                color: 'from-amber-500 to-orange-600',
                bgLight: 'bg-amber-50 dark:bg-amber-950/50',
                textLight: 'text-amber-600 dark:text-amber-300',
                badge: 'Kepegawaian',
            },
        );
    }

    if (capabilities.value.can_view_privilege_audits) {
        list.push({
            title: 'Audit Perubahan Privilege',
            description:
                'Jejak rekaman append-only terkait mutasi akun internal, role, dan permission.',
            href: '/back-office/audits/privileges',
            icon: History,
            color: 'from-slate-600 to-slate-800',
            bgLight: 'bg-slate-100 dark:bg-slate-800/60',
            textLight: 'text-slate-700 dark:text-slate-300',
            badge: 'Audit Trail',
        });
    }

    if (capabilities.value.can_view_disposition_instructions) {
        list.push({
            title: 'Instruksi Disposisi',
            description:
                'Kelola label instruksi aktif tanpa mengubah snapshot histori disposisi lama.',
            href: '/back-office/workflow/instruction-labels',
            icon: ListChecks,
            color: 'from-blue-600 to-cyan-600',
            bgLight: 'bg-blue-50 dark:bg-blue-950/50',
            textLight: 'text-blue-700 dark:text-blue-300',
            badge: 'Workflow',
        });
    }

    return list;
});

const totalActiveModules = computed(() => {
    return (
        letterOperationModules.value.length +
        systemGovernanceModules.value.length
    );
});
</script>

<template>
    <div class="space-y-8">
        <!-- Hero Banner -->
        <section
            class="relative overflow-hidden rounded-3xl border border-indigo-100/90 bg-gradient-to-r from-white via-indigo-50/50 to-violet-50/60 p-6 shadow-sm sm:p-8 lg:p-10 dark:border-indigo-900/40 dark:from-slate-950 dark:via-indigo-950/30 dark:to-violet-950/30"
        >
            <div
                aria-hidden="true"
                class="pointer-events-none absolute -top-24 -right-24 size-72 rounded-full bg-indigo-400/15 blur-3xl dark:bg-indigo-500/10"
            />
            <div
                aria-hidden="true"
                class="pointer-events-none absolute -bottom-16 left-1/4 size-56 rounded-full bg-violet-400/15 blur-3xl dark:bg-violet-500/10"
            />

            <div
                class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="max-w-2xl space-y-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge
                            variant="outline"
                            class="border-indigo-300/80 bg-indigo-50/90 font-medium text-indigo-700 dark:border-indigo-700/60 dark:bg-indigo-950/80 dark:text-indigo-200"
                        >
                            <ShieldCheck class="mr-1.5 size-3.5" />
                            Portal Internal Pemkot
                        </Badge>
                        <Badge
                            v-if="user?.two_factor_enabled"
                            variant="secondary"
                            class="bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
                        >
                            <Lock class="mr-1 size-3 text-emerald-600" />
                            MFA Aktif & Terlindungi
                        </Badge>
                        <Badge
                            v-else
                            variant="secondary"
                            class="bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        >
                            Akun Internal
                        </Badge>
                        <Badge
                            v-if="preview"
                            variant="outline"
                            class="border-amber-400 bg-amber-50 font-mono text-[10px] text-amber-700 dark:bg-amber-950/50 dark:text-amber-300"
                        >
                            Mode Pratinjau Lokal
                        </Badge>
                    </div>

                    <h1
                        class="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl"
                    >
                        Selamat datang,
                        <span
                            class="bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent dark:from-indigo-400 dark:to-violet-400"
                            >{{ userName }}</span
                        >.
                    </h1>

                    <p
                        class="text-sm leading-relaxed text-muted-foreground sm:text-base"
                    >
                        Sistem Disposisi Surat Pemerintah Kota. Akses modul
                        kerja dan wewenang telah disinkronkan secara aman dengan
                        kapabilitas akun dan penugasan jabatan aktif Anda.
                    </p>
                </div>

                <div
                    class="flex flex-col gap-2 rounded-2xl border border-black/5 bg-white/80 p-4 shadow-xs backdrop-blur-md dark:border-white/10 dark:bg-slate-900/80"
                >
                    <div
                        class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Ringkasan Akses
                    </div>
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-10 items-center justify-center rounded-xl bg-indigo-50 text-sm font-bold text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300"
                        >
                            {{ totalActiveModules }}
                        </div>
                        <div>
                            <div class="text-sm font-bold text-foreground">
                                Modul Operasional Aktif
                            </div>
                            <div class="text-xs text-muted-foreground">
                                Terhubung dengan server
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Petugas Surat (General Affairs) Operational Workspace -->
        <section
            v-if="(capabilities.can_view_intake || preview) && intakeData"
            class="space-y-5"
            aria-label="Workspace Operasional Petugas Surat"
        >
            <div
                class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between"
            >
                <div>
                    <h2
                        class="text-lg font-bold tracking-tight text-foreground sm:text-xl"
                    >
                        Antrean Operasional Petugas Surat
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Ringkasan status pengajuan surat masuk dan antrean
                        screening administrasi harian.
                    </p>
                </div>

                <Link
                    href="/back-office/intake/submissions"
                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary hover:underline"
                >
                    <span>Buka Antrean Lengkap</span>
                    <ArrowRight class="size-3.5" aria-hidden="true" />
                </Link>
            </div>

            <!-- 4 Metrics Stats Cards -->
            <IntakeQueueStatsCards
                :metrics="intakeData.metrics"
                :active-filter="activeTab"
                @select-filter="handleSelectFilter"
            />

            <!-- Tabbed Submissions Table -->
            <IntakeQueueTabbedTable
                :submissions="intakeData.recent_submissions"
                :active-tab="activeTab"
                @update:active-tab="activeTab = $event"
                @open-review="handleOpenReview"
            />
        </section>

        <!-- Group 1: Operasional Persuratan -->
        <div v-if="letterOperationModules.length > 0" class="space-y-4">
            <div>
                <h2 class="text-lg font-bold tracking-tight text-foreground">
                    Operasional Persuratan
                </h2>
                <p class="text-xs text-muted-foreground">
                    Modul penanganan intake surat masuk, persetujuan pimpinan,
                    arsip berkas, dan histori aktivitas.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="item in letterOperationModules"
                    :key="item.href"
                    :href="item.href"
                    class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-border/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-1 hover:border-indigo-300 hover:shadow-md dark:border-white/10 dark:bg-slate-900 dark:hover:border-indigo-800"
                >
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div
                                :class="[
                                    'flex size-11 items-center justify-center rounded-2xl transition-transform duration-200 group-hover:scale-110',
                                    item.bgLight,
                                    item.textLight,
                                ]"
                            >
                                <component :is="item.icon" class="size-5" />
                            </div>
                            <Badge
                                variant="outline"
                                class="text-[10px] font-medium"
                            >
                                {{ item.badge }}
                            </Badge>
                        </div>

                        <div class="space-y-1">
                            <h3
                                class="text-sm font-bold text-foreground transition-colors group-hover:text-indigo-600 dark:group-hover:text-indigo-400"
                            >
                                {{ item.title }}
                            </h3>
                            <p
                                class="line-clamp-2 text-xs leading-relaxed text-muted-foreground"
                            >
                                {{ item.description }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-4 flex items-center gap-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400"
                    >
                        <span>Buka Modul</span>
                        <ArrowRight
                            class="size-3.5 transition-transform group-hover:translate-x-1"
                        />
                    </div>
                </Link>
            </div>
        </div>

        <!-- Group 2: Tata Kelola & Keamanan Sistem -->
        <div v-if="systemGovernanceModules.length > 0" class="space-y-4">
            <div>
                <h2 class="text-lg font-bold tracking-tight text-foreground">
                    Tata Kelola & Keamanan Sistem
                </h2>
                <p class="text-xs text-muted-foreground">
                    Modul konfigurasi organisasi, manajemen role wewenang, dan
                    jejak audit administrator.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <Link
                    v-for="item in systemGovernanceModules"
                    :key="item.href"
                    :href="item.href"
                    class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-border/80 bg-white p-5 shadow-xs transition-all duration-200 hover:-translate-y-1 hover:border-indigo-300 hover:shadow-md dark:border-white/10 dark:bg-slate-900 dark:hover:border-indigo-800"
                >
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div
                                :class="[
                                    'flex size-11 items-center justify-center rounded-2xl transition-transform duration-200 group-hover:scale-110',
                                    item.bgLight,
                                    item.textLight,
                                ]"
                            >
                                <component :is="item.icon" class="size-5" />
                            </div>
                            <Badge
                                variant="outline"
                                class="text-[10px] font-medium"
                            >
                                {{ item.badge }}
                            </Badge>
                        </div>

                        <div class="space-y-1">
                            <h3
                                class="text-sm font-bold text-foreground transition-colors group-hover:text-indigo-600 dark:group-hover:text-indigo-400"
                            >
                                {{ item.title }}
                            </h3>
                            <p
                                class="line-clamp-2 text-xs leading-relaxed text-muted-foreground"
                            >
                                {{ item.description }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-4 flex items-center gap-1 text-xs font-semibold text-indigo-600 dark:text-indigo-400"
                    >
                        <span>Buka Modul</span>
                        <ArrowRight
                            class="size-3.5 transition-transform group-hover:translate-x-1"
                        />
                    </div>
                </Link>
            </div>
        </div>

        <!-- Empty state fallback if user has no assigned operational modules -->
        <div
            v-if="totalActiveModules === 0"
            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border p-10 text-center"
        >
            <div
                class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-muted text-muted-foreground"
            >
                <Shield class="size-6" />
            </div>
            <h3 class="text-sm font-semibold text-foreground">
                Belum Ada Modul Operasional yang Ditugaskan
            </h3>
            <p class="mt-1 max-w-sm text-xs text-muted-foreground">
                Hubungi administrator sistem untuk menetapkan role atau
                penugasan jabatan sesuai tanggung jawab kerja Anda.
            </p>
        </div>

        <!-- Quick Review Modal Dialog -->
        <IntakeQuickReviewDialog
            :open="isQuickReviewOpen"
            :submission="selectedSubmission"
            @update:open="isQuickReviewOpen = $event"
        />
    </div>
</template>
