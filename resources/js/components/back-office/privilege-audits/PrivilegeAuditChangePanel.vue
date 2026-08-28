<script setup lang="ts">
import { ArrowDownToLine, ArrowUpFromLine } from '@lucide/vue';
import {
    formatPrivilegeAuditField,
    formatPrivilegeAuditValue,
} from '@/lib/privilegeAuditPresentation';
import type { PrivilegeAuditChangeSet } from '@/types';

defineProps<{
    title: string;
    changes: PrivilegeAuditChangeSet | null;
    tone: 'before' | 'after';
}>();
</script>

<template>
    <section
        class="overflow-hidden rounded-2xl border"
        :class="
            tone === 'before'
                ? 'border-slate-200 dark:border-slate-800'
                : 'border-indigo-200 dark:border-indigo-900'
        "
    >
        <header
            class="flex items-center gap-2 border-b px-4 py-3 text-sm font-semibold"
            :class="
                tone === 'before'
                    ? 'bg-slate-50 dark:bg-slate-900/60'
                    : 'bg-indigo-50 text-indigo-900 dark:bg-indigo-950/35 dark:text-indigo-100'
            "
        >
            <ArrowUpFromLine
                v-if="tone === 'before'"
                class="size-4"
                aria-hidden="true"
            />
            <ArrowDownToLine v-else class="size-4" aria-hidden="true" />
            {{ title }}
        </header>
        <dl v-if="changes && Object.keys(changes).length" class="divide-y">
            <div
                v-for="(value, field) in changes"
                :key="field"
                class="grid gap-1 px-4 py-3 sm:grid-cols-[9rem_minmax(0,1fr)] sm:gap-4"
            >
                <dt class="text-xs font-medium text-muted-foreground">
                    {{ formatPrivilegeAuditField(field) }}
                </dt>
                <dd class="text-sm break-words sm:text-right">
                    {{ formatPrivilegeAuditValue(value) }}
                </dd>
            </div>
        </dl>
        <p v-else class="px-4 py-8 text-center text-sm text-muted-foreground">
            Tidak ada nilai {{ tone === 'before' ? 'sebelumnya' : 'baru' }}.
        </p>
    </section>
</template>
