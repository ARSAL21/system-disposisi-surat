<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    Building2,
    CheckCircle2,
    Clock,
    Globe,
    History,
    KeyRound,
    Lock,
    LogOut,
    Mail,
    Monitor,
    Network,
    Phone,
    Shield,
    ShieldAlert,
    ShieldCheck,
    Smartphone,
    User,
    UserCheck,
    UserX,
} from '@lucide/vue';
import gsap from 'gsap';
import { computed, onMounted, ref, watch } from 'vue';
import DeactivateUserDialog from '@/components/back-office/users/DeactivateUserDialog.vue';
import ReactivateUserDialog from '@/components/back-office/users/ReactivateUserDialog.vue';
import UserAccountEventsTimeline from '@/components/back-office/users/UserAccountEventsTimeline.vue';
import UserMetricsCard from '@/components/back-office/users/UserMetricsCard.vue';
import UserSecurityActionsModal from '@/components/back-office/users/UserSecurityActionsModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    previewAccountEvents,
    previewUsers,
} from '@/fixtures/user-management-preview';
import type {
    DeactivateUserPayload,
    UserAccountEventItem,
    UserManagementCapabilities,
    UserManagementItem,
} from '@/types/user-management';

const props = defineProps<{
    user?: UserManagementItem;
    accountEvents?: UserAccountEventItem[];
    capabilities?: UserManagementCapabilities;
    preview?: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Kelola Pengguna', href: '/back-office/previews/users' },
            { title: 'Detail Akun', href: '#' },
        ],
    },
});

const isPreview = computed(() => props.preview !== false);

// Extract ID from URL if in preview mode and prop is not populated
function resolveInitialUser(): UserManagementItem {
    if (props.user) {
        return { ...props.user };
    }

    const pathParts = window.location.pathname.split('/');
    const idParam = pathParts[pathParts.length - 1];
    const numericId = parseInt(idParam, 10);
    const found = previewUsers.find((u) => u.id === numericId);

    return found ? { ...found } : { ...previewUsers[0] };
}

const currentUser = ref<UserManagementItem>(resolveInitialUser());

watch(
    () => props.user,
    (val) => {
        if (val) {
            currentUser.value = val;
        }
    },
);

const userCapabilities = computed<UserManagementCapabilities>(() => {
    return (
        props.capabilities || {
            can_view_users: true,
            can_invite_users: true,
            can_manage_user_status: true,
            can_manage_user_security: true,
        }
    );
});

// Filter events for this user
const localEvents = ref<UserAccountEventItem[]>(
    (props.accountEvents && props.accountEvents.length > 0
        ? props.accountEvents
        : previewAccountEvents
    ).filter((evt) => evt.user_id === currentUser.value.id),
);

watch(
    () => props.accountEvents,
    (val) => {
        if (val) {
            localEvents.value = [...val];
        }
    },
);

// Dialogs state
const isDeactivateDialogOpen = ref(false);
const isReactivateDialogOpen = ref(false);
const isSecurityModalOpen = ref(false);

// Toast feedback message
const feedbackMessage = ref<string | null>(null);
const feedbackType = ref<'success' | 'warning' | 'info'>('success');

function showFeedback(
    msg: string,
    type: 'success' | 'warning' | 'info' = 'success',
) {
    feedbackMessage.value = msg;
    feedbackType.value = type;
    setTimeout(() => {
        feedbackMessage.value = null;
    }, 5000);
}

function formatDate(isoString: string | null | undefined): string {
    if (!isoString) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(isoString));
}

// Handlers for security and status mutations
function handleDeactivate(payload: DeactivateUserPayload) {
    if (!isPreview.value) {
        router.patch(
            `/back-office/users/${currentUser.value.id}/status`,
            {
                is_active: false,
                reason: payload.reason,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    isDeactivateDialogOpen.value = false;
                    showFeedback(
                        'Akun berhasil dinonaktifkan. Seluruh sesi aktif telah dicabut.',
                        'warning',
                    );
                },
                onError: (errors) => {
                    showFeedback(
                        (Object.values(errors)[0] as string) ||
                            'Gagal menonaktifkan akun.',
                        'warning',
                    );
                },
            },
        );

        return;
    }

    currentUser.value.is_active = false;
    currentUser.value.active_sessions_count = 0;
    currentUser.value.roles = [];

    // Append to local audit
    localEvents.value.unshift({
        id: Date.now(),
        user_id: currentUser.value.id,
        user_name: currentUser.value.name,
        user_email: currentUser.value.email,
        event_type: 'USER_DEACTIVATED',
        actor: {
            id: 1,
            name: 'Administrator Sistem Pemkot',
            email: 'admin@pemkot.go.id',
        },
        description: `Akun dinonaktifkan oleh administrator. Seluruh sesi aktif dicabut dan role operasional dilepas.`,
        reason: payload.reason,
        metadata: { deactivation_reason: payload.reason },
        created_at: new Date().toISOString(),
    });

    isDeactivateDialogOpen.value = false;
    showFeedback(
        'Akun berhasil dinonaktifkan. Seluruh sesi aktif telah dicabut.',
        'warning',
    );
}

function handleReactivate(payload: { reason?: string } = {}) {
    if (!isPreview.value) {
        router.patch(
            `/back-office/users/${currentUser.value.id}/status`,
            {
                is_active: true,
                reason: payload.reason,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    isReactivateDialogOpen.value = false;
                    showFeedback(
                        'Akun berhasil diaktifkan kembali.',
                        'success',
                    );
                },
                onError: (errors) => {
                    showFeedback(
                        (Object.values(errors)[0] as string) ||
                            'Gagal mengaktifkan akun.',
                        'warning',
                    );
                },
            },
        );

        return;
    }

    currentUser.value.is_active = true;

    localEvents.value.unshift({
        id: Date.now(),
        user_id: currentUser.value.id,
        user_name: currentUser.value.name,
        user_email: currentUser.value.email,
        event_type: 'USER_REACTIVATED',
        actor: {
            id: 1,
            name: 'Administrator Sistem Pemkot',
            email: 'admin@pemkot.go.id',
        },
        description:
            'Akun diaktifkan kembali. Penugasan jabatan & role tidak dipulihkan otomatis demi prinsip hak istimewa terkecil.',
        reason: payload.reason || null,
        metadata: {},
        created_at: new Date().toISOString(),
    });

    isReactivateDialogOpen.value = false;
    showFeedback('Akun berhasil diaktifkan kembali.', 'success');
}

function handleRevokeSessions() {
    if (!isPreview.value) {
        router.post(
            `/back-office/users/${currentUser.value.id}/sessions/revoke`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    showFeedback(
                        'Seluruh sesi aktif pengguna telah berhasil dicabut.',
                        'success',
                    );
                },
                onError: (errors) => {
                    showFeedback(
                        (Object.values(errors)[0] as string) ||
                            'Gagal mencabut sesi pengguna.',
                        'warning',
                    );
                },
            },
        );

        return;
    }

    currentUser.value.active_sessions_count = 0;

    localEvents.value.unshift({
        id: Date.now(),
        user_id: currentUser.value.id,
        user_name: currentUser.value.name,
        user_email: currentUser.value.email,
        event_type: 'SESSIONS_REVOKED',
        actor: {
            id: 1,
            name: 'Administrator Sistem Pemkot',
            email: 'admin@pemkot.go.id',
        },
        description:
            'Seluruh sesi login aktif dicabut paksa oleh administrator.',
        reason: null,
        metadata: {},
        created_at: new Date().toISOString(),
    });

    showFeedback(
        'Seluruh sesi aktif pengguna telah berhasil dicabut.',
        'success',
    );
}

function handleSendPasswordReset() {
    if (!isPreview.value) {
        router.post(
            `/back-office/users/${currentUser.value.id}/password-reset`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    showFeedback(
                        `Tautan reset kata sandi telah dikirimkan ke email ${currentUser.value.email}.`,
                        'info',
                    );
                },
                onError: (errors) => {
                    showFeedback(
                        (Object.values(errors)[0] as string) ||
                            'Gagal mengirim tautan reset.',
                        'warning',
                    );
                },
            },
        );

        return;
    }

    localEvents.value.unshift({
        id: Date.now(),
        user_id: currentUser.value.id,
        user_name: currentUser.value.name,
        user_email: currentUser.value.email,
        event_type: 'PASSWORD_RESET_LINK_SENT',
        actor: {
            id: 1,
            name: 'Administrator Sistem Pemkot',
            email: 'admin@pemkot.go.id',
        },
        description: `Tautan pengaturan ulang kata sandi dikirimkan ke alamat email ${currentUser.value.email}.`,
        reason: null,
        metadata: {},
        created_at: new Date().toISOString(),
    });

    showFeedback(
        `Tautan reset kata sandi telah dikirimkan ke email ${currentUser.value.email}.`,
        'info',
    );
}

function handleResetMfa() {
    if (!isPreview.value) {
        router.post(
            `/back-office/users/${currentUser.value.id}/two-factor/reset`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    isSecurityModalOpen.value = false;
                    showFeedback(
                        'Autentikasi dua faktor (MFA) pengguna telah berhasil direset.',
                        'warning',
                    );
                },
                onError: (errors) => {
                    showFeedback(
                        (Object.values(errors)[0] as string) ||
                            'Gagal mereset MFA.',
                        'warning',
                    );
                },
            },
        );

        return;
    }

    currentUser.value.two_factor_enabled = false;

    localEvents.value.unshift({
        id: Date.now(),
        user_id: currentUser.value.id,
        user_name: currentUser.value.name,
        user_email: currentUser.value.email,
        event_type: 'MFA_RESET',
        actor: {
            id: 1,
            name: 'Administrator Sistem Pemkot',
            email: 'admin@pemkot.go.id',
        },
        description:
            'Autentikasi Dua Faktor (MFA/TOTP) dinonaktifkan atas permintaan pemulihan keamanan.',
        reason: null,
        metadata: {},
        created_at: new Date().toISOString(),
    });

    isSecurityModalOpen.value = false;
    showFeedback(
        'Autentikasi dua faktor (MFA) pengguna telah berhasil direset.',
        'warning',
    );
}

onMounted(() => {
    gsap.from('.bento-item', {
        opacity: 0,
        y: 16,
        duration: 0.45,
        stagger: 0.08,
        ease: 'power2.out',
    });
});
</script>

<template>
    <Head :title="`Detail Pengguna - ${currentUser.name}`" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <!-- Top Banner: Mode Simulasi Preview -->
        <div
            v-if="isPreview"
            class="flex items-center justify-between gap-3 rounded-2xl border border-indigo-500/20 bg-indigo-500/10 p-3.5 text-xs text-indigo-950 dark:text-indigo-200"
        >
            <div class="flex items-center gap-2">
                <ShieldCheck
                    class="size-4 shrink-0 text-indigo-600 dark:text-indigo-400"
                />
                <span>
                    <strong>Mode Simulasi Visual (M9):</strong> Seluruh tindakan
                    keamanan, pencabutan sesi, dan aktivasi akun beroperasi
                    secara interaktif tanpa mutasi database permanen.
                </span>
            </div>
            <Badge
                variant="outline"
                class="shrink-0 border-indigo-500/30 font-mono text-[10px] text-indigo-600 dark:text-indigo-300"
            >
                M9_PREVIEW
            </Badge>
        </div>

        <!-- Feedback Alert Toast -->
        <div
            v-if="feedbackMessage"
            class="flex items-center justify-between gap-3 rounded-2xl border p-4 text-xs transition-all"
            :class="{
                'border-emerald-500/30 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300':
                    feedbackType === 'success',
                'border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-300':
                    feedbackType === 'warning',
                'border-blue-500/30 bg-blue-500/10 text-blue-800 dark:text-blue-300':
                    feedbackType === 'info',
            }"
        >
            <div class="flex items-center gap-2.5">
                <CheckCircle2
                    v-if="feedbackType === 'success'"
                    class="size-4 shrink-0 text-emerald-600"
                />
                <AlertCircle v-else class="size-4 shrink-0" />
                <span class="font-medium">{{ feedbackMessage }}</span>
            </div>
            <button
                class="cursor-pointer text-xs underline hover:opacity-80"
                @click="feedbackMessage = null"
            >
                Tutup
            </button>
        </div>

        <!-- Navigation Header & Quick Actions -->
        <div
            class="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center"
        >
            <div class="flex items-center gap-3">
                <Link
                    href="/back-office/previews/users"
                    class="rounded-xl border bg-card p-2 text-muted-foreground transition-colors hover:text-foreground"
                    title="Kembali ke Daftar Pengguna"
                >
                    <ArrowLeft class="size-4" />
                </Link>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1
                            class="text-xl font-bold tracking-tight text-foreground sm:text-2xl"
                        >
                            {{ currentUser.name }}
                        </h1>
                        <span class="font-mono text-xs text-muted-foreground"
                            >#UID-{{ currentUser.id }}</span
                        >
                    </div>
                    <div
                        class="mt-0.5 flex flex-wrap items-center gap-3 text-xs text-muted-foreground sm:text-sm"
                    >
                        <span class="flex items-center gap-1.5">
                            <Mail class="size-3.5" />
                            <span>{{ currentUser.email }}</span>
                        </span>
                        <span
                            v-if="currentUser.phone_number"
                            class="flex items-center gap-1.5"
                        >
                            <Phone
                                class="size-3.5 text-emerald-600 dark:text-emerald-400"
                            />
                            <span>{{ currentUser.phone_number }}</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Header Action Buttons -->
            <div class="flex items-center gap-2 self-stretch sm:self-auto">
                <Button
                    v-if="userCapabilities.can_manage_user_security"
                    variant="outline"
                    class="flex flex-1 cursor-pointer items-center gap-1.5 rounded-xl text-xs sm:flex-none"
                    @click="isSecurityModalOpen = true"
                >
                    <Shield class="size-3.5 text-primary" />
                    <span>Tindakan Keamanan</span>
                </Button>

                <template v-if="userCapabilities.can_manage_user_status">
                    <Button
                        v-if="currentUser.is_active"
                        variant="destructive"
                        class="flex flex-1 cursor-pointer items-center gap-1.5 rounded-xl text-xs sm:flex-none"
                        @click="isDeactivateDialogOpen = true"
                    >
                        <UserX class="size-3.5" />
                        <span>Nonaktifkan Akun</span>
                    </Button>
                    <Button
                        v-else
                        class="flex flex-1 cursor-pointer items-center gap-1.5 rounded-xl bg-emerald-600 text-xs text-white hover:bg-emerald-500 sm:flex-none"
                        @click="isReactivateDialogOpen = true"
                    >
                        <UserCheck class="size-3.5" />
                        <span>Aktifkan Kembali</span>
                    </Button>
                </template>
            </div>
        </div>

        <!-- Badges Bar -->
        <div class="flex flex-wrap items-center gap-2">
            <!-- Account Type Badge -->
            <span
                class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold"
                :class="
                    currentUser.account_type === 'INTERNAL'
                        ? 'border border-indigo-500/20 bg-indigo-500/10 text-indigo-700 dark:text-indigo-400'
                        : 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                "
            >
                <component
                    :is="
                        currentUser.account_type === 'INTERNAL'
                            ? Building2
                            : Globe
                    "
                    class="size-3.5"
                />
                {{
                    currentUser.account_type === 'INTERNAL'
                        ? 'Pengguna Internal (Pemkot)'
                        : 'Pengguna Publik / Instansi Luar'
                }}
            </span>

            <!-- Status Badge -->
            <span
                class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold"
                :class="
                    currentUser.is_active
                        ? 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                        : 'border border-destructive/20 bg-destructive/10 text-destructive'
                "
            >
                <span
                    class="size-1.5 rounded-full"
                    :class="
                        currentUser.is_active
                            ? 'bg-emerald-500'
                            : 'bg-destructive'
                    "
                ></span>
                {{
                    currentUser.is_active
                        ? 'Status Akun: Aktif'
                        : 'Status Akun: Dinonaktifkan'
                }}
            </span>

            <!-- Email Verification Badge -->
            <span
                class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-medium"
                :class="
                    currentUser.email_verified_at
                        ? 'border bg-muted text-muted-foreground'
                        : 'border border-amber-500/20 bg-amber-500/10 text-amber-700 dark:text-amber-400'
                "
            >
                <CheckCircle2
                    v-if="currentUser.email_verified_at"
                    class="size-3 text-emerald-500"
                />
                <AlertCircle v-else class="size-3 text-amber-500" />
                {{
                    currentUser.email_verified_at
                        ? 'Email Terverifikasi'
                        : 'Email Belum Diverifikasi'
                }}
            </span>

            <!-- 2FA Badge -->
            <span
                class="flex items-center gap-1.5 rounded-lg border px-2.5 py-1 text-xs font-medium"
                :class="
                    currentUser.two_factor_enabled
                        ? 'border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                        : 'bg-muted text-muted-foreground'
                "
            >
                <Smartphone class="size-3" />
                {{
                    currentUser.two_factor_enabled
                        ? 'MFA/2FA Aktif'
                        : 'MFA Belum Aktif'
                }}
            </span>
        </div>

        <!-- Bento Grid Layout -->
        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            <!-- Left Column (Span 7) -->
            <div class="space-y-6 lg:col-span-7">
                <!-- Bento Card 1: Profil & Identitas Akun -->
                <div
                    class="bento-item rounded-2xl border bg-card p-5 shadow-xs sm:p-6"
                >
                    <div class="mb-5 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="rounded-xl bg-primary/10 p-2 text-primary"
                            >
                                <User class="size-5" />
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-foreground">
                                    Identitas & Akses Akun
                                </h3>
                                <p class="text-xs text-muted-foreground">
                                    Informasi administratif akun pengguna
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="grid grid-cols-1 gap-3.5 text-xs sm:grid-cols-2"
                    >
                        <div class="rounded-xl border bg-muted/20 p-3.5">
                            <p class="mb-1 font-medium text-muted-foreground">
                                Nama Lengkap
                            </p>
                            <p class="text-sm font-semibold text-foreground">
                                {{ currentUser.name }}
                            </p>
                        </div>
                        <div class="rounded-xl border bg-muted/20 p-3.5">
                            <p class="mb-1 font-medium text-muted-foreground">
                                Alamat Email Resmi
                            </p>
                            <p class="text-sm font-semibold text-foreground">
                                {{ currentUser.email }}
                            </p>
                        </div>
                        <div class="rounded-xl border bg-muted/20 p-3.5">
                            <p class="mb-1 font-medium text-muted-foreground">
                                Nomor Telepon / WhatsApp
                            </p>
                            <div class="flex items-center justify-between">
                                <p
                                    class="text-sm font-semibold text-foreground"
                                >
                                    {{ currentUser.phone_number || '-' }}
                                </p>
                                <a
                                    v-if="currentUser.phone_number"
                                    :href="`https://wa.me/${currentUser.phone_number.replace(/[^0-9]/g, '')}`"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="flex items-center gap-1 text-[11px] font-medium text-emerald-600 hover:underline dark:text-emerald-400"
                                >
                                    <Phone class="size-3" />
                                    <span>Hubungi</span>
                                </a>
                            </div>
                        </div>
                        <div class="rounded-xl border bg-muted/20 p-3.5">
                            <p class="mb-1 font-medium text-muted-foreground">
                                Tipe Akun Pengguna
                            </p>
                            <p class="text-sm font-semibold text-foreground">
                                {{
                                    currentUser.account_type === 'INTERNAL'
                                        ? 'Pegawai Internal (Pemkot)'
                                        : 'Pengguna Publik / Eksternal'
                                }}
                            </p>
                        </div>
                        <div class="rounded-xl border bg-muted/20 p-3.5">
                            <p class="mb-1 font-medium text-muted-foreground">
                                Waktu Terdaftar
                            </p>
                            <p class="font-medium text-foreground tabular-nums">
                                {{ formatDate(currentUser.created_at) }}
                            </p>
                        </div>
                        <div class="rounded-xl border bg-muted/20 p-3.5">
                            <p class="mb-1 font-medium text-muted-foreground">
                                Pembaruan Terakhir
                            </p>
                            <p class="font-medium text-foreground tabular-nums">
                                {{ formatDate(currentUser.updated_at) }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="mt-4 flex items-center justify-between border-t pt-4 text-xs text-muted-foreground"
                    >
                        <div class="flex items-center gap-1.5">
                            <ShieldCheck class="size-3.5 text-emerald-500" />
                            <span
                                >Kredensial dikelola secara mandiri oleh
                                pengguna</span
                            >
                        </div>
                        <span class="font-mono text-[11px]"
                            >Bebas Pengetahuan Sandi Admin</span
                        >
                    </div>
                </div>

                <!-- Bento Card 2: Struktur & Penugasan Jabatan (Khusus Internal) -->
                <div
                    v-if="currentUser.account_type === 'INTERNAL'"
                    class="bento-item rounded-2xl border bg-card p-5 shadow-xs sm:p-6"
                >
                    <div class="mb-5 flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="rounded-xl bg-indigo-500/10 p-2 text-indigo-600"
                            >
                                <Building2 class="size-5" />
                            </span>
                            <div>
                                <h3 class="text-sm font-bold text-foreground">
                                    Penugasan Jabatan & Struktur
                                </h3>
                                <p class="text-xs text-muted-foreground">
                                    Keterikatan kedinasan pada bagan organisasi
                                    Pemkot
                                </p>
                            </div>
                        </div>
                        <Link
                            href="/back-office/organization/assignments"
                            class="flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                        >
                            <span>Kelola Penugasan</span>
                            <span>&rarr;</span>
                        </Link>
                    </div>

                    <!-- Jabatan Aktif Saat Ini -->
                    <div class="space-y-4">
                        <div
                            v-if="currentUser.active_position"
                            class="rounded-xl border border-indigo-500/20 bg-indigo-500/5 p-4"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <span
                                        class="rounded-md bg-indigo-500/15 px-2 py-0.5 text-[10px] font-bold tracking-wider text-indigo-700 uppercase dark:text-indigo-300"
                                    >
                                        Jabatan Aktif Saat Ini
                                    </span>
                                    <h4
                                        class="text-base font-bold text-foreground"
                                    >
                                        {{
                                            currentUser.active_position
                                                .position_name
                                        }}
                                    </h4>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            currentUser.active_position
                                                .unit_name
                                        }}
                                        ({{
                                            currentUser.active_position
                                                .unit_code
                                        }})
                                    </p>
                                </div>
                                <Badge
                                    variant="outline"
                                    class="border-indigo-500/30 font-mono text-xs text-indigo-600 dark:text-indigo-400"
                                >
                                    {{ currentUser.active_position.level_code }}
                                </Badge>
                            </div>
                            <div
                                class="mt-3 flex items-center justify-between border-t border-indigo-500/10 pt-3 text-xs text-muted-foreground"
                            >
                                <span>Terhitung Mulai Tanggal (TMT):</span>
                                <span
                                    class="font-medium text-foreground tabular-nums"
                                    >{{
                                        formatDate(
                                            currentUser.active_position
                                                .started_at,
                                        )
                                    }}</span
                                >
                            </div>
                        </div>

                        <div
                            v-else
                            class="space-y-1 rounded-xl border border-dashed bg-muted/20 p-4 text-center"
                        >
                            <Building2
                                class="mx-auto size-6 text-muted-foreground/50"
                            />
                            <p class="text-xs font-medium text-foreground">
                                Tidak Memiliki Jabatan Aktif
                            </p>
                            <p
                                class="mx-auto max-w-sm text-[11px] text-muted-foreground"
                            >
                                Akun ini tidak sedang memegang posisi
                                struktural. Penugasan jabatan dapat ditambahkan
                                melalui menu Struktur Organisasi.
                            </p>
                        </div>

                        <!-- Riwayat Penugasan Sebelumnya -->
                        <div class="pt-2">
                            <h4
                                class="mb-2.5 flex items-center gap-1.5 text-xs font-semibold tracking-wider text-foreground uppercase"
                            >
                                <History
                                    class="size-3.5 text-muted-foreground"
                                />
                                <span>Riwayat Penugasan Sebelumnya</span>
                            </h4>

                            <div
                                v-if="
                                    currentUser.historical_positions.length > 0
                                "
                                class="space-y-2"
                            >
                                <div
                                    v-for="hist in currentUser.historical_positions"
                                    :key="hist.id"
                                    class="flex items-center justify-between rounded-xl border bg-muted/20 p-3 text-xs"
                                >
                                    <div>
                                        <p
                                            class="font-semibold text-foreground"
                                        >
                                            {{ hist.position_name }}
                                        </p>
                                        <p
                                            class="text-[11px] text-muted-foreground"
                                        >
                                            {{ hist.unit_name }}
                                        </p>
                                    </div>
                                    <div
                                        class="text-right text-[11px] text-muted-foreground tabular-nums"
                                    >
                                        <p>{{ formatDate(hist.started_at) }}</p>
                                        <p>
                                            s/d
                                            {{
                                                hist.ended_at
                                                    ? formatDate(hist.ended_at)
                                                    : 'Selesai'
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <p
                                v-else
                                class="text-xs text-muted-foreground italic"
                            >
                                Belum ada riwayat jabatan dinas sebelumnya
                                tercatat.
                            </p>
                        </div>

                        <!-- Roles Operasional -->
                        <div class="pt-2">
                            <div class="mb-2 flex items-center justify-between">
                                <h4
                                    class="flex items-center gap-1.5 text-xs font-semibold tracking-wider text-foreground uppercase"
                                >
                                    <Shield
                                        class="size-3.5 text-muted-foreground"
                                    />
                                    <span>Role Hak Akses Operasional</span>
                                </h4>
                                <Link
                                    href="/back-office/authorization"
                                    class="text-xs font-medium text-primary hover:underline"
                                >
                                    Manage Role &rarr;
                                </Link>
                            </div>
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span
                                    v-for="role in currentUser.roles"
                                    :key="role"
                                    class="rounded-lg border border-primary/20 bg-primary/10 px-2.5 py-1 font-mono text-xs font-medium text-primary"
                                >
                                    {{ role }}
                                </span>
                                <span
                                    v-if="currentUser.roles.length === 0"
                                    class="text-xs text-muted-foreground italic"
                                >
                                    Tidak ada role operasional khusus yang
                                    ditetapkan.
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Bento Card 3: Timeline Audit Keamanan Akun -->
                <div class="bento-item">
                    <UserAccountEventsTimeline :events="localEvents" />
                </div>
            </div>

            <!-- Right Column (Span 5) -->
            <div class="space-y-6 lg:col-span-5">
                <!-- Bento Card 4: Keamanan & Sesi Akses -->
                <div
                    class="bento-item space-y-5 rounded-2xl border bg-card p-5 shadow-xs sm:p-6"
                >
                    <div class="flex items-center gap-2.5">
                        <span
                            class="rounded-xl bg-amber-500/10 p-2 text-amber-600"
                        >
                            <Lock class="size-5" />
                        </span>
                        <div>
                            <h3 class="text-sm font-bold text-foreground">
                                Keamanan Sesi & Pemulihan
                            </h3>
                            <p class="text-xs text-muted-foreground">
                                Pengendalian akses real-time & multi-factor
                            </p>
                        </div>
                    </div>

                    <!-- Active Sessions Box -->
                    <div class="space-y-3 rounded-xl border bg-muted/20 p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <LogOut class="size-4 text-muted-foreground" />
                                <span
                                    class="text-xs font-medium text-foreground"
                                    >Sesi Login Aktif</span
                                >
                            </div>
                            <Badge
                                variant="outline"
                                class="font-mono text-xs tabular-nums"
                            >
                                {{
                                    currentUser.active_sessions_count
                                }}
                                Perangkat
                            </Badge>
                        </div>
                        <p class="text-[11px] text-muted-foreground">
                            Cabut seluruh sesi login untuk memaksa pengguna
                            keluar dari browser dan perangkat seluler yang
                            terhubung.
                        </p>
                        <Button
                            variant="outline"
                            size="sm"
                            class="w-full cursor-pointer rounded-xl text-xs"
                            :disabled="currentUser.active_sessions_count === 0"
                            @click="handleRevokeSessions"
                        >
                            <LogOut class="mr-1.5 size-3.5" />
                            <span>Cabut Seluruh Sesi Sekarang</span>
                        </Button>
                    </div>

                    <!-- Password Recovery Box -->
                    <div class="space-y-3 rounded-xl border bg-muted/20 p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <KeyRound class="size-4 text-indigo-500" />
                                <span
                                    class="text-xs font-medium text-foreground"
                                    >Pengaturan Sandi Pengguna</span
                                >
                            </div>
                        </div>
                        <p class="text-[11px] text-muted-foreground">
                            Kirim tautan pemulihan kata sandi resmi ke email
                            pengguna. Super-admin tidak memiliki akses untuk
                            menentukan password secara manual.
                        </p>
                        <Button
                            variant="outline"
                            size="sm"
                            class="w-full cursor-pointer rounded-xl text-xs"
                            @click="handleSendPasswordReset"
                        >
                            <Mail class="mr-1.5 size-3.5 text-indigo-500" />
                            <span>Kirim Tautan Reset Sandi</span>
                        </Button>
                    </div>

                    <!-- Two-Factor Authentication Box -->
                    <div class="space-y-3 rounded-xl border bg-muted/20 p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Smartphone
                                    class="size-4"
                                    :class="
                                        currentUser.two_factor_enabled
                                            ? 'text-emerald-500'
                                            : 'text-muted-foreground'
                                    "
                                />
                                <span
                                    class="text-xs font-medium text-foreground"
                                    >Autentikasi Dua Faktor (MFA)</span
                                >
                            </div>
                            <span
                                class="rounded-md px-2 py-0.5 text-[11px] font-semibold"
                                :class="
                                    currentUser.two_factor_enabled
                                        ? 'bg-emerald-500/10 text-emerald-600'
                                        : 'bg-muted text-muted-foreground'
                                "
                            >
                                {{
                                    currentUser.two_factor_enabled
                                        ? 'Aktif'
                                        : 'Tidak Aktif'
                                }}
                            </span>
                        </div>
                        <p class="text-[11px] text-muted-foreground">
                            Reset MFA hanya dilakukan apabila pengguna
                            kehilangan akses ke perangkat autentikator (TOTP).
                            Tindakan ini memerlukan verifikasi kata sandi
                            super-admin.
                        </p>
                        <Button
                            v-if="currentUser.two_factor_enabled"
                            variant="outline"
                            size="sm"
                            class="w-full cursor-pointer rounded-xl text-xs text-destructive hover:text-destructive"
                            @click="isSecurityModalOpen = true"
                        >
                            <ShieldAlert class="mr-1.5 size-3.5" />
                            <span>Buka Prosedur Reset MFA</span>
                        </Button>
                    </div>

                    <!-- Telemetri Login Terakhir (Last Login Telemetry) -->
                    <div class="space-y-3 rounded-xl border bg-muted/20 p-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Clock class="size-4 text-primary" />
                                <span
                                    class="text-xs font-medium text-foreground"
                                    >Telemetri Login Terakhir</span
                                >
                            </div>
                            <span
                                class="rounded-md px-2 py-0.5 font-mono text-[10px] font-semibold"
                                :class="
                                    currentUser.last_login_ip?.includes(
                                        'LAN',
                                    ) ||
                                    currentUser.last_login_ip?.includes(
                                        'Intranet',
                                    )
                                        ? 'border border-indigo-500/20 bg-indigo-500/10 text-indigo-700 dark:text-indigo-400'
                                        : 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                                "
                            >
                                {{
                                    currentUser.last_login_ip?.includes(
                                        'LAN',
                                    ) ||
                                    currentUser.last_login_ip?.includes(
                                        'Intranet',
                                    )
                                        ? 'Jaringan Internal'
                                        : 'Jaringan Luar'
                                }}
                            </span>
                        </div>

                        <div class="space-y-2.5 text-xs">
                            <div
                                class="flex items-center justify-between text-muted-foreground"
                            >
                                <span>Waktu Login:</span>
                                <span
                                    class="font-medium text-foreground tabular-nums"
                                >
                                    {{
                                        currentUser.last_login_at
                                            ? formatDate(
                                                  currentUser.last_login_at,
                                              )
                                            : 'Belum pernah login'
                                    }}
                                </span>
                            </div>
                            <div
                                class="flex items-center justify-between text-muted-foreground"
                            >
                                <span class="flex items-center gap-1.5">
                                    <Network
                                        class="size-3.5 text-muted-foreground"
                                    />
                                    <span>Alamat IP:</span>
                                </span>
                                <span
                                    class="font-mono text-[11px] font-semibold text-foreground"
                                >
                                    {{ currentUser.last_login_ip || '-' }}
                                </span>
                            </div>
                            <div
                                class="flex items-center justify-between text-muted-foreground"
                            >
                                <span class="flex items-center gap-1.5">
                                    <Monitor
                                        class="size-3.5 text-muted-foreground"
                                    />
                                    <span>Perangkat & Peramban:</span>
                                </span>
                                <span
                                    class="text-[11px] font-medium text-foreground"
                                >
                                    {{ currentUser.last_login_device || '-' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Guardrail Notice for Deactivation -->
                    <div
                        v-if="
                            !currentUser.can_deactivate && currentUser.is_active
                        "
                        class="space-y-1 rounded-xl border border-amber-500/20 bg-amber-500/10 p-3 text-xs text-amber-900 dark:text-amber-200"
                    >
                        <div class="flex items-center gap-1.5 font-semibold">
                            <AlertCircle
                                class="size-3.5 shrink-0 text-amber-600"
                            />
                            <span>Penonaktifan Akun Dibatasi</span>
                        </div>
                        <p class="text-[11px] leading-relaxed">
                            {{ currentUser.deactivation_block_reason }}
                        </p>
                    </div>
                </div>

                <!-- Bento Card 5: Metrik Persuratan Aman (Zero-Leakage Invariant) -->
                <div class="bento-item space-y-3">
                    <UserMetricsCard
                        :metrics="currentUser.submission_metrics"
                        :account-type="currentUser.account_type"
                    />

                    <!-- Zero-Leakage Policy Guarantee Card -->
                    <div
                        class="space-y-2 rounded-2xl border border-emerald-500/20 bg-emerald-500/5 p-4 text-xs"
                    >
                        <div
                            class="flex items-center gap-2 font-bold text-emerald-800 dark:text-emerald-300"
                        >
                            <ShieldCheck
                                class="size-4 shrink-0 text-emerald-600"
                            />
                            <span>Kebijakan Zero-Leakage Data Persuratan</span>
                        </div>
                        <p
                            class="text-[11px] leading-relaxed text-muted-foreground"
                        >
                            Super-admin hanya berhak memantau metrik agregat
                            kuantitatif untuk kebutuhan kapasitas teknis. Judul
                            surat, ringkasan perihal, berkas PDF dokumen, dan
                            catatan disposisi bersifat tertutup rapat dan hanya
                            dapat diakses oleh pejabat penerima disposisi
                            berwenang.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals & Dialogs -->
    <DeactivateUserDialog
        v-if="currentUser"
        :open="isDeactivateDialogOpen"
        :user="currentUser"
        @update:open="isDeactivateDialogOpen = $event"
        @confirm="handleDeactivate"
    />

    <ReactivateUserDialog
        v-if="currentUser"
        :open="isReactivateDialogOpen"
        :user="currentUser"
        @update:open="isReactivateDialogOpen = $event"
        @confirm="handleReactivate"
    />

    <UserSecurityActionsModal
        v-if="currentUser"
        :open="isSecurityModalOpen"
        :user="currentUser"
        @update:open="isSecurityModalOpen = $event"
        @revoke-sessions="handleRevokeSessions"
        @send-password-reset="handleSendPasswordReset"
        @reset-mfa="handleResetMfa"
    />
</template>
