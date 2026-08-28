<script setup lang="ts">
import { Eye, Globe2, History, TerminalSquare } from '@lucide/vue';
import { Card, CardContent } from '@/components/ui/card';
import type { PrivilegeAuditSummary } from '@/types';

defineProps<{
    summary: PrivilegeAuditSummary;
}>();

const statistics = [
    {
        key: 'total' as const,
        label: 'Catatan ditampilkan',
        icon: Eye,
        class: 'bg-violet-500/10 text-violet-700 dark:text-violet-300',
    },
    {
        key: 'web' as const,
        label: 'Perubahan dari web',
        icon: Globe2,
        class: 'bg-blue-500/10 text-blue-700 dark:text-blue-300',
    },
    {
        key: 'console' as const,
        label: 'Operasi console',
        icon: TerminalSquare,
        class: 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-300',
    },
];
</script>

<template>
    <header class="space-y-5" aria-labelledby="privilege-audit-title">
        <div
            class="relative overflow-hidden rounded-3xl border border-indigo-100 bg-gradient-to-br from-white via-blue-50/70 to-violet-50 p-6 shadow-sm sm:p-8 dark:border-indigo-950 dark:from-slate-950 dark:via-blue-950/35 dark:to-violet-950/30"
        >
            <div
                aria-hidden="true"
                class="absolute -top-24 -right-14 size-56 rounded-full bg-violet-500/10 blur-3xl"
            />
            <div class="relative max-w-3xl">
                <div class="mb-5 flex items-center gap-3">
                    <span
                        class="flex size-12 items-center justify-center rounded-2xl bg-indigo-600 text-white shadow-sm shadow-indigo-600/20"
                    >
                        <History class="size-6" aria-hidden="true" />
                    </span>
                </div>
                <p
                    class="mb-2 text-sm font-semibold tracking-wide text-indigo-700 uppercase dark:text-indigo-300"
                >
                    Jejak perubahan akses
                </p>
                <h1
                    id="privilege-audit-title"
                    class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl dark:text-white"
                >
                    Audit Perubahan Privilege
                </h1>
                <p
                    class="mt-3 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300"
                >
                    Telusuri provisioning akun, perubahan role, dan permission
                    melalui catatan yang bersifat read-only dan dapat diaudit.
                </p>
            </div>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <Card
                v-for="statistic in statistics"
                :key="statistic.key"
                class="border-slate-200/80 py-0 shadow-none dark:border-slate-800"
            >
                <CardContent class="flex items-center gap-4 p-4 sm:p-5">
                    <span
                        :class="[
                            'flex size-11 shrink-0 items-center justify-center rounded-2xl',
                            statistic.class,
                        ]"
                    >
                        <component
                            :is="statistic.icon"
                            class="size-5"
                            aria-hidden="true"
                        />
                    </span>
                    <div>
                        <p
                            class="text-2xl font-semibold text-slate-950 tabular-nums dark:text-white"
                        >
                            {{ summary[statistic.key] }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ statistic.label }}
                        </p>
                    </div>
                </CardContent>
            </Card>
        </div>
    </header>
</template>
