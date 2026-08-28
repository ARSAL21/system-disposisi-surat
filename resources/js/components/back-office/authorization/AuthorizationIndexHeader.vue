<script setup lang="ts">
import { BadgeCheck, KeyRound, ShieldCheck, UserRoundCog } from '@lucide/vue';
import { Card, CardContent } from '@/components/ui/card';
import type { AuthorizationSummary } from '@/types';

defineProps<{ summary: AuthorizationSummary }>();

const statistics = [
    {
        key: 'custom_roles' as const,
        label: 'Custom role',
        icon: ShieldCheck,
        iconClass: 'bg-blue-500/10 text-blue-700 dark:text-blue-300',
    },
    {
        key: 'permissions' as const,
        label: 'Permission resmi',
        icon: KeyRound,
        iconClass: 'bg-violet-500/10 text-violet-700 dark:text-violet-300',
    },
    {
        key: 'internal_users' as const,
        label: 'Akun internal',
        icon: UserRoundCog,
        iconClass: 'bg-cyan-500/10 text-cyan-700 dark:text-cyan-300',
    },
];
</script>

<template>
    <header class="space-y-6" aria-labelledby="authorization-title">
        <div
            class="relative overflow-hidden rounded-3xl border border-blue-100 bg-gradient-to-br from-white via-blue-50/70 to-violet-50 p-6 shadow-sm sm:p-8 dark:border-blue-950 dark:from-slate-950 dark:via-blue-950/35 dark:to-violet-950/30"
        >
            <div
                aria-hidden="true"
                class="absolute -top-20 -right-16 size-52 rounded-full bg-violet-400/10 blur-3xl"
            />
            <div class="relative max-w-3xl">
                <div
                    class="mb-5 flex size-12 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-sm shadow-blue-600/20"
                >
                    <BadgeCheck class="size-6" aria-hidden="true" />
                </div>
                <p
                    class="mb-2 text-sm font-semibold tracking-wide text-blue-700 uppercase dark:text-blue-300"
                >
                    Kontrol akses internal
                </p>
                <h1
                    id="authorization-title"
                    class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-3xl dark:text-white"
                >
                    Manage Role
                </h1>
                <p
                    class="mt-3 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300"
                >
                    Atur capability teknis melalui role yang eksplisit. Position
                    dan kewenangan workflow tetap dikelola sebagai domain yang
                    terpisah.
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
                            statistic.iconClass,
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
