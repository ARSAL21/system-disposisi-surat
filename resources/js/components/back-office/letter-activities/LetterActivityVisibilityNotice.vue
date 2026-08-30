<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Eye, EyeOff, ShieldCheck } from '@lucide/vue';
import type { LetterActivityVisibility } from '@/types';

defineProps<{
    visibility: LetterActivityVisibility;
    preview: boolean;
}>();
</script>

<template>
    <section
        :class="[
            'flex flex-col gap-4 rounded-2xl border p-4 sm:flex-row sm:items-center sm:justify-between',
            visibility === 'details'
                ? 'border-blue-200 bg-blue-50/70 dark:border-blue-900 dark:bg-blue-950/30'
                : 'border-amber-200 bg-amber-50/70 dark:border-amber-900 dark:bg-amber-950/30',
        ]"
        aria-label="Tingkat visibilitas aktivitas"
    >
        <div class="flex gap-3">
            <span
                :class="[
                    'flex size-10 shrink-0 items-center justify-center rounded-xl',
                    visibility === 'details'
                        ? 'bg-blue-600 text-white'
                        : 'bg-amber-500 text-white',
                ]"
            >
                <ShieldCheck
                    v-if="visibility === 'details'"
                    class="size-5"
                    aria-hidden="true"
                />
                <EyeOff v-else class="size-5" aria-hidden="true" />
            </span>
            <div>
                <h2 class="font-semibold text-slate-950 dark:text-white">
                    {{
                        visibility === 'details'
                            ? 'Tampilan operasional berwenang'
                            : 'Ringkasan administrasi tersanitasi'
                    }}
                </h2>
                <p
                    class="mt-1 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300"
                >
                    <template v-if="visibility === 'details'">
                        Identitas surat dan rincian perubahan hanya tersedia
                        bagi pejabat yang memiliki kewenangan bisnis aktif.
                    </template>
                    <template v-else>
                        Identitas surat, pengirim, isi perubahan, dokumen, dan
                        jejak teknis tidak ditampilkan kepada administrator
                        teknis.
                    </template>
                </p>
            </div>
        </div>

        <Link
            v-if="preview"
            :href="
                visibility === 'details'
                    ? '/back-office/previews/letter-activities/summary'
                    : '/back-office/previews/letter-activities'
            "
            class="inline-flex min-h-11 shrink-0 items-center justify-center gap-2 rounded-xl border border-current/20 bg-white/70 px-4 text-sm font-medium text-slate-700 transition-colors hover:bg-white focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 focus-visible:outline-none dark:bg-slate-950/50 dark:text-slate-200 dark:hover:bg-slate-950"
        >
            <Eye class="size-4" aria-hidden="true" />
            {{
                visibility === 'details'
                    ? 'Lihat simulasi admin'
                    : 'Lihat simulasi pejabat'
            }}
        </Link>
    </section>
</template>
