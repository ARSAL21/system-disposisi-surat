<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Activity,
    AlertCircle,
    Building2,
    CheckCircle2,
    Clock,
    Eye,
    Globe,
    Mail,
    Plus,
    RefreshCw,
    Search,
    Shield,
    Smartphone,
    UserCheck,
    Users,
    UserX,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import DeactivateUserDialog from '@/components/back-office/users/DeactivateUserDialog.vue';
import InviteUserDialog from '@/components/back-office/users/InviteUserDialog.vue';
import ReactivateUserDialog from '@/components/back-office/users/ReactivateUserDialog.vue';
import UserSecurityActionsModal from '@/components/back-office/users/UserSecurityActionsModal.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    previewAccountEvents,
    previewInvitations,
    previewUnits,
    previewUsers,
} from '@/fixtures/user-management-preview';
import type {
    DeactivateUserPayload,
    InviteUserPayload,
    UserAccountEventItem,
    UserInvitationItem,
    UserManagementCapabilities,
    UserManagementItem,
} from '@/types/user-management';

const props = defineProps<{
    users?: UserManagementItem[];
    invitations?: UserInvitationItem[];
    accountEvents?: UserAccountEventItem[];
    capabilities?: UserManagementCapabilities;
    preview?: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            { title: 'Kelola Pengguna', href: '#' },
        ],
    },
});

const isPreview = computed(() => props.preview !== false);

// Active Tab
type TabType = 'all' | 'internal' | 'public' | 'invitations' | 'events';
const activeTab = ref<TabType>('all');

// Reactive state for local simulated mutations
const localUsers = ref<UserManagementItem[]>([
    ...(props.users ?? previewUsers),
]);
const localInvitations = ref<UserInvitationItem[]>([
    ...(props.invitations ?? previewInvitations),
]);
const localEvents = ref<UserAccountEventItem[]>([
    ...(props.accountEvents ?? previewAccountEvents),
]);

watch(
    () => props.users,
    (val) => {
        if (val) {
            localUsers.value = [...val];
        }
    },
);
watch(
    () => props.invitations,
    (val) => {
        if (val) {
            localInvitations.value = [...val];
        }
    },
);
watch(
    () => props.accountEvents,
    (val) => {
        if (val) {
            localEvents.value = [...val];
        }
    },
);

// Filters
const searchQuery = ref('');
const statusFilter = ref<'ALL' | 'ACTIVE' | 'DEACTIVATED'>('ALL');
const verifiedFilter = ref<'ALL' | 'VERIFIED' | 'UNVERIFIED'>('ALL');
const roleFilter = ref('ALL');
const unitFilter = ref('ALL');

// Modals state
const inviteDialogOpen = ref(false);
const deactivateDialogOpen = ref(false);
const reactivateDialogOpen = ref(false);
const securityModalOpen = ref(false);
const selectedUser = ref<UserManagementItem | null>(null);

const processingAction = ref<string | null>(null);
const feedbackNotice = ref<{
    type: 'success' | 'error';
    message: string;
} | null>(null);

function showFeedback(type: 'success' | 'error', message: string): void {
    feedbackNotice.value = { type, message };
    setTimeout(() => {
        if (feedbackNotice.value?.message === message) {
            feedbackNotice.value = null;
        }
    }, 5000);
}

// Filtered Users
const filteredUsers = computed(() => {
    return localUsers.value.filter((u) => {
        // Tab filtering
        if (activeTab.value === 'internal' && u.account_type !== 'INTERNAL') {
            return false;
        }

        if (activeTab.value === 'public' && u.account_type !== 'PUBLIC') {
            return false;
        }

        // Search
        if (searchQuery.value.trim()) {
            const q = searchQuery.value.trim().toLowerCase();
            const matchesName = u.name.toLowerCase().includes(q);
            const matchesEmail = u.email.toLowerCase().includes(q);
            const matchesPosition = u.active_position?.position_name
                .toLowerCase()
                .includes(q);
            const matchesUnit = u.active_position?.unit_name
                .toLowerCase()
                .includes(q);
            const matchesPhone = u.phone_number?.toLowerCase().includes(q);

            if (
                !matchesName &&
                !matchesEmail &&
                !matchesPosition &&
                !matchesUnit &&
                !matchesPhone
            ) {
                return false;
            }
        }

        // Status
        if (statusFilter.value === 'ACTIVE' && !u.is_active) {
            return false;
        }

        if (statusFilter.value === 'DEACTIVATED' && u.is_active) {
            return false;
        }

        // Verified
        if (verifiedFilter.value === 'VERIFIED' && !u.email_verified_at) {
            return false;
        }

        if (verifiedFilter.value === 'UNVERIFIED' && u.email_verified_at) {
            return false;
        }

        // Role
        if (roleFilter.value !== 'ALL' && !u.roles.includes(roleFilter.value)) {
            return false;
        }

        // Unit
        if (
            unitFilter.value !== 'ALL' &&
            u.active_position?.unit_code !== unitFilter.value
        ) {
            return false;
        }

        return true;
    });
});

// Filtered Invitations
const filteredInvitations = computed(() => {
    return localInvitations.value.filter((inv) => {
        if (searchQuery.value.trim()) {
            const q = searchQuery.value.trim().toLowerCase();
            const matchesName = inv.name.toLowerCase().includes(q);
            const matchesEmail = inv.email.toLowerCase().includes(q);

            if (!matchesName && !matchesEmail) {
                return false;
            }
        }

        return true;
    });
});

// Statistics
const stats = computed(() => {
    const total = localUsers.value.length;
    const internalActive = localUsers.value.filter(
        (u) => u.account_type === 'INTERNAL' && u.is_active,
    ).length;
    const publicActive = localUsers.value.filter(
        (u) => u.account_type === 'PUBLIC' && u.is_active,
    ).length;
    const pendingInvites = localInvitations.value.filter(
        (inv) => inv.status === 'PENDING',
    ).length;

    return { total, internalActive, publicActive, pendingInvites };
});

// Actions handling
function openDeactivateDialog(user: UserManagementItem): void {
    selectedUser.value = user;
    deactivateDialogOpen.value = true;
}

function openReactivateDialog(user: UserManagementItem): void {
    selectedUser.value = user;
    reactivateDialogOpen.value = true;
}

function openSecurityModal(user: UserManagementItem): void {
    selectedUser.value = user;
    securityModalOpen.value = true;
}

function handleInviteSubmit(payload: InviteUserPayload): void {
    if (!isPreview.value) {
        processingAction.value = 'invite';
        router.post(
            '/back-office/users/invitations',
            {
                name: payload.name,
                email: payload.email,
                phone_number: payload.phone_number,
                account_type: payload.account_type,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    inviteDialogOpen.value = false;
                    processingAction.value = null;
                    showFeedback(
                        'success',
                        `Tautan aktivasi untuk ${payload.email} berhasil dibuat.`,
                    );
                },
                onError: (errors) => {
                    processingAction.value = null;
                    showFeedback(
                        'error',
                        (Object.values(errors)[0] as string) ||
                            'Gagal membuat undangan.',
                    );
                },
            },
        );

        return;
    }

    processingAction.value = 'invite';
    setTimeout(() => {
        const newInv: UserInvitationItem = {
            id: localInvitations.value.length + 1,
            public_id: '01J7NEWINVITATION' + Date.now(),
            name: payload.name,
            email: payload.email,
            account_type: payload.account_type,
            status: 'PENDING',
            expires_at: new Date(Date.now() + 72 * 3600 * 1000).toISOString(),
            accepted_at: null,
            revoked_at: null,
            created_at: new Date().toISOString(),
            invited_by: {
                id: 1,
                name: 'Administrator Sistem Pemkot',
                email: 'admin@pemkot.go.id',
            },
        };
        localInvitations.value.unshift(newInv);

        localEvents.value.unshift({
            id: localEvents.value.length + 1,
            user_id: null,
            user_name: payload.name,
            user_email: payload.email,
            event_type: 'INVITATION_CREATED',
            actor: {
                id: 1,
                name: 'Administrator Sistem',
                email: 'admin@pemkot.go.id',
            },
            description: `Membuat undangan aktivasi ${payload.account_type === 'INTERNAL' ? 'akun internal' : 'akun publik'} untuk ${payload.name} (${payload.email}).`,
            reason: null,
            metadata: { expires_at: newInv.expires_at },
            created_at: new Date().toISOString(),
        });

        inviteDialogOpen.value = false;
        processingAction.value = null;
        showFeedback(
            'success',
            `Tautan aktivasi untuk ${payload.email} berhasil dibuat dan dikirim.`,
        );
    }, 600);
}

function handleDeactivateConfirm(payload: DeactivateUserPayload): void {
    if (!selectedUser.value) {
        return;
    }

    if (!isPreview.value) {
        processingAction.value = 'deactivate';
        router.patch(
            `/back-office/users/${selectedUser.value.id}/status`,
            {
                is_active: false,
                reason: payload.reason,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    deactivateDialogOpen.value = false;
                    processingAction.value = null;
                    showFeedback(
                        'success',
                        `Akun ${selectedUser.value?.name} berhasil dinonaktifkan.`,
                    );
                },
                onError: (errors) => {
                    processingAction.value = null;
                    showFeedback(
                        'error',
                        (Object.values(errors)[0] as string) ||
                            'Gagal menonaktifkan akun.',
                    );
                },
            },
        );

        return;
    }

    processingAction.value = 'deactivate';

    setTimeout(() => {
        const user = selectedUser.value!;
        user.is_active = false;
        user.active_sessions_count = 0;
        user.roles = []; // operational roles detached
        user.can_deactivate = false;
        user.deactivation_block_reason =
            'Akun sudah dalam status dinonaktifkan.';

        localEvents.value.unshift({
            id: localEvents.value.length + 1,
            user_id: user.id,
            user_name: user.name,
            user_email: user.email,
            event_type: 'USER_DEACTIVATED',
            actor: {
                id: 1,
                name: 'Administrator Sistem',
                email: 'admin@pemkot.go.id',
            },
            description: `Akun ${user.name} dinonaktifkan dan seluruh sesi aktif dicabut.`,
            reason: payload.reason,
            metadata: null,
            created_at: new Date().toISOString(),
        });

        deactivateDialogOpen.value = false;
        processingAction.value = null;
        showFeedback(
            'success',
            `Akun ${user.name} berhasil dinonaktifkan. Sesi telah dicabut.`,
        );
    }, 600);
}

function handleReactivateConfirm(): void {
    if (!selectedUser.value) {
        return;
    }

    if (!isPreview.value) {
        processingAction.value = 'reactivate';
        router.patch(
            `/back-office/users/${selectedUser.value.id}/status`,
            {
                is_active: true,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    reactivateDialogOpen.value = false;
                    processingAction.value = null;
                    showFeedback(
                        'success',
                        `Akun ${selectedUser.value?.name} berhasil diaktifkan kembali.`,
                    );
                },
                onError: (errors) => {
                    processingAction.value = null;
                    showFeedback(
                        'error',
                        (Object.values(errors)[0] as string) ||
                            'Gagal mengaktifkan akun.',
                    );
                },
            },
        );

        return;
    }

    processingAction.value = 'reactivate';

    setTimeout(() => {
        const user = selectedUser.value!;
        user.is_active = true;
        user.can_deactivate = user.active_position === null;
        user.deactivation_block_reason = user.active_position
            ? 'Akun memiliki penugasan jabatan aktif.'
            : null;

        localEvents.value.unshift({
            id: localEvents.value.length + 1,
            user_id: user.id,
            user_name: user.name,
            user_email: user.email,
            event_type: 'USER_REACTIVATED',
            actor: {
                id: 1,
                name: 'Administrator Sistem',
                email: 'admin@pemkot.go.id',
            },
            description: `Akun ${user.name} telah diaktifkan kembali.`,
            reason: null,
            metadata: null,
            created_at: new Date().toISOString(),
        });

        reactivateDialogOpen.value = false;
        processingAction.value = null;
        showFeedback(
            'success',
            `Akun ${user.name} telah aktif kembali. Silakan tetapkan role bila diperlukan.`,
        );
    }, 600);
}

function handleRevokeSessions(): void {
    if (!selectedUser.value) {
        return;
    }

    if (!isPreview.value) {
        processingAction.value = 'sessions';
        router.post(
            `/back-office/users/${selectedUser.value.id}/sessions/revoke`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    securityModalOpen.value = false;
                    processingAction.value = null;
                    showFeedback(
                        'success',
                        `Seluruh sesi aktif untuk ${selectedUser.value?.name} telah dicabut.`,
                    );
                },
                onError: (errors) => {
                    processingAction.value = null;
                    showFeedback(
                        'error',
                        (Object.values(errors)[0] as string) ||
                            'Gagal mencabut sesi.',
                    );
                },
            },
        );

        return;
    }

    processingAction.value = 'sessions';

    setTimeout(() => {
        const user = selectedUser.value!;
        user.active_sessions_count = 0;

        localEvents.value.unshift({
            id: localEvents.value.length + 1,
            user_id: user.id,
            user_name: user.name,
            user_email: user.email,
            event_type: 'SESSIONS_REVOKED',
            actor: {
                id: 1,
                name: 'Administrator Sistem',
                email: 'admin@pemkot.go.id',
            },
            description: `Mencabut seluruh sesi aktif akun ${user.name}.`,
            reason: 'Permintaan keamanan dari super-admin.',
            metadata: null,
            created_at: new Date().toISOString(),
        });

        processingAction.value = null;
        showFeedback(
            'success',
            `Seluruh sesi aktif untuk ${user.name} telah berhasil dicabut.`,
        );
    }, 500);
}

function handleSendPasswordReset(): void {
    if (!selectedUser.value) {
        return;
    }

    if (!isPreview.value) {
        processingAction.value = 'password_reset';
        router.post(
            `/back-office/users/${selectedUser.value.id}/password-reset`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    securityModalOpen.value = false;
                    processingAction.value = null;
                    showFeedback(
                        'success',
                        `Tautan reset kata sandi telah dikirimkan ke email ${selectedUser.value?.email}.`,
                    );
                },
                onError: (errors) => {
                    processingAction.value = null;
                    showFeedback(
                        'error',
                        (Object.values(errors)[0] as string) ||
                            'Gagal mengirim tautan reset kata sandi.',
                    );
                },
            },
        );

        return;
    }

    processingAction.value = 'password_reset';

    setTimeout(() => {
        const user = selectedUser.value!;

        localEvents.value.unshift({
            id: localEvents.value.length + 1,
            user_id: user.id,
            user_name: user.name,
            user_email: user.email,
            event_type: 'PASSWORD_RESET_LINK_SENT',
            actor: {
                id: 1,
                name: 'Administrator Sistem',
                email: 'admin@pemkot.go.id',
            },
            description: `Mengirimkan tautan reset kata sandi resmi ke ${user.email}.`,
            reason: 'Permintaan reset kata sandi oleh super-admin.',
            metadata: null,
            created_at: new Date().toISOString(),
        });

        processingAction.value = null;
        showFeedback(
            'success',
            `Tautan reset kata sandi resmi telah dikirimkan ke email ${user.email}.`,
        );
    }, 500);
}

function handleResetMfa(): void {
    if (!selectedUser.value) {
        return;
    }

    if (!isPreview.value) {
        processingAction.value = 'mfa_reset';
        router.post(
            `/back-office/users/${selectedUser.value.id}/two-factor/reset`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    securityModalOpen.value = false;
                    processingAction.value = null;
                    showFeedback(
                        'success',
                        `MFA untuk ${selectedUser.value?.name} telah direset.`,
                    );
                },
                onError: (errors) => {
                    processingAction.value = null;
                    showFeedback(
                        'error',
                        (Object.values(errors)[0] as string) ||
                            'Gagal mereset MFA.',
                    );
                },
            },
        );

        return;
    }

    processingAction.value = 'mfa_reset';

    setTimeout(() => {
        const user = selectedUser.value!;
        user.two_factor_enabled = false;
        user.active_sessions_count = 0;

        localEvents.value.unshift({
            id: localEvents.value.length + 1,
            user_id: user.id,
            user_name: user.name,
            user_email: user.email,
            event_type: 'MFA_RESET',
            actor: {
                id: 1,
                name: 'Administrator Sistem',
                email: 'admin@pemkot.go.id',
            },
            description: `Mereset konfigurasi MFA (TOTP) dan mencabut sesi akun ${user.name}.`,
            reason: 'Verifikasi identitas oleh super-admin.',
            metadata: null,
            created_at: new Date().toISOString(),
        });

        processingAction.value = null;
        showFeedback(
            'success',
            `MFA untuk ${user.name} telah direset. Pengguna dapat login dengan password dan mengatur ulang MFA.`,
        );
    }, 600);
}

function handleResendInvitation(invitation: UserInvitationItem): void {
    if (!isPreview.value) {
        router.post(
            `/back-office/users/invitations/${invitation.id}/resend`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    showFeedback(
                        'success',
                        `Tautan aktivasi baru telah dikirimkan ke ${invitation.email}.`,
                    );
                },
                onError: (errors) => {
                    showFeedback(
                        'error',
                        (Object.values(errors)[0] as string) ||
                            'Gagal mengirim ulang undangan.',
                    );
                },
            },
        );

        return;
    }

    invitation.status = 'PENDING';
    invitation.expires_at = new Date(
        Date.now() + 72 * 3600 * 1000,
    ).toISOString();

    localEvents.value.unshift({
        id: localEvents.value.length + 1,
        user_id: null,
        user_name: invitation.name,
        user_email: invitation.email,
        event_type: 'INVITATION_RESENT',
        actor: {
            id: 1,
            name: 'Administrator Sistem',
            email: 'admin@pemkot.go.id',
        },
        description: `Mengirim ulang tautan aktivasi untuk ${invitation.name} (${invitation.email}). Token lama dicabut.`,
        reason: null,
        metadata: { new_expires_at: invitation.expires_at },
        created_at: new Date().toISOString(),
    });

    showFeedback(
        'success',
        `Tautan aktivasi baru telah dikirimkan ke ${invitation.email}.`,
    );
}

function handleRevokeInvitation(invitation: UserInvitationItem): void {
    if (!isPreview.value) {
        router.post(
            `/back-office/users/invitations/${invitation.id}/revoke`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    showFeedback(
                        'success',
                        `Undangan untuk ${invitation.email} berhasil dicabut.`,
                    );
                },
                onError: (errors) => {
                    showFeedback(
                        'error',
                        (Object.values(errors)[0] as string) ||
                            'Gagal membatalkan undangan.',
                    );
                },
            },
        );

        return;
    }

    invitation.status = 'REVOKED';
    invitation.revoked_at = new Date().toISOString();

    localEvents.value.unshift({
        id: localEvents.value.length + 1,
        user_id: null,
        user_name: invitation.name,
        user_email: invitation.email,
        event_type: 'INVITATION_REVOKED',
        actor: {
            id: 1,
            name: 'Administrator Sistem',
            email: 'admin@pemkot.go.id',
        },
        description: `Mencabut undangan aktivasi untuk ${invitation.name} (${invitation.email}).`,
        reason: 'Dicabut oleh super-admin.',
        metadata: null,
        created_at: new Date().toISOString(),
    });

    showFeedback(
        'success',
        `Undangan untuk ${invitation.email} berhasil dicabut.`,
    );
}

function formatDate(isoString: string | null): string {
    if (!isoString) {
        return '-';
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(isoString));
}
</script>

<template>
    <Head title="Kelola Pengguna — Back Office" />

    <div class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8">
        <!-- Header Section -->
        <div
            class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="rounded-xl bg-primary/10 p-2 text-primary">
                        <Users class="size-6" />
                    </span>
                    <div>
                        <h1
                            class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl"
                        >
                            Manajemen Pengguna
                        </h1>
                        <p class="mt-0.5 text-sm text-muted-foreground">
                            Kelola siklus hidup akun publik, aparatur internal,
                            undangan aktivasi, dan audit keamanan.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <Button
                    type="button"
                    class="cursor-pointer gap-2 bg-primary text-primary-foreground shadow-sm hover:bg-primary/90"
                    @click="inviteDialogOpen = true"
                >
                    <Plus class="size-4" />
                    <span>Undang Pengguna Baru</span>
                </Button>
            </div>
        </div>

        <!-- Feedback Alert -->
        <div
            v-if="feedbackNotice"
            class="flex items-center justify-between rounded-xl border p-4 text-sm transition-all"
            :class="
                feedbackNotice.type === 'success'
                    ? 'border-emerald-500/30 bg-emerald-50/80 text-emerald-900 dark:bg-emerald-950/20 dark:text-emerald-200'
                    : 'border-destructive/30 bg-destructive/5 text-destructive'
            "
        >
            <div class="flex items-center gap-2.5">
                <CheckCircle2
                    v-if="feedbackNotice.type === 'success'"
                    class="size-5 text-emerald-600 dark:text-emerald-400"
                />
                <AlertCircle v-else class="size-5 text-destructive" />
                <span>{{ feedbackNotice.message }}</span>
            </div>
            <button
                type="button"
                class="text-muted-foreground hover:text-foreground"
                @click="feedbackNotice = null"
            >
                <X class="size-4" />
            </button>
        </div>

        <!-- Quick Metrics Grid -->
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div class="stat-card rounded-2xl border bg-card p-4 shadow-xs">
                <div
                    class="mb-2 flex items-center justify-between text-muted-foreground"
                >
                    <span class="text-xs font-semibold tracking-wider uppercase"
                        >Total Pengguna</span
                    >
                    <Users class="size-4 text-primary" />
                </div>
                <p
                    class="text-2xl font-bold text-foreground tabular-nums sm:text-3xl"
                >
                    {{ stats.total }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Akun terdaftar dalam sistem
                </p>
            </div>

            <div class="stat-card rounded-2xl border bg-card p-4 shadow-xs">
                <div
                    class="mb-2 flex items-center justify-between text-muted-foreground"
                >
                    <span class="text-xs font-semibold tracking-wider uppercase"
                        >Internal Aktif</span
                    >
                    <Building2 class="size-4 text-indigo-500" />
                </div>
                <p
                    class="text-2xl font-bold text-foreground tabular-nums sm:text-3xl"
                >
                    {{ stats.internalActive }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Pegawai & pejabat Pemkot
                </p>
            </div>

            <div class="stat-card rounded-2xl border bg-card p-4 shadow-xs">
                <div
                    class="mb-2 flex items-center justify-between text-muted-foreground"
                >
                    <span class="text-xs font-semibold tracking-wider uppercase"
                        >Publik Aktif</span
                    >
                    <Globe class="size-4 text-emerald-500" />
                </div>
                <p
                    class="text-2xl font-bold text-foreground tabular-nums sm:text-3xl"
                >
                    {{ stats.publicActive }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Masyarakat & instansi luar
                </p>
            </div>

            <div class="stat-card rounded-2xl border bg-card p-4 shadow-xs">
                <div
                    class="mb-2 flex items-center justify-between text-muted-foreground"
                >
                    <span class="text-xs font-semibold tracking-wider uppercase"
                        >Undangan Menunggu</span
                    >
                    <Clock class="size-4 text-amber-500" />
                </div>
                <p
                    class="text-2xl font-bold text-foreground tabular-nums sm:text-3xl"
                >
                    {{ stats.pendingInvites }}
                </p>
                <p class="mt-1 text-xs text-muted-foreground">
                    Belum diklaim dalam 72 jam
                </p>
            </div>
        </div>

        <!-- Tab Navigation & Toolbar -->
        <div class="flex flex-col gap-4">
            <div
                class="flex w-fit items-center gap-1.5 overflow-x-auto rounded-xl border bg-muted/60 p-1 text-xs font-medium"
            >
                <button
                    type="button"
                    class="flex cursor-pointer items-center gap-2 rounded-lg px-4 py-2 whitespace-nowrap transition-all"
                    :class="
                        activeTab === 'all'
                            ? 'bg-background font-semibold text-foreground shadow-xs'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = 'all'"
                >
                    <Users class="size-3.5" />
                    <span>Semua Akun ({{ localUsers.length }})</span>
                </button>
                <button
                    type="button"
                    class="flex cursor-pointer items-center gap-2 rounded-lg px-4 py-2 whitespace-nowrap transition-all"
                    :class="
                        activeTab === 'internal'
                            ? 'bg-background font-semibold text-foreground shadow-xs'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = 'internal'"
                >
                    <Building2 class="size-3.5 text-indigo-500" />
                    <span>Pengguna Internal</span>
                </button>
                <button
                    type="button"
                    class="flex cursor-pointer items-center gap-2 rounded-lg px-4 py-2 whitespace-nowrap transition-all"
                    :class="
                        activeTab === 'public'
                            ? 'bg-background font-semibold text-foreground shadow-xs'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = 'public'"
                >
                    <Globe class="size-3.5 text-emerald-500" />
                    <span>Pengguna Publik</span>
                </button>
                <button
                    type="button"
                    class="flex cursor-pointer items-center gap-2 rounded-lg px-4 py-2 whitespace-nowrap transition-all"
                    :class="
                        activeTab === 'invitations'
                            ? 'bg-background font-semibold text-foreground shadow-xs'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = 'invitations'"
                >
                    <Mail class="size-3.5 text-amber-500" />
                    <span>Undangan ({{ localInvitations.length }})</span>
                </button>
                <button
                    type="button"
                    class="flex cursor-pointer items-center gap-2 rounded-lg px-4 py-2 whitespace-nowrap transition-all"
                    :class="
                        activeTab === 'events'
                            ? 'bg-background font-semibold text-foreground shadow-xs'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="activeTab = 'events'"
                >
                    <Activity class="size-3.5 text-primary" />
                    <span>Audit Keamanan</span>
                </button>
            </div>

            <!-- Filters Bar (For Users & Invitations) -->
            <div
                v-if="activeTab !== 'events'"
                class="flex flex-col items-stretch justify-between gap-3 sm:flex-row sm:items-center"
            >
                <div class="relative max-w-md flex-1">
                    <Input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari berdasarkan nama, email, jabatan..."
                        class="rounded-xl pl-9 text-sm"
                    />
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <button
                        v-if="searchQuery"
                        class="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                        @click="searchQuery = ''"
                    >
                        <X class="size-3.5" />
                    </button>
                </div>

                <div
                    v-if="activeTab !== 'invitations'"
                    class="flex flex-wrap items-center gap-2"
                >
                    <!-- Status Filter -->
                    <select
                        v-model="statusFilter"
                        class="cursor-pointer rounded-xl border bg-background px-3 py-1.5 text-xs text-foreground outline-none"
                    >
                        <option value="ALL">Status: Semua</option>
                        <option value="ACTIVE">Status: Aktif</option>
                        <option value="DEACTIVATED">
                            Status: Dinonaktifkan
                        </option>
                    </select>

                    <!-- Verified Filter -->
                    <select
                        v-model="verifiedFilter"
                        class="cursor-pointer rounded-xl border bg-background px-3 py-1.5 text-xs text-foreground outline-none"
                    >
                        <option value="ALL">Verifikasi: Semua</option>
                        <option value="VERIFIED">Email Terverifikasi</option>
                        <option value="UNVERIFIED">Belum Verifikasi</option>
                    </select>

                    <!-- Unit Filter (if internal or all) -->
                    <select
                        v-if="activeTab !== 'public'"
                        v-model="unitFilter"
                        class="cursor-pointer rounded-xl border bg-background px-3 py-1.5 text-xs text-foreground outline-none"
                    >
                        <option value="ALL">Unit: Semua</option>
                        <option
                            v-for="u in previewUnits"
                            :key="u.id"
                            :value="u.code"
                        >
                            {{ u.name }}
                        </option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div
            class="table-container overflow-hidden rounded-2xl border bg-card shadow-xs"
        >
            <!-- 1. TAB: USERS LIST (All / Internal / Public) -->
            <div
                v-if="
                    activeTab === 'all' ||
                    activeTab === 'internal' ||
                    activeTab === 'public'
                "
                class="overflow-x-auto"
            >
                <table class="w-full text-left text-sm">
                    <thead
                        class="border-b bg-muted/40 text-xs font-semibold text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-4 py-3.5">Pengguna</th>
                            <th class="px-4 py-3.5">Tipe & Status</th>
                            <th class="px-4 py-3.5">Jabatan / Profil</th>
                            <th class="px-4 py-3.5">Keamanan</th>
                            <th class="px-4 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        <tr v-if="filteredUsers.length === 0">
                            <td
                                colspan="5"
                                class="py-12 text-center text-xs text-muted-foreground"
                            >
                                Tidak ada data pengguna yang cocok dengan
                                kriteria pencarian.
                            </td>
                        </tr>
                        <tr
                            v-for="user in filteredUsers"
                            :key="user.id"
                            class="group transition-colors hover:bg-muted/30"
                        >
                            <!-- Pengguna Name & Email -->
                            <td class="px-4 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex size-9 items-center justify-center rounded-full text-xs font-bold"
                                        :class="
                                            user.account_type === 'INTERNAL'
                                                ? 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300'
                                                : 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                        "
                                    >
                                        {{
                                            user.name.slice(0, 2).toUpperCase()
                                        }}
                                    </div>
                                    <div>
                                        <Link
                                            :href="`/back-office/previews/users/${user.id}`"
                                            class="flex items-center gap-1.5 font-semibold text-foreground transition-colors hover:text-primary"
                                        >
                                            <span>{{ user.name }}</span>
                                        </Link>
                                        <div
                                            class="flex flex-wrap items-center gap-1.5 text-xs text-muted-foreground"
                                        >
                                            <span>{{ user.email }}</span>
                                            <template v-if="user.phone_number">
                                                <span
                                                    class="text-muted-foreground/40"
                                                    >•</span
                                                >
                                                <span
                                                    class="font-mono text-[11px]"
                                                    >{{
                                                        user.phone_number
                                                    }}</span
                                                >
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Tipe & Status Badges -->
                            <td class="px-4 py-3.5">
                                <div class="flex flex-col items-start gap-1">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="rounded-md px-2 py-0.5 text-[11px] font-semibold"
                                            :class="
                                                user.account_type === 'INTERNAL'
                                                    ? 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300'
                                                    : 'bg-slate-500/10 text-slate-700 dark:text-slate-300'
                                            "
                                        >
                                            {{
                                                user.account_type === 'INTERNAL'
                                                    ? 'Internal'
                                                    : 'Publik'
                                            }}
                                        </span>
                                        <span
                                            class="flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-semibold"
                                            :class="
                                                user.is_active
                                                    ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                                                    : 'bg-destructive/10 text-destructive'
                                            "
                                        >
                                            <span
                                                class="size-1.5 rounded-full"
                                                :class="
                                                    user.is_active
                                                        ? 'bg-emerald-500'
                                                        : 'bg-destructive'
                                                "
                                            ></span>
                                            {{
                                                user.is_active
                                                    ? 'Aktif'
                                                    : 'Dinonaktifkan'
                                            }}
                                        </span>
                                    </div>
                                    <span
                                        v-if="user.email_verified_at"
                                        class="text-[11px] text-muted-foreground"
                                    >
                                        Terverifikasi:
                                        {{ formatDate(user.email_verified_at) }}
                                    </span>
                                    <span
                                        v-else
                                        class="text-[11px] font-medium text-amber-600 dark:text-amber-400"
                                    >
                                        Email Belum Diverifikasi
                                    </span>
                                </div>
                            </td>

                            <!-- Jabatan / Profil -->
                            <td class="px-4 py-3.5">
                                <div v-if="user.account_type === 'INTERNAL'">
                                    <div
                                        v-if="user.active_position"
                                        class="space-y-0.5"
                                    >
                                        <p
                                            class="text-xs font-medium text-foreground"
                                        >
                                            {{
                                                user.active_position
                                                    .position_name
                                            }}
                                        </p>
                                        <p
                                            class="text-[11px] text-muted-foreground"
                                        >
                                            {{ user.active_position.unit_name }}
                                        </p>
                                    </div>
                                    <span
                                        v-else
                                        class="text-xs text-muted-foreground italic"
                                    >
                                        Belum memiliki jabatan aktif
                                    </span>
                                </div>
                                <div
                                    v-else
                                    class="space-y-0.5 text-xs text-muted-foreground"
                                >
                                    <p class="font-medium text-foreground">
                                        {{
                                            user.submission_metrics.total
                                        }}
                                        Total Pengajuan
                                    </p>
                                    <p class="text-[11px]">
                                        {{
                                            user.submission_metrics.verified
                                        }}
                                        Selesai |
                                        {{
                                            user.submission_metrics.submitted
                                        }}
                                        Diproses
                                    </p>
                                </div>
                            </td>

                            <!-- Keamanan & Sesi -->
                            <td class="px-4 py-3.5">
                                <div class="space-y-1">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="flex items-center gap-1 rounded-md p-1 text-xs"
                                            :class="
                                                user.two_factor_enabled
                                                    ? 'bg-emerald-500/10 text-emerald-600'
                                                    : 'bg-muted text-muted-foreground'
                                            "
                                            :title="
                                                user.two_factor_enabled
                                                    ? 'MFA Aktif'
                                                    : 'MFA Belum Aktif'
                                            "
                                        >
                                            <Smartphone class="size-3.5" />
                                            <span
                                                class="text-[11px] font-medium"
                                                >{{
                                                    user.two_factor_enabled
                                                        ? '2FA'
                                                        : 'No 2FA'
                                                }}</span
                                            >
                                        </span>
                                        <span
                                            class="text-xs text-muted-foreground tabular-nums"
                                        >
                                            {{
                                                user.active_sessions_count
                                            }}
                                            sesi
                                        </span>
                                    </div>
                                    <p
                                        v-if="user.last_login_at"
                                        class="text-[10px] text-muted-foreground tabular-nums"
                                    >
                                        Login:
                                        {{ formatDate(user.last_login_at) }}
                                    </p>
                                </div>
                            </td>

                            <!-- Actions Dropdown -->
                            <td class="px-4 py-3.5 text-right">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            class="h-8 w-8 cursor-pointer p-0"
                                        >
                                            <span class="sr-only"
                                                >Buka menu</span
                                            >
                                            <svg
                                                class="size-4"
                                                fill="currentColor"
                                                viewBox="0 0 20 20"
                                            >
                                                <path
                                                    d="M6 10a2 2 0 11-4 0 2 2 0 014 0zM12 10a2 2 0 11-4 0 2 2 0 014 0zM18 10a2 2 0 11-4 0 2 2 0 014 0z"
                                                />
                                            </svg>
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent
                                        align="end"
                                        class="w-48 text-xs"
                                    >
                                        <DropdownMenuItem as-child>
                                            <Link
                                                :href="`/back-office/previews/users/${user.id}`"
                                                class="flex cursor-pointer items-center gap-2"
                                            >
                                                <Eye class="size-3.5" />
                                                <span>Lihat Detail Akun</span>
                                            </Link>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            class="flex cursor-pointer items-center gap-2"
                                            @click="openSecurityModal(user)"
                                        >
                                            <Shield
                                                class="size-3.5 text-primary"
                                            />
                                            <span>Tindakan Keamanan</span>
                                        </DropdownMenuItem>
                                        <DropdownMenuSeparator />
                                        <DropdownMenuItem
                                            v-if="user.is_active"
                                            class="flex cursor-pointer items-center gap-2 text-destructive focus:text-destructive"
                                            @click="openDeactivateDialog(user)"
                                        >
                                            <UserX class="size-3.5" />
                                            <span>Nonaktifkan Akun</span>
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-else
                                            class="flex cursor-pointer items-center gap-2 text-emerald-600 focus:text-emerald-600"
                                            @click="openReactivateDialog(user)"
                                        >
                                            <UserCheck class="size-3.5" />
                                            <span>Aktifkan Kembali</span>
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 2. TAB: INVITATIONS LIST -->
            <div
                v-else-if="activeTab === 'invitations'"
                class="overflow-x-auto"
            >
                <table class="w-full text-left text-sm">
                    <thead
                        class="border-b bg-muted/40 text-xs font-semibold text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-4 py-3.5">Calon Pengguna</th>
                            <th class="px-4 py-3.5">Tipe</th>
                            <th class="px-4 py-3.5">Status Undangan</th>
                            <th class="px-4 py-3.5">Masa Berlaku (72 Jam)</th>
                            <th class="px-4 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border/60">
                        <tr v-if="filteredInvitations.length === 0">
                            <td
                                colspan="5"
                                class="py-12 text-center text-xs text-muted-foreground"
                            >
                                Tidak ada data undangan aktivasi.
                            </td>
                        </tr>
                        <tr
                            v-for="inv in filteredInvitations"
                            :key="inv.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3.5">
                                <p class="font-semibold text-foreground">
                                    {{ inv.name }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    {{ inv.email }}
                                </p>
                            </td>
                            <td class="px-4 py-3.5">
                                <span
                                    class="rounded-md px-2.5 py-0.5 text-xs font-semibold"
                                    :class="
                                        inv.account_type === 'INTERNAL'
                                            ? 'bg-indigo-500/10 text-indigo-700 dark:text-indigo-300'
                                            : 'bg-slate-500/10 text-slate-700 dark:text-slate-300'
                                    "
                                >
                                    {{
                                        inv.account_type === 'INTERNAL'
                                            ? 'Internal'
                                            : 'Publik'
                                    }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold"
                                    :class="{
                                        'bg-amber-500/10 text-amber-700 dark:text-amber-300':
                                            inv.status === 'PENDING',
                                        'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300':
                                            inv.status === 'ACCEPTED',
                                        'bg-slate-500/10 text-slate-700 dark:text-slate-400':
                                            inv.status === 'REVOKED',
                                        'bg-destructive/10 text-destructive':
                                            inv.status === 'EXPIRED',
                                    }"
                                >
                                    <Clock
                                        v-if="inv.status === 'PENDING'"
                                        class="size-3"
                                    />
                                    <CheckCircle2
                                        v-else-if="inv.status === 'ACCEPTED'"
                                        class="size-3"
                                    />
                                    {{
                                        inv.status === 'PENDING'
                                            ? 'Menunggu Aktivasi'
                                            : inv.status === 'ACCEPTED'
                                              ? 'Sudah Diterima'
                                              : inv.status === 'REVOKED'
                                                ? 'Dicabut'
                                                : 'Kedaluwarsa'
                                    }}
                                </span>
                            </td>
                            <td
                                class="px-4 py-3.5 text-xs text-muted-foreground"
                            >
                                <p>
                                    Kedaluwarsa:
                                    {{ formatDate(inv.expires_at) }}
                                </p>
                                <p class="text-[11px]">
                                    Dibuat: {{ formatDate(inv.created_at) }}
                                </p>
                            </td>
                            <td class="space-x-1.5 px-4 py-3.5 text-right">
                                <Button
                                    v-if="inv.status === 'PENDING'"
                                    variant="outline"
                                    size="sm"
                                    class="h-7 cursor-pointer gap-1 text-xs"
                                    @click="handleResendInvitation(inv)"
                                >
                                    <RefreshCw class="size-3" />
                                    <span>Kirim Ulang</span>
                                </Button>
                                <Button
                                    v-if="inv.status === 'PENDING'"
                                    variant="ghost"
                                    size="sm"
                                    class="h-7 cursor-pointer text-xs text-destructive hover:text-destructive"
                                    @click="handleRevokeInvitation(inv)"
                                >
                                    <span>Cabut</span>
                                </Button>
                                <Link
                                    v-if="inv.status === 'PENDING'"
                                    :href="`/account-invitations/${inv.public_id}`"
                                    target="_blank"
                                    class="ml-2 text-xs text-primary hover:underline"
                                >
                                    Buka Tautan
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 3. TAB: SECURITY EVENTS AUDIT TRAIL -->
            <div v-else-if="activeTab === 'events'" class="p-6">
                <div class="space-y-4">
                    <div
                        class="flex items-center justify-between border-b pb-3"
                    >
                        <div>
                            <h3 class="text-base font-bold text-foreground">
                                Log Perubahan Keamanan Akun
                            </h3>
                            <p class="mt-0.5 text-xs text-muted-foreground">
                                Seluruh mutasi status akun, reset kredensial,
                                dan pembuatan undangan terekam secara
                                append-only.
                            </p>
                        </div>
                    </div>

                    <div class="divide-y divide-border/60">
                        <div
                            v-for="evt in localEvents"
                            :key="evt.id"
                            class="flex flex-col justify-between gap-2 py-3.5 text-xs sm:flex-row sm:items-center"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-foreground">{{
                                        evt.event_type
                                    }}</span>
                                    <span
                                        v-if="evt.actor"
                                        class="text-muted-foreground"
                                    >
                                        oleh
                                        <strong class="text-foreground">{{
                                            evt.actor.name
                                        }}</strong>
                                    </span>
                                </div>
                                <p class="text-muted-foreground">
                                    {{ evt.description }}
                                </p>
                                <p
                                    v-if="evt.reason"
                                    class="text-primary italic"
                                >
                                    Alasan: "{{ evt.reason }}"
                                </p>
                            </div>
                            <time
                                class="shrink-0 text-[11px] text-muted-foreground tabular-nums"
                            >
                                {{ formatDate(evt.created_at) }}
                            </time>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <InviteUserDialog
            :open="inviteDialogOpen"
            :processing="processingAction === 'invite'"
            @update:open="inviteDialogOpen = $event"
            @submit="handleInviteSubmit"
        />

        <DeactivateUserDialog
            :open="deactivateDialogOpen"
            :user="selectedUser"
            :processing="processingAction === 'deactivate'"
            @update:open="deactivateDialogOpen = $event"
            @confirm="handleDeactivateConfirm"
        />

        <ReactivateUserDialog
            :open="reactivateDialogOpen"
            :user="selectedUser"
            :processing="processingAction === 'reactivate'"
            @update:open="reactivateDialogOpen = $event"
            @confirm="handleReactivateConfirm"
        />

        <UserSecurityActionsModal
            :open="securityModalOpen"
            :user="selectedUser"
            :processing-action="processingAction as any"
            @update:open="securityModalOpen = $event"
            @revoke-sessions="handleRevokeSessions"
            @send-password-reset="handleSendPasswordReset"
            @reset-mfa="handleResetMfa"
        />
    </div>
</template>
