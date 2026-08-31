<script setup lang="ts">
import { Fingerprint } from '@lucide/vue';
import type { LetterActivityRecord } from '@/types';

defineProps<{
    activity: LetterActivityRecord;
}>();
</script>

<template>
    <section
        v-if="activity.document"
        class="rounded-2xl border border-violet-200 bg-violet-50/70 p-4 dark:border-violet-900 dark:bg-violet-950/30"
    >
        <h3 class="flex items-center gap-2 text-sm font-semibold">
            <Fingerprint
                class="size-4 text-violet-700 dark:text-violet-300"
                aria-hidden="true"
            />
            Integritas dokumen resmi
        </h3>
        <dl class="mt-3 grid gap-3 sm:grid-cols-[9rem_1fr]">
            <dt class="text-sm text-muted-foreground">Versi</dt>
            <dd class="text-sm font-medium">
                {{ activity.document.version_number }}
            </dd>
            <dt class="text-sm text-muted-foreground">SHA-256</dt>
            <dd class="font-mono text-xs leading-5 break-all">
                {{ activity.document.sha256 }}
            </dd>
        </dl>
    </section>

    <section class="rounded-2xl border p-4">
        <h3 class="text-sm font-semibold">Jejak teknis</h3>
        <dl class="mt-3 grid gap-x-4 gap-y-3 text-sm sm:grid-cols-[8rem_1fr]">
            <dt class="text-muted-foreground">Request ID</dt>
            <dd class="font-mono text-xs break-all">
                {{ activity.request_id ?? 'Tidak tersedia' }}
            </dd>
            <dt class="text-muted-foreground">Alamat IP</dt>
            <dd>{{ activity.ip_address ?? 'Tidak tersedia' }}</dd>
            <dt class="text-muted-foreground">Perangkat</dt>
            <dd>{{ activity.user_agent ?? 'Tidak tersedia' }}</dd>
        </dl>
    </section>
</template>
