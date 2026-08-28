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
    <div class="hidden overflow-x-auto lg:block">
        <table class="w-full min-w-5xl text-left text-sm">
            <caption class="sr-only">
                Catatan perubahan privilege akun internal, role, dan permission
            </caption>
            <thead class="border-b bg-slate-50/70 dark:bg-slate-900/50">
                <tr
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    <th scope="col" class="px-5 py-3.5">Waktu</th>
                    <th scope="col" class="px-5 py-3.5">Perubahan</th>
                    <th scope="col" class="px-5 py-3.5">Actor</th>
                    <th scope="col" class="px-5 py-3.5">Target</th>
                    <th scope="col" class="px-5 py-3.5">Sumber</th>
                    <th scope="col" class="px-5 py-3.5 text-right">Detail</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr
                    v-for="audit in audits"
                    :key="audit.id"
                    class="transition-colors duration-200 hover:bg-indigo-50/35 motion-reduce:transition-none dark:hover:bg-indigo-950/15"
                >
                    <td class="px-5 py-4 align-top whitespace-nowrap">
                        <p class="font-medium tabular-nums">
                            {{ formatPrivilegeAuditDate(audit.created_at) }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Audit #{{ audit.id }}
                        </p>
                    </td>
                    <td class="px-5 py-4 align-top">
                        <Badge
                            variant="outline"
                            :class="privilegeAuditActionClass(audit.action)"
                        >
                            {{ privilegeAuditActionLabels[audit.action] }}
                        </Badge>
                        <p
                            v-if="audit.change"
                            class="mt-2 max-w-60 text-xs break-words text-muted-foreground"
                        >
                            {{ audit.change.replaceAll('_', ' ') }}
                        </p>
                    </td>
                    <td class="px-5 py-4 align-top">
                        <div class="flex min-w-44 items-center gap-3">
                            <span
                                class="flex size-9 shrink-0 items-center justify-center rounded-full bg-blue-100 font-semibold text-blue-700 dark:bg-blue-950 dark:text-blue-200"
                                aria-hidden="true"
                            >
                                {{ audit.actor.name.slice(0, 1).toUpperCase() }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate font-semibold">
                                    {{ audit.actor.name }}
                                </p>
                                <p
                                    v-if="audit.actor.email"
                                    class="mt-0.5 truncate text-xs text-muted-foreground"
                                >
                                    {{ audit.actor.email }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4 align-top">
                        <div class="min-w-44">
                            <p class="flex items-center gap-2 font-semibold">
                                {{ audit.target.label }}
                                <TriangleAlert
                                    v-if="!audit.target.exists"
                                    class="size-4 text-amber-600"
                                    aria-label="Resource telah dihapus"
                                />
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{
                                    privilegeAuditTargetLabels[
                                        audit.target.type
                                    ]
                                }}
                                <template v-if="audit.target.id">
                                    · #{{ audit.target.id }}
                                </template>
                            </p>
                        </div>
                    </td>
                    <td class="px-5 py-4 align-top">
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
                    </td>
                    <td class="px-5 py-4 text-right align-top">
                        <Button
                            type="button"
                            variant="outline"
                            class="min-h-11"
                            :aria-label="`Lihat detail audit ${audit.id}`"
                            @click="$emit('detail', audit)"
                        >
                            <Eye class="size-4" aria-hidden="true" />
                            Lihat
                        </Button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
