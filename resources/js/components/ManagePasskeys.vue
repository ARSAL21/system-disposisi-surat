<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Fingerprint, KeyRound } from '@lucide/vue';
import { destroy } from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyRegistrationController';
import PasskeyItem from '@/components/PasskeyItem.vue';
import PasskeyRegister from '@/components/PasskeyRegister.vue';
import { Badge } from '@/components/ui/badge';
import type { Passkey } from '@/types/auth';

export type Props = {
    canManagePasskeys?: boolean;
    passkeys?: Passkey[];
};

withDefaults(defineProps<Props>(), {
    canManagePasskeys: false,
    passkeys: () => [],
});

const handleDelete = (id: number, onError: () => void) => {
    router.delete(destroy.url(id), {
        preserveScroll: true,
        onError,
    });
};

const handleRegisterSuccess = () => {
    router.reload();
};
</script>

<template>
    <div v-if="canManagePasskeys" class="space-y-6">
        <div class="flex items-center gap-3 border-b border-border/60 pb-4">
            <div
                class="flex size-9 items-center justify-center rounded-xl bg-violet-50 text-violet-600 dark:bg-violet-950/70 dark:text-violet-300"
            >
                <Fingerprint class="size-4.5" />
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-base font-semibold text-foreground">
                        Kunci Sandi (Passkeys)
                    </h3>
                    <Badge
                        variant="outline"
                        class="border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-800 dark:bg-violet-950/50 dark:text-violet-300"
                    >
                        {{ passkeys.length }} Terdaftar
                    </Badge>
                </div>
                <p class="text-xs text-muted-foreground">
                    Masuk secara instan dan aman menggunakan biometrik perangkat
                    atau kunci fisik
                </p>
            </div>
        </div>

        <div class="space-y-4">
            <div
                v-if="passkeys.length"
                class="divide-y divide-border/60 overflow-hidden rounded-2xl border border-black/5 bg-white shadow-xs dark:border-white/10 dark:bg-slate-900"
            >
                <PasskeyItem
                    v-for="passkey in passkeys"
                    :key="passkey.id"
                    :passkey="passkey"
                    @remove="handleDelete"
                />
            </div>

            <div
                v-else
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border/80 bg-neutral-50/50 p-8 text-center dark:bg-slate-900/50"
            >
                <div
                    class="mb-3 flex size-12 items-center justify-center rounded-2xl bg-violet-100 text-violet-600 dark:bg-violet-950/80 dark:text-violet-300"
                >
                    <KeyRound class="size-6" />
                </div>
                <h4 class="text-sm font-semibold text-foreground">
                    Belum Ada Passkey Terdaftar
                </h4>
                <p
                    class="mt-1 max-w-sm text-xs leading-relaxed text-muted-foreground"
                >
                    Tambahkan Touch ID, Face ID, Windows Hello, atau security
                    key fisik untuk masuk tanpa perlu mengingat kata sandi.
                </p>
            </div>

            <PasskeyRegister @success="handleRegisterSuccess" />
        </div>
    </div>
</template>
