<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CheckCircle2, KeyRound, LockKeyhole, ShieldAlert } from '@lucide/vue';
import { computed, ref } from 'vue';
import BackOfficeConfirmPasswordModal from '@/components/back-office/auth/BackOfficeConfirmPasswordModal.vue';
import { Button } from '@/components/ui/button';
import type { AuthorizationMutationSecurity } from '@/types';

const props = defineProps<{
    security: AuthorizationMutationSecurity;
}>();

const isConfirmModalOpen = ref(false);

const confirmedUntil = computed(() => {
    if (!props.security.password_confirmed_until) {
        return null;
    }

    return new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(props.security.password_confirmed_until));
});
</script>

<template>
    <section
        v-if="!security.can_manage"
        class="flex flex-col gap-4 rounded-2xl border border-blue-200 bg-blue-50/70 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-blue-900 dark:bg-blue-950/30"
        aria-label="Mode akses otorisasi"
    >
        <div class="flex gap-3">
            <LockKeyhole
                class="mt-0.5 size-5 shrink-0 text-blue-700 dark:text-blue-300"
                aria-hidden="true"
            />
            <div>
                <p class="font-medium text-blue-950 dark:text-blue-100">
                    Mode baca aktif
                </p>
                <p
                    class="mt-1 text-sm leading-6 text-blue-800 dark:text-blue-200"
                >
                    Anda dapat memeriksa konfigurasi, tetapi tidak memiliki
                    capability untuk melakukan perubahan.
                </p>
            </div>
        </div>
    </section>

    <section
        v-else-if="!security.mfa_enabled"
        class="flex flex-col gap-4 rounded-2xl border border-amber-200 bg-amber-50/80 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-amber-900/70 dark:bg-amber-950/25"
        aria-label="MFA diperlukan"
    >
        <div class="flex gap-3">
            <ShieldAlert
                class="mt-0.5 size-5 shrink-0 text-amber-700 dark:text-amber-300"
                aria-hidden="true"
            />
            <div>
                <p class="font-medium text-amber-950 dark:text-amber-100">
                    Aktifkan MFA untuk melanjutkan
                </p>
                <p
                    class="mt-1 text-sm leading-6 text-amber-800 dark:text-amber-200"
                >
                    Mutasi privilege dikunci sampai autentikasi dua faktor telah
                    dikonfigurasi dan dikonfirmasi.
                </p>
            </div>
        </div>
        <Button
            as-child
            variant="outline"
            class="min-h-11 shrink-0 bg-background"
        >
            <Link :href="security.security_settings_url">
                <ShieldAlert class="size-4" aria-hidden="true" />
                Buka pengaturan keamanan
            </Link>
        </Button>
    </section>

    <section
        v-else-if="!security.password_confirmed"
        class="flex flex-col gap-4 rounded-2xl border border-violet-200 bg-violet-50/70 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-violet-900/70 dark:bg-violet-950/25"
        aria-label="Konfirmasi password diperlukan"
    >
        <div class="flex gap-3">
            <KeyRound
                class="mt-0.5 size-5 shrink-0 text-violet-700 dark:text-violet-300"
                aria-hidden="true"
            />
            <div>
                <p class="font-medium text-violet-950 dark:text-violet-100">
                    Mode perubahan masih terkunci
                </p>
                <p
                    class="mt-1 text-sm leading-6 text-violet-800 dark:text-violet-200"
                >
                    Konfirmasi password untuk membuka aksi administratif selama
                    15 menit.
                </p>
            </div>
        </div>
        <Button
            type="button"
            class="min-h-11 shrink-0 bg-violet-700 hover:bg-violet-800"
            @click="isConfirmModalOpen = true"
        >
            <KeyRound class="size-4" aria-hidden="true" />
            Aktifkan mode perubahan
        </Button>
    </section>

    <section
        v-else
        class="flex gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 dark:border-emerald-900/70 dark:bg-emerald-950/25"
        aria-label="Mode perubahan aktif"
    >
        <CheckCircle2
            class="mt-0.5 size-5 shrink-0 text-emerald-700 dark:text-emerald-300"
            aria-hidden="true"
        />
        <div>
            <p class="font-medium text-emerald-950 dark:text-emerald-100">
                Mode perubahan aktif
            </p>
            <p
                class="mt-1 text-sm leading-6 text-emerald-800 dark:text-emerald-200"
            >
                MFA dan recent password telah terpenuhi<span
                    v-if="confirmedUntil"
                >
                    hingga {{ confirmedUntil }}</span
                >. Setiap mutasi akan tetap diverifikasi dan diaudit oleh
                server.
            </p>
        </div>
    </section>

    <BackOfficeConfirmPasswordModal
        v-model:open="isConfirmModalOpen"
        title="Konfirmasi akses manage role"
    />
</template>
