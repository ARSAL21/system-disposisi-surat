<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    ClipboardCheck,
    History,
    Inbox,
    Network,
    Shield,
    ShieldCheck,
    Sparkles,
    UserRoundCog,
} from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

defineProps<{
    userName: string;
}>();

const page = usePage();
const capabilities = computed(() => page.props.auth.capabilities || {});

const quickModules = computed(() => {
    const list = [];

    if (capabilities.value.can_view_intake) {
        list.push({
            title: 'Penerimaan Surat Masuk',
            description:
                'Screening dan verifikasi administrasi pengajuan surat dari publik dan manual.',
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

    return list;
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

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
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
                            variant="secondary"
                            class="bg-emerald-50 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300"
                        >
                            <Sparkles class="mr-1 size-3 text-emerald-600" />
                            Sesi Terverifikasi
                        </Badge>
                    </div>

                    <h1 class="text-3xl font-extrabold tracking-tight text-foreground sm:text-4xl">
                        Selamat datang, <span class="bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent dark:from-indigo-400 dark:to-violet-400">{{ userName }}</span>.
                    </h1>

                    <p class="leading-relaxed text-muted-foreground text-sm sm:text-base">
                        Sistem Disposisi Surat Pemerintah Kota. Akses modul kerja dan wewenang telah disinkronkan secara aman dengan kapabilitas akun dan penugasan jabatan aktif Anda.
                    </p>
                </div>

                <div class="flex flex-col gap-2 rounded-2xl border border-black/5 bg-white/80 p-4 shadow-xs backdrop-blur-md dark:border-white/10 dark:bg-slate-900/80">
                    <div class="text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                        Ringkasan Akses
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-300 font-bold text-sm">
                            {{ quickModules.length }}
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

        <!-- Quick Access Workspaces Grid -->
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold tracking-tight text-foreground">
                        Ruang Kerja & Modul Cepat
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Pilih modul untuk memulai penanganan dokumen dan administrasi
                    </p>
                </div>
            </div>

            <div
                v-if="quickModules.length > 0"
                class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3"
            >
                <Link
                    v-for="item in quickModules"
                    :key="item.href"
                    :href="item.href"
                    class="group relative flex flex-col justify-between overflow-hidden rounded-2xl border border-border/80 bg-white p-6 shadow-xs transition-all duration-200 hover:-translate-y-1 hover:border-indigo-300 hover:shadow-md dark:border-white/10 dark:bg-slate-900 dark:hover:border-indigo-800"
                >
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div
                                :class="[
                                    'flex size-12 items-center justify-center rounded-2xl transition-transform duration-200 group-hover:scale-110',
                                    item.bgLight,
                                    item.textLight,
                                ]"
                            >
                                <component :is="item.icon" class="size-6" />
                            </div>
                            <Badge variant="outline" class="text-[11px] font-medium">
                                {{ item.badge }}
                            </Badge>
                        </div>

                        <div class="space-y-1.5">
                            <h3 class="font-bold text-base text-foreground transition-colors group-hover:text-indigo-600 dark:group-hover:text-indigo-400">
                                {{ item.title }}
                            </h3>
                            <p class="text-xs leading-relaxed text-muted-foreground line-clamp-2">
                                {{ item.description }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-5 flex items-center gap-1.5 text-xs font-semibold text-indigo-600 dark:text-indigo-400">
                        <span>Buka Modul</span>
                        <ArrowRight class="size-3.5 transition-transform group-hover:translate-x-1" />
                    </div>
                </Link>
            </div>

            <!-- Empty state fallback if user has no assigned operational modules -->
            <div
                v-else
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border p-10 text-center"
            >
                <div class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-muted text-muted-foreground">
                    <Shield class="size-6" />
                </div>
                <h3 class="text-sm font-semibold text-foreground">
                    Belum Ada Modul Operasional yang Ditugaskan
                </h3>
                <p class="mt-1 max-w-sm text-xs text-muted-foreground">
                    Hubungi administrator sistem untuk menetapkan role atau penugasan jabatan sesuai tanggung jawab kerja Anda.
                </p>
            </div>
        </div>
    </div>
</template>
