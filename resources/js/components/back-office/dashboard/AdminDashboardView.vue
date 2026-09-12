<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    ArrowUpRight,
    CheckCircle2,
    ChevronRight,
    Clock,
    FileText,
    Fingerprint,
    History,
    Layers,
    Network,
    Send,
    ShieldCheck,
    UserCheck,
    Users,
    Wifi,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { previewAdminDashboardData } from '@/fixtures/admin-dashboard-preview';
import type { AdminDashboardData } from '@/types/admin-dashboard';

const props = defineProps<{
    dashboardData?: AdminDashboardData | null;
    userName?: string;
    preview?: boolean;
}>();

const adminData = computed<AdminDashboardData>(() => {
    if (props.dashboardData) {
        return props.dashboardData;
    }

    return previewAdminDashboardData;
});

const isOnlineDetailsOpen = ref<boolean>(false);

const currentDateFormatted = computed(() => {
    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    }).format(new Date());
});

const internalPercent = computed(() => {
    const total = adminData.value.users.total;

    if (total === 0) {
        return 0;
    }

    return Math.round((adminData.value.users.internal_count / total) * 100);
});

const publicPercent = computed(() => {
    const total = adminData.value.users.total;

    if (total === 0) {
        return 0;
    }

    return Math.round((adminData.value.users.public_count / total) * 100);
});

function getEventBadgeClass(eventType: string): string {
    const type = eventType.toUpperCase();

    if (type.includes('INVIT')) {
        return 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/50 dark:text-blue-300 dark:border-blue-900';
    }

    if (
        type.includes('MFA') ||
        type.includes('2FA') ||
        type.includes('PASSKEY')
    ) {
        return 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/50 dark:text-emerald-300 dark:border-emerald-900';
    }

    if (
        type.includes('ROLE') ||
        type.includes('PERMISSION') ||
        type.includes('PRIVILEGE')
    ) {
        return 'bg-purple-50 text-purple-700 border-purple-200 dark:bg-purple-950/50 dark:text-purple-300 dark:border-purple-900';
    }

    if (
        type.includes('DEACTIVAT') ||
        type.includes('SUSPEND') ||
        type.includes('REVOK')
    ) {
        return 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-900';
    }

    return 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700';
}
</script>

<template>
    <div class="space-y-6">
        <!-- Header Command Center Banner -->
        <section
            class="relative overflow-hidden rounded-3xl border border-border/80 bg-gradient-to-r from-card via-card/90 to-muted/40 p-6 shadow-xs sm:p-8 dark:border-border/60"
        >
            <div
                aria-hidden="true"
                class="pointer-events-none absolute -top-24 -right-24 size-80 rounded-full bg-primary/10 blur-3xl"
            />
            <div
                aria-hidden="true"
                class="pointer-events-none absolute -bottom-16 left-1/3 size-64 rounded-full bg-indigo-500/10 blur-3xl"
            />

            <div
                class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between"
            >
                <div class="max-w-2xl space-y-2.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge
                            variant="outline"
                            class="border-primary/30 bg-primary/10 font-medium text-primary dark:bg-primary/20"
                        >
                            <ShieldCheck class="mr-1.5 size-3.5" />
                            Pusat Kendali Administrator
                        </Badge>

                        <div
                            class="inline-flex items-center gap-2 rounded-full border border-emerald-500/20 bg-emerald-50/80 px-2.5 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300"
                        >
                            <span class="relative flex size-2">
                                <span
                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                                />
                                <span
                                    class="relative inline-flex size-2 rounded-full bg-emerald-500"
                                />
                            </span>
                            <span>Sistem Operasional</span>
                        </div>

                        <Badge
                            v-if="preview"
                            variant="outline"
                            class="border-amber-400/60 bg-amber-50 font-mono text-[10px] text-amber-700 dark:bg-amber-950/40 dark:text-amber-300"
                        >
                            Mode Pratinjau
                        </Badge>
                    </div>

                    <h1
                        class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl"
                    >
                        Dashboard Administrator
                    </h1>

                    <p class="text-sm leading-relaxed text-muted-foreground">
                        Monitoring real-time telemetri sesi aktif, kepatuhan
                        keamanan MFA, throughput alur disposisi, dan tata kelola
                        struktur organisasi.
                    </p>
                </div>

                <div
                    class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center"
                >
                    <div
                        class="flex flex-col gap-1 rounded-2xl border bg-background/80 px-4 py-3 text-xs text-muted-foreground shadow-xs backdrop-blur-xs"
                    >
                        <div
                            class="flex items-center gap-1.5 font-medium text-foreground"
                        >
                            <Clock class="size-3.5 text-primary" />
                            <span>{{ currentDateFormatted }}</span>
                        </div>
                        <div>Zona Waktu Resmi Server (WITA)</div>
                    </div>

                    <div class="flex items-center gap-2">
                        <Button
                            as-child
                            variant="outline"
                            class="gap-1.5 border-border/80 text-xs font-medium"
                        >
                            <Link href="/back-office/users">
                                <Users class="size-3.5" />
                                <span>Kelola Pengguna</span>
                            </Link>
                        </Button>
                        <Button
                            as-child
                            class="gap-1.5 bg-primary text-xs font-medium text-primary-foreground hover:bg-primary/90"
                        >
                            <Link href="/back-office/audits/privileges">
                                <History class="size-3.5" />
                                <span>Log Audit</span>
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Bento Grid Row 1: 4 Key Metric Cards -->
        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Card 1: Pengguna Online -->
            <div
                class="group relative flex flex-col justify-between rounded-2xl border bg-card p-5 shadow-xs transition-all hover:border-primary/40 hover:shadow-sm"
            >
                <div>
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Pengguna Online
                        </span>
                        <div class="flex items-center gap-1.5">
                            <span class="relative flex size-2.5">
                                <span
                                    class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"
                                />
                                <span
                                    class="relative inline-flex size-2.5 rounded-full bg-emerald-500"
                                />
                            </span>
                            <span
                                class="text-[11px] font-medium text-emerald-600 dark:text-emerald-400"
                                >Live</span
                            >
                        </div>
                    </div>

                    <div class="mt-3 flex items-baseline gap-2">
                        <span
                            class="text-3xl font-bold tracking-tight text-foreground tabular-nums"
                        >
                            {{ adminData.users.online_count }}
                        </span>
                        <span class="text-xs text-muted-foreground">
                            sesi aktif (5 mnt)
                        </span>
                    </div>

                    <p class="mt-1 text-xs text-muted-foreground">
                        dari
                        <span
                            class="font-medium text-foreground tabular-nums"
                            >{{ adminData.users.total }}</span
                        >
                        total pengguna terdaftar
                    </p>
                </div>

                <div
                    class="mt-4 flex items-center justify-between border-t border-border/60 pt-3 text-xs"
                >
                    <button
                        type="button"
                        class="inline-flex cursor-pointer items-center gap-1 font-medium text-primary hover:underline"
                        @click="isOnlineDetailsOpen = true"
                    >
                        <span>Lihat daftar sesi</span>
                        <ArrowUpRight class="size-3" />
                    </button>
                    <span class="text-[11px] text-muted-foreground">
                        {{ adminData.users.recent_online.length }} terdeteksi
                    </span>
                </div>
            </div>

            <!-- Card 2: Demografi Pengguna -->
            <div
                class="group relative flex flex-col justify-between rounded-2xl border bg-card p-5 shadow-xs transition-all hover:border-primary/40 hover:shadow-sm"
            >
                <div>
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Demografi Pengguna
                        </span>
                        <div
                            class="rounded-xl bg-indigo-50 p-2 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-300"
                        >
                            <UserCheck class="size-4" />
                        </div>
                    </div>

                    <div class="mt-3 flex items-baseline justify-between">
                        <div class="flex items-baseline gap-1.5">
                            <span
                                class="text-3xl font-bold tracking-tight text-foreground tabular-nums"
                            >
                                {{ adminData.users.internal_count }}
                            </span>
                            <span
                                class="text-xs font-medium text-indigo-600 dark:text-indigo-400"
                                >ASN / Internal</span
                            >
                        </div>
                        <div class="flex items-baseline gap-1.5">
                            <span
                                class="text-xl font-bold tracking-tight text-muted-foreground tabular-nums"
                            >
                                {{ adminData.users.public_count }}
                            </span>
                            <span class="text-xs text-muted-foreground"
                                >Publik</span
                            >
                        </div>
                    </div>

                    <!-- Progress bar -->
                    <div
                        class="mt-3 flex h-2 w-full overflow-hidden rounded-full bg-muted"
                    >
                        <div
                            class="bg-indigo-600 transition-all dark:bg-indigo-500"
                            :style="{ width: `${internalPercent}%` }"
                            title="Internal ASN"
                        />
                        <div
                            class="bg-slate-400 transition-all dark:bg-slate-600"
                            :style="{ width: `${publicPercent}%` }"
                            title="Publik"
                        />
                    </div>
                </div>

                <div
                    class="mt-4 flex items-center justify-between border-t border-border/60 pt-3 text-xs text-muted-foreground"
                >
                    <span class="flex items-center gap-1.5">
                        <span class="size-1.5 rounded-full bg-emerald-500" />
                        <span>{{ adminData.users.active_count }} Aktif</span>
                    </span>
                    <span class="flex items-center gap-1.5">
                        <span class="size-1.5 rounded-full bg-amber-500" />
                        <span
                            >{{
                                adminData.users.suspended_count
                            }}
                            Nonaktif</span
                        >
                    </span>
                </div>
            </div>

            <!-- Card 3: Kepatuhan Keamanan & 2FA -->
            <div
                class="group relative flex flex-col justify-between rounded-2xl border bg-card p-5 shadow-xs transition-all hover:border-primary/40 hover:shadow-sm"
            >
                <div>
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Kepatuhan 2FA / MFA
                        </span>
                        <div
                            class="rounded-xl bg-emerald-50 p-2 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300"
                        >
                            <ShieldCheck class="size-4" />
                        </div>
                    </div>

                    <div class="mt-3 flex items-baseline gap-2">
                        <span
                            class="text-3xl font-bold tracking-tight text-foreground tabular-nums"
                        >
                            {{ adminData.security.internal_mfa_percentage }}%
                        </span>
                        <span
                            class="text-xs font-medium text-emerald-600 dark:text-emerald-400"
                        >
                            Terlindungi
                        </span>
                    </div>

                    <p class="mt-1 text-xs text-muted-foreground">
                        <span
                            class="font-medium text-foreground tabular-nums"
                            >{{ adminData.security.internal_mfa_enabled }}</span
                        >
                        dari
                        <span
                            class="font-medium text-foreground tabular-nums"
                            >{{ adminData.users.internal_count }}</span
                        >
                        ASN mengaktifkan MFA
                    </p>
                </div>

                <div
                    class="mt-4 flex items-center justify-between border-t border-border/60 pt-3 text-xs"
                >
                    <div
                        v-if="adminData.security.super_admins_without_mfa > 0"
                        class="inline-flex items-center gap-1 font-medium text-amber-600 dark:text-amber-400"
                    >
                        <AlertCircle class="size-3" />
                        <span
                            >{{
                                adminData.security.super_admins_without_mfa
                            }}
                            Admin belum 2FA</span
                        >
                    </div>
                    <div
                        v-else
                        class="inline-flex items-center gap-1 font-medium text-emerald-600 dark:text-emerald-400"
                    >
                        <CheckCircle2 class="size-3" />
                        <span>Admin 100% 2FA</span>
                    </div>

                    <span
                        class="inline-flex items-center gap-1 text-muted-foreground"
                    >
                        <Fingerprint class="size-3" />
                        <span
                            >{{
                                adminData.security.passkeys_count
                            }}
                            Passkey</span
                        >
                    </span>
                </div>
            </div>

            <!-- Card 4: Throughput Persuratan & Disposisi -->
            <div
                class="group relative flex flex-col justify-between rounded-2xl border bg-card p-5 shadow-xs transition-all hover:border-primary/40 hover:shadow-sm"
            >
                <div>
                    <div class="flex items-center justify-between">
                        <span
                            class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
                        >
                            Throughput Surat
                        </span>
                        <div
                            class="rounded-xl bg-blue-50 p-2 text-blue-600 dark:bg-blue-950/50 dark:text-blue-300"
                        >
                            <Send class="size-4" />
                        </div>
                    </div>

                    <div class="mt-3 flex items-baseline gap-2">
                        <span
                            class="text-3xl font-bold tracking-tight text-foreground tabular-nums"
                        >
                            {{ adminData.workflow.incoming_letters_count }}
                        </span>
                        <span class="text-xs text-muted-foreground">
                            surat terdaftar
                        </span>
                    </div>

                    <p class="mt-1 text-xs text-muted-foreground">
                        Total volume surat masuk dalam sistem
                    </p>
                </div>

                <div
                    class="mt-4 flex items-center justify-between border-t border-border/60 pt-3 text-xs"
                >
                    <span
                        class="inline-flex items-center gap-1 font-medium text-indigo-600 dark:text-indigo-400"
                    >
                        <Activity class="size-3" />
                        <span
                            >{{
                                adminData.workflow.active_dispositions_count
                            }}
                            Disposisi Aktif</span
                        >
                    </span>
                    <span
                        class="inline-flex items-center gap-1 text-muted-foreground"
                    >
                        <CheckCircle2 class="size-3 text-emerald-500" />
                        <span
                            >{{
                                adminData.workflow.completed_dispositions_count
                            }}
                            Selesai</span
                        >
                    </span>
                </div>
            </div>
        </section>

        <!-- Bento Grid Row 2: Beban Disposisi & Master Tata Kelola (60% / 40%) -->
        <section class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- Left: Beban & Antrean Disposisi per Unit Kerja (7 cols) -->
            <div
                class="flex flex-col justify-between rounded-2xl border bg-card p-6 shadow-xs lg:col-span-7"
            >
                <div>
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <h2
                                    class="text-base font-bold tracking-tight text-foreground"
                                >
                                    Beban Disposisi per Unit Kerja
                                </h2>
                                <Badge
                                    variant="secondary"
                                    class="font-mono text-[10px]"
                                >
                                    Bottleneck Monitor
                                </Badge>
                            </div>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                Pemantauan volume antrean disposisi pending per unit struktural untuk mitigasi kendala administratif.
                            </p>
                        </div>

                        <Button
                            as-child
                            variant="outline"
                            size="sm"
                            class="h-8 w-fit gap-1.5 border-border/80 text-xs font-medium text-foreground"
                        >
                            <Link href="/back-office/organization/assignments">
                                <UserCheck class="size-3.5 text-primary" />
                                <span>Penugasan Pejabat</span>
                            </Link>
                        </Button>
                    </div>

                    <!-- List of Top Pending Units -->
                    <div class="mt-6 space-y-4">
                        <div
                            v-if="
                                adminData.workflow.top_pending_units.length ===
                                0
                            "
                            class="flex flex-col items-center justify-center py-8 text-center"
                        >
                            <div
                                class="rounded-full bg-emerald-50 p-3 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400"
                            >
                                <CheckCircle2 class="size-6" />
                            </div>
                            <h3
                                class="mt-2 text-sm font-semibold text-foreground"
                            >
                                Tidak Ada Antrean Tertunda
                            </h3>
                            <p
                                class="mt-1 max-w-sm text-xs text-muted-foreground"
                            >
                                Seluruh instruksi disposisi surat telah
                                diselesaikan oleh masing-masing unit kerja
                                operasional.
                            </p>
                        </div>

                        <div
                            v-for="(unit, index) in adminData.workflow
                                .top_pending_units"
                            :key="index"
                            class="group rounded-xl border border-border/60 bg-background/50 p-3.5 transition-all hover:border-border hover:bg-background"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <Badge
                                            variant="outline"
                                            class="border-border/80 font-mono text-[10px] text-muted-foreground"
                                        >
                                            {{ unit.unit_code }}
                                        </Badge>
                                        <span
                                            class="text-xs font-semibold text-foreground transition-colors group-hover:text-primary"
                                        >
                                            {{ unit.unit_name }}
                                        </span>
                                    </div>
                                    <div
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Jabatan Struktural:
                                        <span
                                            class="font-medium text-foreground/80"
                                            >{{ unit.position_name }}</span
                                        >
                                    </div>
                                </div>

                                <div class="flex flex-col items-end gap-1 shrink-0">
                                    <span
                                        class="inline-flex items-center gap-1 rounded-lg bg-amber-50 px-2 py-0.5 text-xs font-bold text-amber-700 tabular-nums dark:bg-amber-950/50 dark:text-amber-300"
                                    >
                                        {{ unit.pending_count }} Instruksi
                                    </span>
                                    <Link
                                        href="/back-office/organization/assignments"
                                        class="text-[10px] font-medium text-primary hover:underline"
                                    >
                                        Cek Pejabat
                                    </Link>
                                </div>
                            </div>

                            <!-- Visual indicator bar -->
                            <div
                                class="mt-2.5 h-1.5 w-full overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full rounded-full bg-amber-500/80 transition-all duration-500"
                                    :style="{
                                        width: `${Math.min(100, Math.max(15, (unit.pending_count / (adminData.workflow.active_dispositions_count || 1)) * 100))}%`,
                                    }"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-t border-border/60 pt-4 text-xs text-muted-foreground"
                >
                    <span class="flex items-center gap-1.5">
                        <Layers class="size-3.5 text-primary" />
                        <span
                            >Total
                            {{
                                adminData.workflow.active_dispositions_count
                            }}
                            disposisi aktif berjalan di seluruh satuan kerja</span
                        >
                    </span>
                    <Link
                        href="/back-office/organization/structure"
                        class="inline-flex items-center gap-1 font-medium text-primary hover:underline"
                    >
                        <span>Bagan Struktur Organisasi</span>
                        <ArrowUpRight class="size-3" />
                    </Link>
                </div>
            </div>

            <!-- Right: Tata Kelola Organisasi & Undangan (5 cols) -->
            <div
                class="flex flex-col justify-between rounded-2xl border bg-card p-6 shadow-xs lg:col-span-5"
            >
                <div>
                    <div class="flex items-center justify-between">
                        <div>
                            <h2
                                class="text-base font-bold tracking-tight text-foreground"
                            >
                                Master Organisasi & Akun
                            </h2>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                Struktur kelembagaan dan status registrasi akun
                                aparatur.
                            </p>
                        </div>
                        <div
                            class="rounded-xl bg-purple-50 p-2 text-purple-600 dark:bg-purple-950/50 dark:text-purple-300"
                        >
                            <Network class="size-4" />
                        </div>
                    </div>

                    <!-- 3-Box Mini Grid -->
                    <div class="mt-5 grid grid-cols-3 gap-2.5">
                        <div
                            class="rounded-xl border border-border/60 bg-muted/30 p-3 text-center"
                        >
                            <div class="text-xs text-muted-foreground">
                                Satuan Kerja
                            </div>
                            <div
                                class="mt-1 text-2xl font-bold tracking-tight text-foreground tabular-nums"
                            >
                                {{ adminData.organization.units_count }}
                            </div>
                            <div
                                class="mt-0.5 text-[10px] text-muted-foreground"
                            >
                                Unit Organisasi
                            </div>
                        </div>

                        <div
                            class="rounded-xl border border-border/60 bg-muted/30 p-3 text-center"
                        >
                            <div class="text-xs text-muted-foreground">
                                Jabatan
                            </div>
                            <div
                                class="mt-1 text-2xl font-bold tracking-tight text-foreground tabular-nums"
                            >
                                {{ adminData.organization.positions_count }}
                            </div>
                            <div
                                class="mt-0.5 text-[10px] text-muted-foreground"
                            >
                                Struktural
                            </div>
                        </div>

                        <div
                            class="rounded-xl border border-border/60 bg-muted/30 p-3 text-center"
                        >
                            <div class="text-xs text-muted-foreground">
                                Pejabat Aktif
                            </div>
                            <div
                                class="mt-1 text-2xl font-bold tracking-tight text-foreground tabular-nums"
                            >
                                {{
                                    adminData.organization
                                        .active_assignments_count
                                }}
                            </div>
                            <div
                                class="mt-0.5 text-[10px] text-muted-foreground"
                            >
                                Definitif / Plt
                            </div>
                        </div>
                    </div>

                    <!-- Pending Invitations Callout -->
                    <div
                        class="mt-5 rounded-xl border border-border/80 bg-background/80 p-4"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div
                                    class="rounded-lg bg-amber-50 p-1.5 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400"
                                >
                                    <Clock class="size-4" />
                                </div>
                                <div>
                                    <div
                                        class="text-xs font-semibold text-foreground"
                                    >
                                        Undangan Akun Belum Diaktivasi
                                    </div>
                                    <div
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Masa berlaku tautan 72 jam sejak
                                        penerbitan
                                    </div>
                                </div>
                            </div>
                            <span
                                class="text-lg font-bold text-foreground tabular-nums"
                            >
                                {{ adminData.users.pending_invitations_count }}
                            </span>
                        </div>

                        <div class="mt-3 flex items-center justify-end">
                            <Button
                                as-child
                                variant="ghost"
                                size="sm"
                                class="h-7 text-xs font-medium text-primary"
                            >
                                <Link href="/back-office/users">
                                    <span>Tinjau Undangan</span>
                                    <ChevronRight class="size-3" />
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-6 flex items-center justify-between border-t border-border/60 pt-4 text-xs"
                >
                    <Link
                        href="/back-office/organization/structure"
                        class="inline-flex items-center gap-1 font-medium text-primary hover:underline"
                    >
                        <span>Bagan Struktur Organisasi</span>
                        <ArrowUpRight class="size-3" />
                    </Link>
                    <Link
                        href="/back-office/organization/assignments"
                        class="text-muted-foreground hover:text-foreground"
                    >
                        Penugasan Pejabat
                    </Link>
                </div>
            </div>
        </section>

        <!-- Bento Grid Row 3: Jejak Audit & Mutasi Akun + Pintasan Administrator (60% / 40%) -->
        <section class="grid grid-cols-1 gap-6 lg:grid-cols-12">
            <!-- Left: Log Peristiwa Keamanan & Akun Terkini (7 cols) -->
            <div
                class="flex flex-col justify-between rounded-2xl border bg-card p-6 shadow-xs lg:col-span-7"
            >
                <div>
                    <div
                        class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between"
                    >
                        <div>
                            <div class="flex items-center gap-2">
                                <h2
                                    class="text-base font-bold tracking-tight text-foreground"
                                >
                                    Jejak Audit & Mutasi Akun Terkini
                                </h2>
                                <Badge variant="outline" class="text-[10px]">
                                    Append-Only Log
                                </Badge>
                            </div>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                Rekaman peristiwa pendaftaran, pergantian
                                status, dan keamanan akun pengguna.
                            </p>
                        </div>

                        <Button
                            as-child
                            variant="ghost"
                            size="sm"
                            class="h-8 w-fit gap-1 text-xs font-medium text-primary"
                        >
                            <Link href="/back-office/audits/privileges">
                                <span>Seluruh Log</span>
                                <ArrowUpRight class="size-3" />
                            </Link>
                        </Button>
                    </div>

                    <!-- Events stream -->
                    <div class="mt-5 divide-y divide-border/60">
                        <div
                            v-if="adminData.recent_events.length === 0"
                            class="py-8 text-center text-xs text-muted-foreground"
                        >
                            Belum ada rekaman aktivitas akun terkini yang
                            tercatat.
                        </div>

                        <div
                            v-for="event in adminData.recent_events"
                            :key="event.id"
                            class="flex items-start justify-between gap-4 py-3 first:pt-0 last:pb-0"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="inline-block rounded-md border px-2 py-0.5 text-[10px] font-semibold"
                                        :class="
                                            getEventBadgeClass(event.event_type)
                                        "
                                    >
                                        {{ event.event_type }}
                                    </span>
                                    <span
                                        class="text-xs font-medium text-foreground"
                                    >
                                        {{ event.user_name }}
                                    </span>
                                    <span
                                        v-if="event.user_email"
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        ({{ event.user_email }})
                                    </span>
                                </div>
                                <p class="text-xs text-muted-foreground">
                                    {{ event.description }}
                                </p>
                            </div>

                            <span
                                class="shrink-0 text-[11px] whitespace-nowrap text-muted-foreground"
                            >
                                {{ event.created_at_human }}
                            </span>
                        </div>
                    </div>
                </div>

                <div
                    class="mt-6 flex items-center justify-between border-t border-border/60 pt-4 text-xs text-muted-foreground"
                >
                    <span class="flex items-center gap-1.5">
                        <History class="size-3.5 text-primary" />
                        <span
                            >Integritas jejak audit dilindungi kebijakan
                            non-modifikasi</span
                        >
                    </span>
                    <Link
                        href="/back-office/audits/letters"
                        class="font-medium text-primary hover:underline"
                    >
                        Audit Surat Masuk
                    </Link>
                </div>
            </div>

            <!-- Right: Pintasan Modul Administrator (5 cols) -->
            <div
                class="flex flex-col justify-between rounded-2xl border bg-card p-6 shadow-xs lg:col-span-5"
            >
                <div>
                    <div>
                        <h2
                            class="text-base font-bold tracking-tight text-foreground"
                        >
                            Pusat Modul Administrator
                        </h2>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            Pintasan cepat navigasi tata kelola sistem dan
                            wewenang.
                        </p>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <Link
                            href="/back-office/users"
                            class="group flex flex-col justify-between rounded-xl border border-border/70 bg-background/60 p-3.5 transition-all hover:border-primary/40 hover:bg-background hover:shadow-xs"
                        >
                            <div class="flex items-center justify-between">
                                <div
                                    class="rounded-lg bg-blue-50 p-2 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400"
                                >
                                    <Users class="size-4" />
                                </div>
                                <ArrowUpRight
                                    class="size-3.5 text-muted-foreground transition-colors group-hover:text-primary"
                                />
                            </div>
                            <div class="mt-3">
                                <div
                                    class="text-xs font-semibold text-foreground transition-colors group-hover:text-primary"
                                >
                                    Kelola Pengguna
                                </div>
                                <div
                                    class="mt-0.5 text-[11px] text-muted-foreground"
                                >
                                    Manajemen akun ASN & publik
                                </div>
                            </div>
                        </Link>

                        <Link
                            href="/back-office/authorization/roles"
                            class="group flex flex-col justify-between rounded-xl border border-border/70 bg-background/60 p-3.5 transition-all hover:border-primary/40 hover:bg-background hover:shadow-xs"
                        >
                            <div class="flex items-center justify-between">
                                <div
                                    class="rounded-lg bg-purple-50 p-2 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400"
                                >
                                    <ShieldCheck class="size-4" />
                                </div>
                                <ArrowUpRight
                                    class="size-3.5 text-muted-foreground transition-colors group-hover:text-primary"
                                />
                            </div>
                            <div class="mt-3">
                                <div
                                    class="text-xs font-semibold text-foreground transition-colors group-hover:text-primary"
                                >
                                    Role & Otorisasi
                                </div>
                                <div
                                    class="mt-0.5 text-[11px] text-muted-foreground"
                                >
                                    Matriks izin dan hak akses
                                </div>
                            </div>
                        </Link>

                        <Link
                            href="/back-office/organization/structure"
                            class="group flex flex-col justify-between rounded-xl border border-border/70 bg-background/60 p-3.5 transition-all hover:border-primary/40 hover:bg-background hover:shadow-xs"
                        >
                            <div class="flex items-center justify-between">
                                <div
                                    class="rounded-lg bg-emerald-50 p-2 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400"
                                >
                                    <Network class="size-4" />
                                </div>
                                <ArrowUpRight
                                    class="size-3.5 text-muted-foreground transition-colors group-hover:text-primary"
                                />
                            </div>
                            <div class="mt-3">
                                <div
                                    class="text-xs font-semibold text-foreground transition-colors group-hover:text-primary"
                                >
                                    Struktur Dinas
                                </div>
                                <div
                                    class="mt-0.5 text-[11px] text-muted-foreground"
                                >
                                    Hierarki unit & jabatan
                                </div>
                            </div>
                        </Link>

                        <Link
                            href="/back-office/organization/assignments"
                            class="group flex flex-col justify-between rounded-xl border border-border/70 bg-background/60 p-3.5 transition-all hover:border-primary/40 hover:bg-background hover:shadow-xs"
                        >
                            <div class="flex items-center justify-between">
                                <div
                                    class="rounded-lg bg-amber-50 p-2 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400"
                                >
                                    <UserCheck class="size-4" />
                                </div>
                                <ArrowUpRight
                                    class="size-3.5 text-muted-foreground transition-colors group-hover:text-primary"
                                />
                            </div>
                            <div class="mt-3">
                                <div
                                    class="text-xs font-semibold text-foreground transition-colors group-hover:text-primary"
                                >
                                    Penugasan Pejabat
                                </div>
                                <div
                                    class="mt-0.5 text-[11px] text-muted-foreground"
                                >
                                    Penetapan pemegang posisi
                                </div>
                            </div>
                        </Link>

                        <Link
                            href="/back-office/audits/privileges"
                            class="group flex flex-col justify-between rounded-xl border border-border/70 bg-background/60 p-3.5 transition-all hover:border-primary/40 hover:bg-background hover:shadow-xs"
                        >
                            <div class="flex items-center justify-between">
                                <div
                                    class="rounded-lg bg-slate-100 p-2 text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                >
                                    <History class="size-4" />
                                </div>
                                <ArrowUpRight
                                    class="size-3.5 text-muted-foreground transition-colors group-hover:text-primary"
                                />
                            </div>
                            <div class="mt-3">
                                <div
                                    class="text-xs font-semibold text-foreground transition-colors group-hover:text-primary"
                                >
                                    Audit Wewenang
                                </div>
                                <div
                                    class="mt-0.5 text-[11px] text-muted-foreground"
                                >
                                    Mutasi privilege & role
                                </div>
                            </div>
                        </Link>

                        <Link
                            href="/back-office/audits/letters"
                            class="group flex flex-col justify-between rounded-xl border border-border/70 bg-background/60 p-3.5 transition-all hover:border-primary/40 hover:bg-background hover:shadow-xs"
                        >
                            <div class="flex items-center justify-between">
                                <div
                                    class="rounded-lg bg-cyan-50 p-2 text-cyan-600 dark:bg-cyan-950/50 dark:text-cyan-400"
                                >
                                    <FileText class="size-4" />
                                </div>
                                <ArrowUpRight
                                    class="size-3.5 text-muted-foreground transition-colors group-hover:text-primary"
                                />
                            </div>
                            <div class="mt-3">
                                <div
                                    class="text-xs font-semibold text-foreground transition-colors group-hover:text-primary"
                                >
                                    Audit Surat Masuk
                                </div>
                                <div
                                    class="mt-0.5 text-[11px] text-muted-foreground"
                                >
                                    Rekaman aktivitas intake
                                </div>
                            </div>
                        </Link>
                    </div>
                </div>

                <div
                    class="mt-6 border-t border-border/60 pt-4 text-[11px] text-muted-foreground"
                >
                    Seluruh modul terikat pada wewenang kebijakan otorisasi
                    server.
                </div>
            </div>
        </section>

        <!-- Dialog: Rincian Sesi Pengguna Online -->
        <Dialog
            :open="isOnlineDetailsOpen"
            @update:open="isOnlineDetailsOpen = $event"
        >
            <DialogContent class="sm:max-w-xl">
                <DialogHeader>
                    <div class="flex items-center gap-2">
                        <div
                            class="rounded-lg bg-emerald-50 p-2 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400"
                        >
                            <Wifi class="size-4" />
                        </div>
                        <DialogTitle class="text-lg font-bold">
                            Sesi Pengguna Sedang Online
                        </DialogTitle>
                    </div>
                    <DialogDescription
                        class="mt-1 text-xs text-muted-foreground"
                    >
                        Daftar pengguna dengan rekaman aktivitas sesi dalam 15
                        menit terakhir.
                    </DialogDescription>
                </DialogHeader>

                <div class="mt-4 max-h-[60vh] space-y-2.5 overflow-y-auto">
                    <div
                        v-if="adminData.users.recent_online.length === 0"
                        class="py-6 text-center text-xs text-muted-foreground"
                    >
                        Tidak ada aktivitas sesi pengguna terdeteksi dalam 15
                        menit terakhir.
                    </div>

                    <div
                        v-for="user in adminData.users.recent_online"
                        :key="user.id"
                        class="flex items-center justify-between rounded-xl border border-border/60 bg-muted/20 p-3"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-9 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary"
                            >
                                {{ user.name.charAt(0).toUpperCase() }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="text-xs font-semibold text-foreground"
                                        >{{ user.name }}</span
                                    >
                                    <Badge
                                        variant="outline"
                                        class="font-mono text-[9px]"
                                        :class="
                                            user.account_type === 'INTERNAL'
                                                ? 'border-indigo-300 text-indigo-700 dark:text-indigo-300'
                                                : 'border-slate-300 text-slate-700'
                                        "
                                    >
                                        {{ user.account_type }}
                                    </Badge>
                                </div>
                                <div class="text-[11px] text-muted-foreground">
                                    {{ user.email }}
                                </div>
                            </div>
                        </div>

                        <div class="text-right text-xs">
                            <div
                                class="flex items-center justify-end gap-1 font-medium text-emerald-600 dark:text-emerald-400"
                            >
                                <span
                                    class="inline-block size-1.5 rounded-full bg-emerald-500"
                                />
                                <span>{{ user.last_seen }}</span>
                            </div>
                            <div
                                v-if="user.ip_address"
                                class="font-mono text-[10px] text-muted-foreground"
                            >
                                IP: {{ user.ip_address }}
                            </div>
                        </div>
                    </div>
                </div>

                <DialogFooter class="mt-4">
                    <Button
                        type="button"
                        variant="outline"
                        class="text-xs"
                        @click="isOnlineDetailsOpen = false"
                    >
                        Tutup
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
