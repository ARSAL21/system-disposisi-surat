<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertCircle,
    Building2,
    Check,
    CheckCircle2,
    Clock,
    Globe,
    KeyRound,
    Lock,
    ShieldCheck,
    XCircle,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { previewInvitations } from '@/fixtures/user-management-preview';
import AuthSimpleLayout from '@/layouts/auth/AuthSimpleLayout.vue';
import type { UserInvitationItem } from '@/types/user-management';

const props = defineProps<{
    invitation?: UserInvitationItem;
    publicId?: string;
    token?: string;
    preview?: boolean;
}>();

const isPreview = computed(() => props.preview !== false);

function resolveInitialInvitation(): UserInvitationItem {
    if (props.invitation) {
        return { ...props.invitation };
    }

    const pathParts = window.location.pathname.split('/');
    const idParam = pathParts[pathParts.length - 1];
    const found = previewInvitations.find(
        (inv) => inv.public_id === idParam || String(inv.id) === idParam,
    );

    return found ? { ...found } : { ...previewInvitations[0] };
}

const currentInvitation = ref<UserInvitationItem>(resolveInitialInvitation());

// Form state
const password = ref('');
const passwordConfirmation = ref('');
const isSubmitting = ref(false);
const isSuccess = ref(false);
const formErrors = ref<Record<string, string>>({});

// Password requirements live validation
const hasMinLength = computed(() => password.value.length >= 8);
const hasUpperAndLower = computed(
    () => /[a-z]/.test(password.value) && /[A-Z]/.test(password.value),
);
const hasNumber = computed(() => /\d/.test(password.value));
const hasSpecialChar = computed(() => /[^A-Za-z0-9]/.test(password.value));
const passwordsMatch = computed(
    () =>
        password.value.length > 0 &&
        password.value === passwordConfirmation.value,
);

const isFormValid = computed(() => {
    return (
        hasMinLength.value &&
        hasUpperAndLower.value &&
        hasNumber.value &&
        hasSpecialChar.value &&
        passwordsMatch.value
    );
});

// Expiration / Status check
const isExpired = computed(() => {
    if (currentInvitation.value.status === 'EXPIRED') {
        return true;
    }

    return new Date(currentInvitation.value.expires_at) < new Date();
});

const isInvalid = computed(() => currentInvitation.value.status === 'INVALID');
const isRevoked = computed(() => currentInvitation.value.status === 'REVOKED');
const isAlreadyAccepted = computed(
    () => currentInvitation.value.status === 'ACCEPTED' || isSuccess.value,
);

function formatDate(isoString: string): string {
    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'full',
        timeStyle: 'short',
    }).format(new Date(isoString));
}

function handleFormSubmit() {
    formErrors.value = {};

    if (!hasMinLength.value) {
        formErrors.value.password = 'Kata sandi minimal harus 8 karakter.';

        return;
    }

    if (!hasUpperAndLower.value || !hasNumber.value || !hasSpecialChar.value) {
        formErrors.value.password =
            'Kata sandi harus mengandung huruf besar, huruf kecil, angka, dan karakter khusus.';

        return;
    }

    if (!passwordsMatch.value) {
        formErrors.value.password_confirmation =
            'Konfirmasi kata sandi tidak cocok.';

        return;
    }

    if (!isPreview.value) {
        isSubmitting.value = true;
        const targetPublicId =
            props.publicId || currentInvitation.value.public_id;
        const rawToken =
            props.token ||
            new URLSearchParams(window.location.search).get('token') ||
            '';

        router.post(
            `/account-invitations/${targetPublicId}`,
            {
                token: rawToken,
                name: currentInvitation.value.name,
                phone_number: currentInvitation.value.phone_number,
                password: password.value,
                password_confirmation: passwordConfirmation.value,
            },
            {
                onError: (errs) => {
                    isSubmitting.value = false;
                    formErrors.value = errs as Record<string, string>;
                },
                onFinish: () => {
                    isSubmitting.value = false;
                },
            },
        );

        return;
    }

    isSubmitting.value = true;

    // Simulate API call
    setTimeout(() => {
        isSubmitting.value = false;
        isSuccess.value = true;
        currentInvitation.value.status = 'ACCEPTED';
        currentInvitation.value.accepted_at = new Date().toISOString();
    }, 800);
}
</script>

<template>
    <Head title="Aktivasi Akun Layanan Persuratan" />

    <AuthSimpleLayout
        title="Aktivasi Akun Pengguna"
        description="Silakan tentukan kata sandi Anda untuk mengaktifkan akun dan mulai menggunakan layanan persuratan."
    >
        <!-- Preview Banner -->
        <div
            v-if="isPreview"
            class="mb-6 flex items-center justify-between gap-2 rounded-xl border border-indigo-500/20 bg-indigo-500/10 p-3 text-xs text-indigo-950 dark:text-indigo-200"
        >
            <div class="flex items-center gap-1.5">
                <ShieldCheck
                    class="size-3.5 shrink-0 text-indigo-600 dark:text-indigo-400"
                />
                <span>Simulasi Preview Halaman Aktivasi Undangan Pengguna</span>
            </div>
            <span
                class="rounded bg-indigo-500/20 px-1.5 py-0.5 font-mono text-[10px]"
                >M9_INVITE</span
            >
        </div>

        <!-- Case 0: Undangan Tidak Valid (Invalid Token / Not Found) -->
        <div
            v-if="isInvalid"
            class="space-y-4 rounded-2xl border bg-card p-6 text-center shadow-xs"
        >
            <div
                class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-destructive/10 text-destructive"
            >
                <AlertCircle class="size-6" />
            </div>
            <div class="space-y-1">
                <h3 class="text-base font-bold text-foreground">
                    Tautan Tidak Valid
                </h3>
                <p class="mx-auto max-w-sm text-xs text-muted-foreground">
                    Tautan aktivasi akun tidak valid atau token otentikasi tidak
                    cocok. Pastikan Anda membuka tautan lengkap yang diberikan
                    oleh administrator.
                </p>
            </div>
            <Button variant="outline" as-child class="rounded-xl text-xs">
                <Link href="/">Kembali ke Halaman Utama</Link>
            </Button>
        </div>

        <!-- Case 1: Undangan Sudah Dicabut (Revoked) -->
        <div
            v-else-if="isRevoked"
            class="space-y-4 rounded-2xl border bg-card p-6 text-center shadow-xs"
        >
            <div
                class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-destructive/10 text-destructive"
            >
                <XCircle class="size-6" />
            </div>
            <div class="space-y-1">
                <h3 class="text-base font-bold text-foreground">
                    Tautan Undangan Telah Dicabut
                </h3>
                <p class="mx-auto max-w-sm text-xs text-muted-foreground">
                    Undangan aktivasi akun untuk
                    <strong>{{ currentInvitation.email }}</strong> telah
                    dibatalkan atau dicabut oleh administrator sistem.
                </p>
            </div>
            <p class="pt-2 text-[11px] text-muted-foreground">
                Silakan hubungi administrator instansi jika Anda memerlukan
                tautan undangan baru.
            </p>
            <Button variant="outline" as-child class="rounded-xl text-xs">
                <Link href="/">Kembali ke Halaman Utama</Link>
            </Button>
        </div>

        <!-- Case 2: Undangan Kedaluwarsa (Expired) -->
        <div
            v-else-if="isExpired"
            class="space-y-4 rounded-2xl border bg-card p-6 text-center shadow-xs"
        >
            <div
                class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-600"
            >
                <Clock class="size-6" />
            </div>
            <div class="space-y-1">
                <h3 class="text-base font-bold text-foreground">
                    Tautan Undangan Kedaluwarsa
                </h3>
                <p class="mx-auto max-w-sm text-xs text-muted-foreground">
                    Masa berlaku aktivasi selama 72 jam telah berakhir pada
                    <span class="font-medium text-foreground">{{
                        formatDate(currentInvitation.expires_at)
                    }}</span
                    >.
                </p>
            </div>
            <p class="pt-2 text-[11px] text-muted-foreground">
                Demi standar keamanan akun dinas, undangan yang melewati tenggat
                waktu harus dikirimkan ulang oleh administrator.
            </p>
            <Button variant="outline" as-child class="rounded-xl text-xs">
                <Link href="/">Kembali ke Beranda</Link>
            </Button>
        </div>

        <!-- Case 3: Berhasil Diaktifkan (Success State) -->
        <div
            v-else-if="isAlreadyAccepted"
            class="space-y-4 rounded-2xl border bg-card p-6 text-center shadow-xs"
        >
            <div
                class="mx-auto flex size-14 items-center justify-center rounded-2xl bg-emerald-500/10 text-emerald-600"
            >
                <CheckCircle2 class="size-8" />
            </div>
            <div class="space-y-1">
                <h3 class="text-lg font-bold text-foreground">
                    Akun Berhasil Diaktifkan!
                </h3>
                <p class="mx-auto max-w-sm text-xs text-muted-foreground">
                    Kata sandi baru telah berhasil disimpan dan diamankan dengan
                    enkripsi standar pemerintah. Anda kini dapat masuk ke dalam
                    sistem persuratan.
                </p>
            </div>

            <div
                class="my-4 space-y-1.5 rounded-xl border bg-muted/30 p-3.5 text-left text-xs"
            >
                <div
                    class="flex items-center justify-between text-muted-foreground"
                >
                    <span>Nama Pengguna:</span>
                    <strong class="text-foreground">{{
                        currentInvitation.name
                    }}</strong>
                </div>
                <div
                    class="flex items-center justify-between text-muted-foreground"
                >
                    <span>Email Terdaftar:</span>
                    <span class="font-medium text-foreground">{{
                        currentInvitation.email
                    }}</span>
                </div>
                <div
                    class="flex items-center justify-between text-muted-foreground"
                >
                    <span>Tipe Akun:</span>
                    <span class="font-semibold text-foreground">
                        {{
                            currentInvitation.account_type === 'INTERNAL'
                                ? 'Pegawai Internal Pemkot'
                                : 'Pengguna Publik'
                        }}
                    </span>
                </div>
            </div>

            <div class="pt-2">
                <Button
                    as-child
                    class="h-11 w-full rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-xs font-semibold text-white shadow-lg shadow-emerald-600/20 hover:from-emerald-500 hover:to-teal-500"
                >
                    <Link
                        :href="
                            currentInvitation.account_type === 'INTERNAL'
                                ? '/back-office/login'
                                : '/login'
                        "
                    >
                        Masuk ke Akun Sekarang &rarr;
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Case 4: Formulir Pengisian Sandi (Active Invitation) -->
        <div v-else class="space-y-6">
            <!-- Invitation Identity Header Card -->
            <div class="space-y-3 rounded-2xl border bg-card p-4 shadow-xs">
                <div class="flex items-center justify-between">
                    <span
                        class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-[11px] font-semibold"
                        :class="
                            currentInvitation.account_type === 'INTERNAL'
                                ? 'border border-indigo-500/20 bg-indigo-500/10 text-indigo-700 dark:text-indigo-400'
                                : 'border border-emerald-500/20 bg-emerald-500/10 text-emerald-700 dark:text-emerald-400'
                        "
                    >
                        <component
                            :is="
                                currentInvitation.account_type === 'INTERNAL'
                                    ? Building2
                                    : Globe
                            "
                            class="size-3"
                        />
                        <span>{{
                            currentInvitation.account_type === 'INTERNAL'
                                ? 'Akun Pegawai Pemkot'
                                : 'Akun Pengguna Publik'
                        }}</span>
                    </span>

                    <span
                        class="flex items-center gap-1 text-[11px] text-muted-foreground"
                    >
                        <Clock class="size-3 text-amber-500" />
                        <span>Berlaku 72 Jam</span>
                    </span>
                </div>

                <div class="space-y-1">
                    <p class="text-xs text-muted-foreground">
                        Undangan atas nama:
                    </p>
                    <p class="text-base font-bold text-foreground">
                        {{ currentInvitation.name }}
                    </p>
                    <p class="font-mono text-xs text-muted-foreground">
                        {{ currentInvitation.email }}
                    </p>
                </div>

                <div
                    class="flex items-center gap-1.5 border-t pt-2 text-[11px] text-muted-foreground"
                >
                    <ShieldCheck class="size-3.5 shrink-0 text-emerald-500" />
                    <span v-if="currentInvitation.invited_by">
                        Diverifikasi oleh
                        {{ currentInvitation.invited_by.name }}
                    </span>
                </div>
            </div>

            <!-- Trustless Policy Callout -->
            <div
                class="space-y-1.5 rounded-xl border bg-muted/40 p-3.5 text-xs"
            >
                <div
                    class="flex items-center gap-1.5 font-semibold text-foreground"
                >
                    <Lock class="size-3.5 text-primary" />
                    <span>Kebijakan Keamanan Kredensial Mandiri</span>
                </div>
                <p class="text-[11px] leading-relaxed text-muted-foreground">
                    Administrator tidak pernah mengetahui dan tidak dapat
                    menetapkan kata sandi Anda. Anda memegang kendali penuh atas
                    keamanan akun ini.
                </p>
            </div>

            <!-- Password Form -->
            <form class="space-y-4" @submit.prevent="handleFormSubmit">
                <!-- Kata Sandi Baru -->
                <div class="space-y-1.5">
                    <Label
                        for="password"
                        class="text-xs font-semibold text-foreground"
                    >
                        Kata Sandi Baru <span class="text-destructive">*</span>
                    </Label>
                    <PasswordInput
                        id="password"
                        v-model="password"
                        name="password"
                        placeholder="Minimal 8 karakter aman"
                        class="h-11 rounded-xl text-sm"
                        autocomplete="new-password"
                        autofocus
                    />
                    <InputError :message="formErrors.password" />
                </div>

                <!-- Konfirmasi Kata Sandi Baru -->
                <div class="space-y-1.5">
                    <Label
                        for="password_confirmation"
                        class="text-xs font-semibold text-foreground"
                    >
                        Konfirmasi Kata Sandi Baru
                        <span class="text-destructive">*</span>
                    </Label>
                    <PasswordInput
                        id="password_confirmation"
                        v-model="passwordConfirmation"
                        name="password_confirmation"
                        placeholder="Ulangi kata sandi baru"
                        class="h-11 rounded-xl text-sm"
                        autocomplete="new-password"
                    />
                    <InputError :message="formErrors.password_confirmation" />
                </div>

                <!-- Password Validation Live Requirements Checklist -->
                <div
                    class="space-y-2 rounded-xl border bg-muted/20 p-3.5 text-[11px]"
                >
                    <p
                        class="text-[10px] font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Syarat Keamanan Kata Sandi:
                    </p>
                    <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2">
                        <div
                            class="flex items-center gap-1.5"
                            :class="
                                hasMinLength
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-muted-foreground'
                            "
                        >
                            <component
                                :is="hasMinLength ? Check : AlertCircle"
                                class="size-3 shrink-0"
                            />
                            <span>Minimal 8 karakter</span>
                        </div>
                        <div
                            class="flex items-center gap-1.5"
                            :class="
                                hasUpperAndLower
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-muted-foreground'
                            "
                        >
                            <component
                                :is="hasUpperAndLower ? Check : AlertCircle"
                                class="size-3 shrink-0"
                            />
                            <span>Huruf besar & huruf kecil</span>
                        </div>
                        <div
                            class="flex items-center gap-1.5"
                            :class="
                                hasNumber
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-muted-foreground'
                            "
                        >
                            <component
                                :is="hasNumber ? Check : AlertCircle"
                                class="size-3 shrink-0"
                            />
                            <span>Minimal 1 angka</span>
                        </div>
                        <div
                            class="flex items-center gap-1.5"
                            :class="
                                hasSpecialChar
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-muted-foreground'
                            "
                        >
                            <component
                                :is="hasSpecialChar ? Check : AlertCircle"
                                class="size-3 shrink-0"
                            />
                            <span>Simbol khusus (@$!%*#?&)</span>
                        </div>
                        <div
                            class="flex items-center gap-1.5 sm:col-span-2"
                            :class="
                                passwordsMatch
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-muted-foreground'
                            "
                        >
                            <component
                                :is="passwordsMatch ? Check : AlertCircle"
                                class="size-3 shrink-0"
                            />
                            <span>Konfirmasi kata sandi cocok</span>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <Button
                        type="submit"
                        class="h-11 w-full cursor-pointer rounded-xl bg-gradient-to-r from-emerald-600 via-teal-600 to-cyan-600 text-xs font-semibold text-white shadow-lg shadow-emerald-600/20 hover:from-emerald-500 hover:via-teal-500 hover:to-cyan-500"
                        :disabled="!isFormValid || isSubmitting"
                    >
                        <Spinner v-if="isSubmitting" class="mr-2" />
                        <KeyRound v-else class="mr-2 size-4" />
                        <span>{{
                            isSubmitting
                                ? 'Mengaktifkan Akun...'
                                : 'Aktifkan Akun & Masuk'
                        }}</span>
                    </Button>
                </div>
            </form>
        </div>
    </AuthSimpleLayout>
</template>
