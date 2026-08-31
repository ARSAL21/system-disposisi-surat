<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Fingerprint,
    KeyRound,
    Lock,
    Save,
    Shield,
    ShieldAlert,
    ShieldCheck,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import InputError from '@/components/InputError.vue';
import type { Props as ManagePasskeysProps } from '@/components/ManagePasskeys.vue';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
import type { Props as ManageTwoFactorProps } from '@/components/ManageTwoFactor.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/security';

type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Security settings',
                href: edit(),
            },
        ],
    },
});

const newPasswordValue = ref('');

const hasMinLength = computed(() => newPasswordValue.value.length >= 8);
const hasMixedCase = computed(
    () =>
        /[a-z]/.test(newPasswordValue.value) &&
        /[A-Z]/.test(newPasswordValue.value),
);
const hasNumbersOrSymbols = computed(() =>
    /[0-9\W_]/.test(newPasswordValue.value),
);
</script>

<template>
    <Head title="Security settings" />

    <h1 class="sr-only">Security settings</h1>

    <div class="space-y-10">
        <!-- Security Overview Health Card -->
        <div
            class="relative overflow-hidden rounded-2xl border border-indigo-100 bg-gradient-to-br from-indigo-50/60 via-white to-violet-50/40 p-5 shadow-xs sm:p-6 dark:border-indigo-900/30 dark:from-slate-900 dark:via-slate-900 dark:to-indigo-950/20"
        >
            <div
                class="flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="flex items-center gap-4">
                    <div
                        class="flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-600 text-white shadow-md shadow-indigo-500/20"
                    >
                        <ShieldCheck class="size-7" />
                    </div>
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <h2
                                class="text-base font-bold text-foreground sm:text-lg"
                            >
                                Status Keamanan Akun
                            </h2>
                            <Badge
                                v-if="props.twoFactorEnabled"
                                variant="outline"
                                class="border-emerald-300 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300"
                            >
                                <CheckCircle2
                                    class="mr-1 size-3 text-emerald-600"
                                />
                                Sangat Baik
                            </Badge>
                            <Badge
                                v-else
                                variant="outline"
                                class="border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-300"
                            >
                                <ShieldAlert
                                    class="mr-1 size-3 text-amber-600"
                                />
                                2FA Disarankan
                            </Badge>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            Lindungi akun Anda dengan kata sandi kuat, 2FA, dan
                            kunci sandi (Passkeys).
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 text-xs">
                    <div
                        class="flex items-center gap-1.5 rounded-lg border border-border/80 bg-white/70 px-3 py-1.5 font-medium backdrop-blur-xs dark:bg-slate-800/80"
                    >
                        <Lock class="size-3.5 text-indigo-500" />
                        <span>Kata Sandi Aktif</span>
                    </div>
                    <div
                        v-if="props.canManageTwoFactor"
                        class="flex items-center gap-1.5 rounded-lg border border-border/80 bg-white/70 px-3 py-1.5 font-medium backdrop-blur-xs dark:bg-slate-800/80"
                    >
                        <Shield
                            class="size-3.5"
                            :class="
                                props.twoFactorEnabled
                                    ? 'text-emerald-500'
                                    : 'text-amber-500'
                            "
                        />
                        <span
                            >2FA:
                            {{
                                props.twoFactorEnabled ? 'Aktif' : 'Nonaktif'
                            }}</span
                        >
                    </div>
                    <div
                        v-if="props.canManagePasskeys"
                        class="flex items-center gap-1.5 rounded-lg border border-border/80 bg-white/70 px-3 py-1.5 font-medium backdrop-blur-xs dark:bg-slate-800/80"
                    >
                        <Fingerprint class="size-3.5 text-violet-500" />
                        <span>Passkey ({{ props.passkeys?.length || 0 }})</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Update Password Form -->
        <div class="space-y-6">
            <div class="flex items-center gap-3 border-b border-border/60 pb-4">
                <div
                    class="flex size-9 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 dark:bg-indigo-950/70 dark:text-indigo-300"
                >
                    <KeyRound class="size-4.5" />
                </div>
                <div>
                    <h3 class="text-base font-semibold text-foreground">
                        Perbarui Kata Sandi
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        Gunakan kata sandi yang unik, acak, dan panjang untuk
                        melindungi akun
                    </p>
                </div>
            </div>

            <Form
                v-bind="SecurityController.update.form()"
                :options="{
                    preserveScroll: true,
                }"
                reset-on-success
                :reset-on-error="[
                    'password',
                    'password_confirmation',
                    'current_password',
                ]"
                class="space-y-5"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label
                        for="current_password"
                        class="text-sm font-medium text-foreground"
                    >
                        Kata Sandi Saat Ini
                    </Label>
                    <PasswordInput
                        id="current_password"
                        name="current_password"
                        class="block w-full focus-visible:ring-indigo-500"
                        autocomplete="current-password"
                        placeholder="Masukkan kata sandi saat ini"
                    />
                    <InputError :message="errors.current_password" />
                </div>

                <div class="grid gap-2">
                    <Label
                        for="password"
                        class="text-sm font-medium text-foreground"
                    >
                        Kata Sandi Baru
                    </Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        class="block w-full focus-visible:ring-indigo-500"
                        autocomplete="new-password"
                        placeholder="Masukkan kata sandi baru"
                        :passwordrules="props.passwordRules"
                        @input="
                            newPasswordValue = (
                                $event.target as HTMLInputElement
                            ).value
                        "
                    />
                    <InputError :message="errors.password" />

                    <!-- Live Password Strength hints -->
                    <div
                        v-if="newPasswordValue.length > 0"
                        class="mt-2 grid grid-cols-1 gap-1.5 rounded-xl border border-border/80 bg-neutral-50/60 p-3 text-xs sm:grid-cols-3 dark:bg-slate-800/40"
                    >
                        <div
                            class="flex items-center gap-1.5 font-medium transition-colors"
                            :class="
                                hasMinLength
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-muted-foreground'
                            "
                        >
                            <CheckCircle2
                                class="size-3.5"
                                :class="
                                    hasMinLength ? 'opacity-100' : 'opacity-30'
                                "
                            />
                            <span>Minimal 8 karakter</span>
                        </div>
                        <div
                            class="flex items-center gap-1.5 font-medium transition-colors"
                            :class="
                                hasMixedCase
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-muted-foreground'
                            "
                        >
                            <CheckCircle2
                                class="size-3.5"
                                :class="
                                    hasMixedCase ? 'opacity-100' : 'opacity-30'
                                "
                            />
                            <span>Huruf besar & kecil</span>
                        </div>
                        <div
                            class="flex items-center gap-1.5 font-medium transition-colors"
                            :class="
                                hasNumbersOrSymbols
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-muted-foreground'
                            "
                        >
                            <CheckCircle2
                                class="size-3.5"
                                :class="
                                    hasNumbersOrSymbols
                                        ? 'opacity-100'
                                        : 'opacity-30'
                                "
                            />
                            <span>Angka atau simbol</span>
                        </div>
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label
                        for="password_confirmation"
                        class="text-sm font-medium text-foreground"
                    >
                        Konfirmasi Kata Sandi Baru
                    </Label>
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        class="block w-full focus-visible:ring-indigo-500"
                        autocomplete="new-password"
                        placeholder="Ulangi kata sandi baru"
                        :passwordrules="props.passwordRules"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <div class="flex items-center gap-4 pt-2">
                    <Button
                        type="submit"
                        :disabled="processing"
                        data-test="update-password-button"
                        class="gap-2 bg-indigo-600 font-medium text-white shadow-xs hover:bg-indigo-700 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                    >
                        <Save class="size-4" />
                        Simpan Kata Sandi
                    </Button>
                </div>
            </Form>
        </div>

        <!-- Two-Factor Authentication Section -->
        <div
            v-if="props.canManageTwoFactor"
            class="border-t border-border/60 pt-8"
        >
            <ManageTwoFactor
                :canManageTwoFactor="props.canManageTwoFactor"
                :requiresConfirmation="props.requiresConfirmation"
                :twoFactorEnabled="props.twoFactorEnabled"
            />
        </div>

        <!-- Passkeys Section -->
        <div
            v-if="props.canManagePasskeys"
            class="border-t border-border/60 pt-8"
        >
            <ManagePasskeys
                :canManagePasskeys="props.canManagePasskeys"
                :passkeys="props.passkeys"
            />
        </div>
    </div>
</template>
