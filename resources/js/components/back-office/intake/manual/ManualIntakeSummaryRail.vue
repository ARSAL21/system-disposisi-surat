<script setup lang="ts">
import {
    Building2,
    Check,
    Circle,
    Clock3,
    FileCheck2,
    Send,
    ShieldCheck,
} from '@lucide/vue';
import { computed } from 'vue';
import type { ManualIntakeFormPayload, ManualIntakeStep } from '@/types';

const props = defineProps<{
    form: ManualIntakeFormPayload;
    currentStep: ManualIntakeStep;
    hasExistingDocument?: boolean;
}>();

const checks = computed(() => [
    {
        label: 'Identitas pengirim',
        complete: Boolean(
            props.form.sender_organization_name.trim() &&
            props.form.contact_name.trim() &&
            props.form.received_at,
        ),
    },
    {
        label: 'Informasi surat',
        complete: Boolean(props.form.subject.trim()),
    },
    {
        label: 'Scan PDF',
        complete: props.form.document !== null || props.hasExistingDocument,
    },
    {
        label: 'Pemeriksaan lengkap',
        complete: props.form.checklist.every((item) => item.checked),
    },
]);

const completeCount = computed(
    () => checks.value.filter((item) => item.complete).length,
);
const readiness = computed(() =>
    Math.round((completeCount.value / checks.value.length) * 100),
);
const receivedAtLabel = computed(() => {
    if (!props.form.received_at) {
        return 'Belum ditentukan';
    }

    const date = new Date(`${props.form.received_at}:00+08:00`);

    if (Number.isNaN(date.getTime())) {
        return props.form.received_at;
    }

    return new Intl.DateTimeFormat('id-ID', {
        dateStyle: 'medium',
        timeStyle: 'short',
        timeZone: 'Asia/Makassar',
    }).format(date);
});
</script>

<template>
    <aside
        class="space-y-4 lg:sticky lg:top-6"
        aria-label="Ringkasan pencatatan"
    >
        <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
            <div
                class="border-b bg-emerald-950 p-5 text-white dark:bg-emerald-950"
            >
                <div class="flex items-center justify-between gap-4">
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-white/10"
                    >
                        <FileCheck2 class="size-5" aria-hidden="true" />
                    </span>
                    <span class="text-2xl font-semibold tabular-nums">
                        {{ readiness }}%
                    </span>
                </div>
                <h2 class="mt-4 font-semibold">Kesiapan pencatatan</h2>
                <p class="mt-1 text-xs leading-5 text-emerald-100/80">
                    Tahap {{ currentStep }} dari 3 · {{ completeCount }} dari
                    {{ checks.length }} kelompok lengkap
                </p>
                <div
                    class="mt-4 h-2 overflow-hidden rounded-full bg-white/15"
                    role="progressbar"
                    :aria-valuenow="readiness"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-label="Kesiapan pencatatan surat"
                >
                    <div
                        class="h-full rounded-full bg-amber-300 transition-[width] duration-300 motion-reduce:transition-none"
                        :style="{ width: `${readiness}%` }"
                    />
                </div>
            </div>

            <div class="space-y-3 p-5">
                <div
                    v-for="item in checks"
                    :key="item.label"
                    class="flex items-center gap-3 text-sm"
                >
                    <span
                        :class="[
                            'flex size-6 shrink-0 items-center justify-center rounded-full border',
                            item.complete
                                ? 'border-emerald-600 bg-emerald-600 text-white'
                                : 'border-border bg-muted/40 text-muted-foreground',
                        ]"
                    >
                        <Check
                            v-if="item.complete"
                            class="size-3.5"
                            aria-hidden="true"
                        />
                        <Circle v-else class="size-2.5" aria-hidden="true" />
                    </span>
                    <span
                        :class="
                            item.complete
                                ? 'font-medium'
                                : 'text-muted-foreground'
                        "
                    >
                        {{ item.label }}
                    </span>
                </div>
            </div>
        </section>

        <section class="rounded-2xl border bg-card p-5 shadow-xs">
            <div class="flex items-start gap-3">
                <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300"
                >
                    <Building2 class="size-4" aria-hidden="true" />
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold">Ringkasan surat</h2>
                    <p
                        class="mt-1 truncate text-xs text-muted-foreground"
                        :title="form.sender_organization_name"
                    >
                        {{
                            form.sender_organization_name ||
                            'Instansi belum diisi'
                        }}
                    </p>
                </div>
            </div>
            <dl class="mt-4 grid gap-3 text-sm">
                <div class="rounded-xl bg-muted/45 p-3">
                    <dt class="text-xs text-muted-foreground">Perihal</dt>
                    <dd class="mt-1 line-clamp-2 font-medium">
                        {{ form.subject || 'Belum diisi' }}
                    </dd>
                </div>
                <div class="flex items-center gap-3 rounded-xl bg-muted/45 p-3">
                    <Clock3
                        class="size-4 shrink-0 text-emerald-700 dark:text-emerald-300"
                        aria-hidden="true"
                    />
                    <div>
                        <dt class="text-xs text-muted-foreground">Diterima</dt>
                        <dd class="mt-0.5 font-medium tabular-nums">
                            {{ receivedAtLabel }} WITA
                        </dd>
                    </div>
                </div>
            </dl>
        </section>

        <section
            class="rounded-2xl border border-blue-200 bg-blue-50/70 p-5 dark:border-blue-900 dark:bg-blue-950/25"
        >
            <div class="flex gap-3">
                <ShieldCheck
                    class="mt-0.5 size-5 shrink-0 text-blue-700 dark:text-blue-300"
                    aria-hidden="true"
                />
                <div>
                    <h2
                        class="text-sm font-semibold text-blue-950 dark:text-blue-100"
                    >
                        Setelah disimpan
                    </h2>
                    <ol
                        class="mt-3 space-y-3 text-xs leading-5 text-blue-900/75 dark:text-blue-100/75"
                    >
                        <li class="flex gap-2">
                            <Send
                                class="mt-0.5 size-3.5 shrink-0"
                                aria-hidden="true"
                            />
                            Masuk antrean persetujuan Kabag Umum.
                        </li>
                        <li class="flex gap-2">
                            <FileCheck2
                                class="mt-0.5 size-3.5 shrink-0"
                                aria-hidden="true"
                            />
                            Nomor agenda diberikan saat registrasi resmi.
                        </li>
                    </ol>
                </div>
            </div>
        </section>
    </aside>
</template>
