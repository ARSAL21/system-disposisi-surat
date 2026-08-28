<script setup lang="ts">
import { Monitor, TerminalSquare, TriangleAlert, UserRound } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import {
    formatPrivilegeAuditDate,
    privilegeAuditSourceClass,
    privilegeAuditSourceLabel,
    privilegeAuditTargetLabels,
} from '@/lib/privilegeAuditPresentation';
import type { PrivilegeAuditRecord } from '@/types';

defineProps<{ audit: PrivilegeAuditRecord }>();
</script>

<template>
    <div class="grid gap-3 sm:grid-cols-2">
        <section
            class="rounded-2xl border bg-slate-50/60 p-4 dark:bg-slate-900/35"
        >
            <p
                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
            >
                Actor
            </p>
            <div class="mt-3 flex items-center gap-3">
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-200"
                >
                    <TerminalSquare
                        v-if="audit.actor.kind === 'system'"
                        class="size-5"
                        aria-hidden="true"
                    />
                    <UserRound v-else class="size-5" aria-hidden="true" />
                </span>
                <div class="min-w-0">
                    <p class="truncate font-semibold">{{ audit.actor.name }}</p>
                    <p
                        v-if="audit.actor.email"
                        class="truncate text-sm text-muted-foreground"
                    >
                        {{ audit.actor.email }}
                    </p>
                    <p v-else class="text-sm text-muted-foreground">
                        Trusted console operation
                    </p>
                </div>
            </div>
        </section>

        <section
            class="rounded-2xl border bg-slate-50/60 p-4 dark:bg-slate-900/35"
        >
            <p
                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
            >
                Target
            </p>
            <div class="mt-3">
                <p class="flex items-center gap-2 font-semibold">
                    {{ audit.target.label }}
                    <TriangleAlert
                        v-if="!audit.target.exists"
                        class="size-4 text-amber-600"
                        aria-label="Resource telah dihapus"
                    />
                </p>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ privilegeAuditTargetLabels[audit.target.type] }}
                    <template v-if="audit.target.id"
                        >· #{{ audit.target.id }}</template
                    >
                </p>
                <p
                    v-if="audit.target.secondary"
                    class="mt-1 text-sm text-muted-foreground"
                >
                    {{ audit.target.secondary }}
                </p>
            </div>
        </section>
    </div>

    <div
        class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border px-4 py-3"
    >
        <p class="text-sm font-medium tabular-nums">
            {{ formatPrivilegeAuditDate(audit.created_at) }}
        </p>
        <Badge
            variant="outline"
            :class="privilegeAuditSourceClass(audit.source)"
        >
            <TerminalSquare
                v-if="audit.source === 'console'"
                class="size-3"
                aria-hidden="true"
            />
            <Monitor v-else class="size-3" aria-hidden="true" />
            {{ privilegeAuditSourceLabel(audit.source) }}
        </Badge>
    </div>
</template>
