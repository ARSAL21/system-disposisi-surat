<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CheckCircle2, KeyRound, LockKeyhole, ShieldAlert } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import type { MutationSecurityState } from '@/types';

const props = defineProps<{
    security: MutationSecurityState;
    subject: string;
}>();
const confirmedUntil = computed(() =>
    props.security.password_confirmed_until
        ? new Intl.DateTimeFormat('id-ID', {
              hour: '2-digit',
              minute: '2-digit',
          }).format(new Date(props.security.password_confirmed_until))
        : null,
);
</script>

<template>
    <section
        class="flex flex-col gap-4 rounded-2xl border p-4 sm:flex-row sm:items-center sm:justify-between"
        :class="
            security.can_mutate
                ? 'border-emerald-200 bg-emerald-50/70 dark:border-emerald-900 dark:bg-emerald-950/20'
                : 'border-violet-200 bg-violet-50/60 dark:border-violet-900 dark:bg-violet-950/20'
        "
        aria-label="Keamanan mutasi"
    >
        <div class="flex gap-3">
            <CheckCircle2
                v-if="security.can_mutate"
                class="mt-0.5 size-5 shrink-0 text-emerald-700 dark:text-emerald-300"
                aria-hidden="true"
            />
            <LockKeyhole
                v-else-if="!security.can_manage"
                class="mt-0.5 size-5 shrink-0 text-blue-700 dark:text-blue-300"
                aria-hidden="true"
            />
            <ShieldAlert
                v-else-if="!security.mfa_enabled"
                class="mt-0.5 size-5 shrink-0 text-amber-700 dark:text-amber-300"
                aria-hidden="true"
            />
            <KeyRound
                v-else
                class="mt-0.5 size-5 shrink-0 text-violet-700 dark:text-violet-300"
                aria-hidden="true"
            />
            <div>
                <p class="font-medium">
                    {{
                        security.can_mutate
                            ? 'Mode perubahan aktif'
                            : !security.can_manage
                              ? 'Mode baca aktif'
                              : !security.mfa_enabled
                                ? 'MFA diperlukan'
                                : 'Konfirmasi password diperlukan'
                    }}
                </p>
                <p class="mt-1 text-sm leading-6 text-muted-foreground">
                    <template v-if="security.can_mutate"
                        >Mutasi {{ subject }} aktif<span v-if="confirmedUntil">
                            hingga {{ confirmedUntil }}</span
                        >; server tetap memverifikasi dan mengaudit setiap
                        aksi.</template
                    >
                    <template v-else-if="!security.can_manage"
                        >Anda dapat membaca data, tetapi tidak memiliki
                        capability mutasi {{ subject }}.</template
                    >
                    <template v-else-if="!security.mfa_enabled"
                        >Aktifkan autentikasi dua faktor sebelum melakukan
                        mutasi {{ subject }}.</template
                    >
                    <template v-else
                        >Konfirmasi password untuk membuka mode perubahan selama
                        15 menit.</template
                    >
                </p>
            </div>
        </div>
        <Button
            v-if="security.can_manage && !security.mfa_enabled"
            as-child
            variant="outline"
            class="min-h-11 shrink-0 bg-background"
        >
            <Link :href="security.security_settings_url">Buka keamanan</Link>
        </Button>
        <Button
            v-else-if="security.can_manage && !security.password_confirmed"
            as-child
            class="min-h-11 shrink-0 bg-violet-700 hover:bg-violet-800"
        >
            <Link :href="security.activation_url"
                ><KeyRound class="size-4" aria-hidden="true" />Aktifkan
                perubahan</Link
            >
        </Button>
    </section>
</template>
