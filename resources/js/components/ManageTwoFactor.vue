<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import {
    CheckCircle2,
    QrCode,
    ShieldAlert,
    ShieldCheck,
    Smartphone,
} from '@lucide/vue';
import { onUnmounted, ref } from 'vue';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorSetupModal from '@/components/TwoFactorSetupModal.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useTwoFactorAuth } from '@/composables/useTwoFactorAuth';
import { disable, enable } from '@/routes/two-factor';

export type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
};

withDefaults(defineProps<Props>(), {
    canManageTwoFactor: false,
    requiresConfirmation: false,
    twoFactorEnabled: false,
});

const { hasSetupData, clearTwoFactorAuthData } = useTwoFactorAuth();
const showSetupModal = ref<boolean>(false);

onUnmounted(() => clearTwoFactorAuthData());
</script>

<template>
    <div v-if="canManageTwoFactor" class="space-y-6">
        <div class="flex items-center gap-3 border-b border-border/60 pb-4">
            <div
                class="flex size-9 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-950/70 dark:text-emerald-300"
            >
                <ShieldCheck class="size-4.5" />
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-semibold text-foreground">
                        Autentikasi Dua Faktor (2FA)
                    </h3>
                    <Badge
                        v-if="twoFactorEnabled"
                        variant="outline"
                        class="border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300"
                    >
                        <CheckCircle2 class="mr-1 size-3 text-emerald-600" />
                        Aktif
                    </Badge>
                    <Badge
                        v-else
                        variant="outline"
                        class="border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-300"
                    >
                        <ShieldAlert class="mr-1 size-3 text-amber-600" />
                        Nonaktif
                    </Badge>
                </div>
                <p class="text-xs text-muted-foreground">
                    Tingkatkan keamanan akun Anda dengan verifikasi kode OTP
                    berbasis aplikasi
                </p>
            </div>
        </div>

        <div
            v-if="!twoFactorEnabled"
            class="space-y-5 rounded-2xl border border-indigo-100/80 bg-gradient-to-br from-indigo-50/40 via-white to-neutral-50/40 p-5 sm:p-6 dark:border-indigo-900/30 dark:from-slate-900 dark:via-slate-900 dark:to-indigo-950/20"
        >
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="space-y-2">
                    <div
                        class="flex items-center gap-2 text-sm font-semibold text-foreground"
                    >
                        <Smartphone
                            class="size-4 text-indigo-600 dark:text-indigo-400"
                        />
                        <span>Gunakan Aplikasi Autentikator (TOTP)</span>
                    </div>
                    <p
                        class="max-w-xl text-xs leading-relaxed text-muted-foreground"
                    >
                        Saat 2FA aktif, Anda akan diminta memasukkan 6 digit
                        kode PIN acak dari aplikasi seperti Google
                        Authenticator, Microsoft Authenticator, atau Authy
                        setiap kali login.
                    </p>
                </div>

                <div>
                    <Button
                        v-if="hasSetupData"
                        @click="showSetupModal = true"
                        class="gap-2 bg-indigo-600 font-medium text-white shadow-xs hover:bg-indigo-700"
                    >
                        <ShieldCheck class="size-4" />
                        Lanjutkan Konfigurasi
                    </Button>
                    <Form
                        v-else
                        v-bind="enable.form()"
                        @success="showSetupModal = true"
                        #default="{ processing }"
                    >
                        <Button
                            type="submit"
                            :disabled="processing"
                            class="gap-2 bg-indigo-600 font-medium text-white shadow-xs hover:bg-indigo-700"
                        >
                            <QrCode class="size-4" />
                            Aktifkan 2FA Sekarang
                        </Button>
                    </Form>
                </div>
            </div>
        </div>

        <div
            v-else
            class="space-y-6 rounded-2xl border border-emerald-200/80 bg-gradient-to-br from-emerald-50/40 via-white to-neutral-50/40 p-5 sm:p-6 dark:border-emerald-900/30 dark:from-slate-900 dark:via-slate-900 dark:to-emerald-950/20"
        >
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <div class="space-y-1">
                    <div
                        class="flex items-center gap-2 text-sm font-semibold text-emerald-950 dark:text-emerald-200"
                    >
                        <CheckCircle2 class="size-4.5 text-emerald-600" />
                        <span>Autentikasi Dua Faktor Aktif</span>
                    </div>
                    <p
                        class="max-w-xl text-xs leading-relaxed text-muted-foreground"
                    >
                        Akun Anda telah terlindungi dengan verifikasi dua
                        langkah. Kode keamanan wajib dimasukkan saat proses
                        masuk.
                    </p>
                </div>

                <div class="relative inline">
                    <Form v-bind="disable.form()" #default="{ processing }">
                        <Button
                            variant="destructive"
                            type="submit"
                            :disabled="processing"
                            class="gap-2 bg-rose-600 font-medium text-white shadow-xs hover:bg-rose-700"
                        >
                            <ShieldAlert class="size-4" />
                            Nonaktifkan 2FA
                        </Button>
                    </Form>
                </div>
            </div>

            <div
                class="border-t border-emerald-100 pt-4 dark:border-emerald-900/40"
            >
                <TwoFactorRecoveryCodes />
            </div>
        </div>

        <TwoFactorSetupModal
            v-model:isOpen="showSetupModal"
            :requiresConfirmation="requiresConfirmation"
            :twoFactorEnabled="twoFactorEnabled"
        />
    </div>
</template>
