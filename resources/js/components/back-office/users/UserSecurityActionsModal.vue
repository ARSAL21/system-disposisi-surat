<script setup lang="ts">
import {
    AlertTriangle,
    KeyRound,
    LogOut,
    Shield,
    ShieldAlert,
    Smartphone,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import type {
    ResetUserMfaPayload,
    UserManagementItem,
} from '@/types/user-management';

const props = defineProps<{
    open: boolean;
    user: UserManagementItem | null;
    processingAction?: 'sessions' | 'password_reset' | 'mfa_reset' | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
    revokeSessions: [];
    sendPasswordReset: [];
    resetMfa: [payload: ResetUserMfaPayload];
}>();

type SecurityTab = 'sessions' | 'password' | 'mfa';
const activeTab = ref<SecurityTab>('sessions');

const adminPassword = ref('');
const adminTwoFactorCode = ref('');
const mfaError = ref('');

watch(
    () => props.open,
    (isOpen) => {
        if (isOpen) {
            activeTab.value = 'sessions';
            adminPassword.value = '';
            adminTwoFactorCode.value = '';
            mfaError.value = '';
        }
    },
);

function handleMfaResetSubmit(): void {
    if (props.processingAction) {
        return;
    }

    mfaError.value = '';

    if (!adminPassword.value) {
        mfaError.value =
            'Kata sandi super-admin wajib diisi untuk konfirmasi otorisasi.';

        return;
    }

    emit('resetMfa', {
        super_admin_password: adminPassword.value,
        super_admin_two_factor_code:
            adminTwoFactorCode.value.trim() || undefined,
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90vh] max-w-2xl overflow-y-auto">
            <DialogHeader>
                <div class="flex items-center gap-3">
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
                    >
                        <Shield class="size-5" />
                    </span>
                    <div>
                        <DialogTitle class="text-xl font-bold tracking-tight">
                            Tindakan Keamanan Akun
                        </DialogTitle>
                        <DialogDescription
                            class="text-sm text-muted-foreground"
                        >
                            Kelola sesi login, pemulihan kata sandi, dan
                            autentikasi multi-faktor.
                        </DialogDescription>
                    </div>
                </div>
            </DialogHeader>

            <div v-if="user" class="space-y-5 py-2">
                <!-- User Banner -->
                <div
                    class="flex items-center justify-between rounded-xl border bg-muted/30 p-3.5 text-sm"
                >
                    <div>
                        <p class="font-bold text-foreground">{{ user.name }}</p>
                        <p class="text-xs text-muted-foreground">
                            {{ user.email }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            class="rounded-lg px-2.5 py-1 text-xs font-semibold"
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
                            class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold"
                            :class="
                                user.two_factor_enabled
                                    ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                    : 'bg-muted text-muted-foreground'
                            "
                        >
                            <Smartphone class="size-3.5" />
                            {{
                                user.two_factor_enabled
                                    ? 'MFA Aktif'
                                    : 'MFA Nonaktif'
                            }}
                        </span>
                    </div>
                </div>

                <!-- Tab Segmented Controls -->
                <div
                    class="flex rounded-xl border bg-muted/60 p-1 text-xs font-medium"
                >
                    <button
                        type="button"
                        class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg px-3 py-2 text-center transition-all"
                        :class="
                            activeTab === 'sessions'
                                ? 'bg-background font-semibold text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="activeTab = 'sessions'"
                    >
                        <LogOut class="size-3.5" />
                        <span
                            >Sesi Aktif ({{ user.active_sessions_count }})</span
                        >
                    </button>
                    <button
                        type="button"
                        class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg px-3 py-2 text-center transition-all"
                        :class="
                            activeTab === 'password'
                                ? 'bg-background font-semibold text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="activeTab = 'password'"
                    >
                        <KeyRound class="size-3.5" />
                        <span>Reset Kata Sandi</span>
                    </button>
                    <button
                        type="button"
                        class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg px-3 py-2 text-center transition-all"
                        :class="
                            activeTab === 'mfa'
                                ? 'bg-background font-semibold text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                        @click="activeTab = 'mfa'"
                    >
                        <Smartphone class="size-3.5" />
                        <span>Reset MFA / TOTP</span>
                    </button>
                </div>

                <!-- Tab Content: Sesi Aktif -->
                <div
                    v-if="activeTab === 'sessions'"
                    class="space-y-4 rounded-xl border bg-card p-4"
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="shrink-0 rounded-lg bg-primary/10 p-2 text-primary"
                        >
                            <LogOut class="size-5" />
                        </span>
                        <div>
                            <h4 class="text-sm font-semibold text-foreground">
                                Pencabutan Sesi Login
                            </h4>
                            <p
                                class="mt-1 text-xs leading-relaxed text-muted-foreground"
                            >
                                Fitur ini akan menghapus seluruh sesi aktif
                                pengguna di semua perangkat browser. Pengguna
                                akan segera dipaksa keluar (*logged out*) pada
                                permintaan HTTP berikutnya.
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex items-center justify-between rounded-lg border bg-muted/30 p-3 text-xs"
                    >
                        <span class="text-muted-foreground"
                            >Jumlah Perangkat / Sesi Saat Ini:</span
                        >
                        <span class="text-sm font-bold text-foreground"
                            >{{ user.active_sessions_count }} sesi aktif</span
                        >
                    </div>

                    <div class="flex justify-end pt-2">
                        <Button
                            type="button"
                            variant="destructive"
                            class="gap-2"
                            :disabled="
                                Boolean(processingAction) ||
                                user.active_sessions_count === 0
                            "
                            @click="emit('revokeSessions')"
                        >
                            <Spinner v-if="processingAction === 'sessions'" />
                            <LogOut v-else class="size-4" />
                            <span>{{
                                processingAction === 'sessions'
                                    ? 'Mencabut...'
                                    : 'Cabut Semua Sesi Aktif'
                            }}</span>
                        </Button>
                    </div>
                </div>

                <!-- Tab Content: Reset Kata Sandi -->
                <div
                    v-if="activeTab === 'password'"
                    class="space-y-4 rounded-xl border bg-card p-4"
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="shrink-0 rounded-lg bg-amber-500/10 p-2 text-amber-600"
                        >
                            <KeyRound class="size-5" />
                        </span>
                        <div>
                            <h4 class="text-sm font-semibold text-foreground">
                                Kirim Tautan Reset Kata Sandi
                            </h4>
                            <p
                                class="mt-1 text-xs leading-relaxed text-muted-foreground"
                            >
                                Sistem akan mengirimkan tautan reset kata sandi
                                resmi langsung ke alamat email terdaftar
                                <strong>{{ user.email }}</strong
                                >. Super-admin tidak pernah mengetahui maupun
                                menetapkan password pengguna.
                            </p>
                        </div>
                    </div>

                    <div
                        class="rounded-lg border border-blue-500/20 bg-blue-50/50 p-3 text-xs leading-relaxed text-blue-900 dark:bg-blue-950/20 dark:text-blue-200"
                    >
                        Tautan berlaku selama 60 menit dan hanya dapat digunakan
                        satu kali oleh pemilik akun.
                    </div>

                    <div class="flex justify-end pt-2">
                        <Button
                            type="button"
                            class="gap-2 bg-primary text-primary-foreground hover:bg-primary/90"
                            :disabled="Boolean(processingAction)"
                            @click="emit('sendPasswordReset')"
                        >
                            <Spinner
                                v-if="processingAction === 'password_reset'"
                            />
                            <KeyRound v-else class="size-4" />
                            <span>{{
                                processingAction === 'password_reset'
                                    ? 'Mengirim...'
                                    : 'Kirim Tautan Reset ke Email'
                            }}</span>
                        </Button>
                    </div>
                </div>

                <!-- Tab Content: Reset MFA -->
                <div
                    v-if="activeTab === 'mfa'"
                    class="space-y-4 rounded-xl border bg-card p-4"
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="shrink-0 rounded-lg bg-destructive/10 p-2 text-destructive"
                        >
                            <ShieldAlert class="size-5" />
                        </span>
                        <div>
                            <h4 class="text-sm font-semibold text-foreground">
                                Reset Multi-Factor Authentication (MFA)
                            </h4>
                            <p
                                class="mt-1 text-xs leading-relaxed text-muted-foreground"
                            >
                                Gunakan tindakan darurat ini jika pengguna
                                kehilangan perangkat authenticator / ponselnya
                                dan tidak dapat masuk.
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="!user.two_factor_enabled"
                        class="rounded-lg border bg-muted/40 p-3 text-center text-xs text-muted-foreground"
                    >
                        Akun ini saat ini belum mengaktifkan autentikasi dua
                        faktor (MFA).
                    </div>

                    <div v-else class="space-y-3.5">
                        <Alert
                            variant="destructive"
                            class="border-destructive/30 bg-destructive/5 py-2.5 text-xs text-destructive"
                        >
                            <AlertTriangle class="size-4" />
                            <AlertTitle class="font-bold"
                                >Perlindungan Kredensial Kritis</AlertTitle
                            >
                            <AlertDescription class="mt-1 leading-relaxed">
                                Reset MFA akan menghapus kunci TOTP dan kode
                                pemulihan akun target, serta mencabut seluruh
                                sesi loginnya. Memerlukan konfirmasi otentikasi
                                super-admin.
                            </AlertDescription>
                        </Alert>

                        <div>
                            <label
                                for="admin-password"
                                class="mb-1 block text-xs font-semibold text-foreground"
                            >
                                Konfirmasi Kata Sandi Super-Admin
                                <span class="text-destructive">*</span>
                            </label>
                            <Input
                                id="admin-password"
                                v-model="adminPassword"
                                type="password"
                                placeholder="Masukkan kata sandi Anda..."
                                :disabled="Boolean(processingAction)"
                                @input="mfaError = ''"
                            />
                        </div>

                        <div>
                            <label
                                for="admin-2fa"
                                class="mb-1 block text-xs font-semibold text-foreground"
                            >
                                Kode TOTP / 2FA Super-Admin (Opsional bila sesi
                                masih segar)
                            </label>
                            <Input
                                id="admin-2fa"
                                v-model="adminTwoFactorCode"
                                type="text"
                                maxlength="6"
                                placeholder="6 digit kode authenticator..."
                                :disabled="Boolean(processingAction)"
                                @input="mfaError = ''"
                            />
                        </div>

                        <InputError :message="mfaError" class="mt-1" />

                        <div class="flex justify-end pt-2">
                            <Button
                                type="button"
                                variant="destructive"
                                class="gap-2"
                                :disabled="
                                    Boolean(processingAction) || !adminPassword
                                "
                                @click="handleMfaResetSubmit"
                            >
                                <Spinner
                                    v-if="processingAction === 'mfa_reset'"
                                />
                                <ShieldAlert v-else class="size-4" />
                                <span>{{
                                    processingAction === 'mfa_reset'
                                        ? 'Memproses Reset...'
                                        : 'Konfirmasi Reset MFA Target'
                                }}</span>
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <DialogFooter class="pt-2">
                <Button
                    type="button"
                    variant="outline"
                    :disabled="Boolean(processingAction)"
                    @click="emit('update:open', false)"
                >
                    Tutup
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
