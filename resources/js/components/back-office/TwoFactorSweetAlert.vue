<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Lock, ShieldAlert, Sparkles } from '@lucide/vue';
import { computed, onMounted } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useTwoFactorWalkthrough } from '@/composables/useTwoFactorWalkthrough';

const page = usePage();
const { isSweetAlertOpen, closeSweetAlert, startTour } =
    useTwoFactorWalkthrough();

const requiresMfaSetup = computed(() => {
    return Boolean(page.props.auth.user?.requires_mfa_setup);
});

onMounted(() => {
    if (requiresMfaSetup.value) {
        isSweetAlertOpen.value = true;
    }
});

const handleDismiss = () => {
    closeSweetAlert();
};

const handleStartTour = () => {
    closeSweetAlert();
    startTour();

    if (
        typeof window !== 'undefined' &&
        !window.location.pathname.startsWith('/settings')
    ) {
        router.visit('/settings/security', {
            preserveScroll: true,
        });
    }
};
</script>

<template>
    <Dialog
        :open="isSweetAlertOpen"
        @update:open="
            (open) => {
                if (!open) handleDismiss();
            }
        "
    >
        <DialogContent
            class="max-w-md overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 text-center shadow-2xl sm:p-8 dark:border-slate-800 dark:bg-slate-950"
            :show-close-button="false"
        >
            <div class="flex flex-col items-center">
                <!-- SweetAlert Animated Warning Icon -->
                <div class="relative my-2 flex items-center justify-center">
                    <div
                        class="absolute size-20 animate-ping rounded-full bg-amber-500/15 dark:bg-amber-400/10"
                    />
                    <div
                        class="relative flex size-18 items-center justify-center rounded-full border-2 border-amber-400/80 bg-amber-50 text-amber-600 shadow-inner dark:border-amber-500/50 dark:bg-amber-950/40 dark:text-amber-400"
                    >
                        <ShieldAlert class="size-9" />
                    </div>
                </div>

                <DialogHeader class="mt-4 space-y-2 text-center">
                    <div class="flex items-center justify-center gap-1.5">
                        <span
                            class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-100/70 px-2.5 py-0.5 text-[11px] font-bold tracking-wide text-amber-800 uppercase dark:border-amber-800 dark:bg-amber-950/60 dark:text-amber-300"
                        >
                            <Lock class="size-3" />
                            Akses Super Admin
                        </span>
                    </div>
                    <DialogTitle
                        class="text-xl font-bold tracking-tight text-slate-900 sm:text-2xl dark:text-white"
                    >
                        Akses Sistem Sementara Dibatasi
                    </DialogTitle>
                    <DialogDescription
                        class="text-xs leading-relaxed text-slate-600 sm:text-sm dark:text-slate-400"
                    >
                        Sistem mendeteksi bahwa akun Super Admin Anda belum
                        mengaktifkan
                        <strong class="text-slate-900 dark:text-white"
                            >Autentikasi Dua Faktor (2FA)</strong
                        >. Seluruh menu dan fitur operasional back-office
                        sementara diblokir demi kepatuhan standar keamanan
                        siber.
                    </DialogDescription>
                </DialogHeader>

                <div
                    class="mt-4 w-full rounded-2xl border border-slate-100 bg-slate-50/80 p-3.5 text-left text-xs text-slate-600 dark:border-slate-800/80 dark:bg-slate-900/60 dark:text-slate-400"
                >
                    <p class="flex items-start gap-2">
                        <span
                            class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full bg-amber-500 text-[10px] font-bold text-white"
                            >!</span
                        >
                        <span
                            >Silakan ikuti panduan langkah interaktif berikut
                            untuk memverifikasi kata sandi dan mengaktifkan
                            2FA.</span
                        >
                    </p>
                </div>

                <div class="mt-6 flex w-full flex-col gap-2 sm:flex-row">
                    <DialogClose as-child>
                        <Button
                            type="button"
                            variant="outline"
                            class="w-full border-slate-200 text-xs font-semibold text-slate-700 hover:bg-slate-100 sm:w-1/3 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-900"
                            @click="handleDismiss"
                        >
                            Nanti Saja
                        </Button>
                    </DialogClose>
                    <Button
                        type="button"
                        class="w-full gap-2 bg-indigo-600 text-xs font-semibold text-white shadow-md hover:bg-indigo-700 sm:w-2/3 dark:bg-indigo-600 dark:hover:bg-indigo-500"
                        @click="handleStartTour"
                    >
                        <span>Ikuti Panduan Aktivasi</span>
                        <Sparkles class="size-3.5" />
                    </Button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
