<script setup lang="ts">
import { Eye, Monitor, TerminalSquare, TriangleAlert } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    formatPrivilegeAuditDate,
    privilegeAuditActionClass,
    privilegeAuditActionLabels,
    privilegeAuditSourceClass,
    privilegeAuditSourceLabel,
    privilegeAuditTargetLabels,
} from '@/lib/privilegeAuditPresentation';
import type { PrivilegeAuditRecord } from '@/types';

defineProps<{ audits: PrivilegeAuditRecord[] }>();
defineEmits<{ detail: [audit: PrivilegeAuditRecord] }>();
</script>

<template>
    <div class="grid gap-3 p-3 lg:hidden">
        <article
            v-for="audit in audits"
            :key="audit.id"
            class="rounded-2xl border bg-card p-4 shadow-xs"
        >
            <div class="flex flex-wrap items-center justify-between gap-2">
                <Badge
                    variant="outline"
                    :class="privilegeAuditActionClass(audit.action)"
                >
                    {{ privilegeAuditActionLabels[audit.action] }}
                </Badge>
                <span class="text-xs text-muted-foreground tabular-nums">
                    #{{ audit.id }}
                </span>
            </div>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <p class="text-xs font-medium text-muted-foreground">
                        Actor
                    </p>
                    <p class="mt-1 font-semibold">{{ audit.actor.name }}</p>
                    <p
                        v-if="audit.actor.email"
                        class="mt-1 text-sm break-all text-muted-foreground"
                    >
                        {{ audit.actor.email }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium text-muted-foreground">
                        Target
                    </p>
                    <p class="mt-1 flex items-center gap-2 font-semibold">
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
                </div>
            </div>

            <div
                class="mt-4 flex flex-wrap items-center justify-between gap-3 border-t pt-4"
            >
                <div class="space-y-2">
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
                <Button
                    type="button"
                    variant="outline"
                    class="min-h-11"
                    @click="$emit('detail', audit)"
                >
                    <Eye class="size-4" aria-hidden="true" />
                    Lihat detail
                </Button>
            </div>
        </article>
    </div>
</template>
