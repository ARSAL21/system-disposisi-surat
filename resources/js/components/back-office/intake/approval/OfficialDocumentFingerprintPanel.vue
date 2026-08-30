<script setup lang="ts">
import { Check, Copy, Fingerprint, ShieldCheck } from '@lucide/vue';
import { useClipboard } from '@vueuse/core';
import { Button } from '@/components/ui/button';

const props = defineProps<{ fingerprint: string }>();
const { copy, copied, isSupported } = useClipboard();

function copyFingerprint(): void {
    if (isSupported.value) {
        void copy(props.fingerprint);
    }
}
</script>

<template>
    <section
        class="rounded-2xl border border-violet-200/80 bg-slate-950 p-4 text-slate-100 shadow-inner sm:p-5 dark:border-violet-900"
        aria-labelledby="official-document-fingerprint"
    >
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-2">
                <Fingerprint
                    class="size-4 text-violet-300"
                    aria-hidden="true"
                />
                <h3
                    id="official-document-fingerprint"
                    class="text-sm font-semibold"
                >
                    Sidik digital SHA-256
                </h3>
            </div>
            <Button
                type="button"
                size="sm"
                variant="outline"
                class="min-h-9 border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800 hover:text-white"
                :disabled="!isSupported"
                :aria-label="
                    copied
                        ? 'Sidik digital berhasil disalin'
                        : 'Salin sidik digital SHA-256'
                "
                @click="copyFingerprint"
            >
                <Check
                    v-if="copied"
                    class="size-3.5 text-emerald-300"
                    aria-hidden="true"
                />
                <Copy v-else class="size-3.5" aria-hidden="true" />
                {{ copied ? 'Tersalin' : 'Salin' }}
            </Button>
        </div>

        <code
            class="mt-4 block rounded-xl border border-slate-800 bg-black/30 p-3 font-mono text-xs leading-6 break-all text-blue-200 sm:p-4"
            >{{ fingerprint }}</code
        >

        <p class="mt-4 flex gap-2 text-xs leading-5 text-slate-400">
            <ShieldCheck
                class="mt-0.5 size-3.5 shrink-0 text-emerald-300"
                aria-hidden="true"
            />
            Sidik digital ini dicatat saat registrasi. Nilai ini bukan enkripsi,
            tanda tangan elektronik, atau status verifikasi langsung.
        </p>
        <p class="sr-only" aria-live="polite" aria-atomic="true">
            {{ copied ? 'Sidik digital SHA-256 berhasil disalin.' : '' }}
        </p>
    </section>
</template>
