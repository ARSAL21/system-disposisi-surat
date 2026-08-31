<script setup lang="ts">
import {
    Briefcase,
    Building2,
    ChevronDown,
    Crown,
    ExternalLink,
    FileInput,
    GitFork,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import type {
    OrganizationTreeNode,
    OrganizationTreePosition,
} from '@/types';

defineProps<{
    node: OrganizationTreeNode;
    depth: number;
    searchQuery: string;
    viewMode: 'all' | 'units_only' | 'occupied_only' | 'vacant_only';
    collapsedUnitIds: Set<number>;
}>();

const emit = defineEmits<{
    inspectUnit: [unit: OrganizationTreeNode];
    inspectPosition: [position: OrganizationTreePosition];
    toggleCollapse: [unitId: number];
}>();

function getLevelBadgeClass(code: string): string {
    switch (code) {
        case 'EXECUTIVE_ENTRY':
            return 'bg-amber-500/10 text-amber-700 border-amber-500/20 dark:bg-amber-400/10 dark:text-amber-300 dark:border-amber-400/20';
        case 'ASSISTANT':
            return 'bg-indigo-500/10 text-indigo-700 border-indigo-500/20 dark:bg-indigo-400/10 dark:text-indigo-300 dark:border-indigo-400/20';
        case 'SECTION_HEAD':
            return 'bg-emerald-500/10 text-emerald-700 border-emerald-500/20 dark:bg-emerald-400/10 dark:text-emerald-300 dark:border-emerald-400/20';
        case 'GENERAL_AFFAIRS':
            return 'bg-sky-500/10 text-sky-700 border-sky-500/20 dark:bg-sky-400/10 dark:text-sky-300 dark:border-sky-400/20';
        default:
            return 'bg-slate-500/10 text-slate-700 border-slate-500/20 dark:bg-slate-400/10 dark:text-slate-300 dark:border-slate-400/20';
    }
}

function getLevelIcon(code: string) {
    switch (code) {
        case 'EXECUTIVE_ENTRY':
            return Crown;
        case 'ASSISTANT':
            return GitFork;
        case 'SECTION_HEAD':
            return Briefcase;
        case 'GENERAL_AFFAIRS':
            return FileInput;
        default:
            return Building2;
    }
}

function isUnitMatched(unit: OrganizationTreeNode, query: string): boolean {
    if (!query) {
return true;
}

    const q = query.toLowerCase();
    const matchName = unit.name.toLowerCase().includes(q);
    const matchCode = unit.code ? unit.code.toLowerCase().includes(q) : false;
    const matchPosition = unit.positions.some(
        (p) =>
            p.name.toLowerCase().includes(q) ||
            p.code.toLowerCase().includes(q) ||
            (p.active_assignment &&
                p.active_assignment.user.name.toLowerCase().includes(q)),
    );

    return matchName || matchCode || matchPosition;
}

function isPositionMatched(pos: OrganizationTreePosition, query: string): boolean {
    if (!query) {
return true;
}

    const q = query.toLowerCase();

    return (
        pos.name.toLowerCase().includes(q) ||
        pos.code.toLowerCase().includes(q) ||
        (pos.active_assignment?.user.name.toLowerCase().includes(q) ?? false)
    );
}

function filterPositions(
    positions: OrganizationTreePosition[],
    mode: string,
): OrganizationTreePosition[] {
    if (mode === 'units_only') {
return [];
}

    if (mode === 'occupied_only') {
        return positions.filter((p) => p.active_assignment !== null);
    }

    if (mode === 'vacant_only') {
        return positions.filter((p) => p.active_assignment === null);
    }

    return positions;
}
</script>

<template>
    <div class="flex flex-col items-center">
        <!-- Node Card for Current Unit / Bagian -->
        <div
            class="group relative rounded-2xl border bg-card p-4 shadow-md transition-all duration-300 hover:-translate-y-1 hover:shadow-xl dark:bg-slate-900"
            :class="[
                depth === 0 ? 'w-80 border-indigo-500/40' : 'w-72 border-border/80',
                isUnitMatched(node, searchQuery) && searchQuery
                    ? 'border-indigo-500 ring-2 ring-indigo-500/50'
                    : 'hover:border-indigo-500/60',
            ]"
        >
            <!-- Top Gradient Bar -->
            <div
                class="absolute inset-x-0 top-0 h-1 rounded-t-2xl"
                :class="[
                    depth === 0
                        ? 'bg-gradient-to-r from-indigo-500 via-teal-500 to-emerald-500'
                        : depth === 1
                          ? 'bg-gradient-to-r from-sky-500 to-indigo-500'
                          : 'bg-gradient-to-r from-violet-500 to-purple-600',
                ]"
            />

            <!-- Header Badge & Status -->
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-2 truncate">
                    <div
                        class="flex size-7.5 items-center justify-center rounded-xl font-bold text-xs"
                        :class="[
                            depth === 0
                                ? 'bg-indigo-500/15 text-indigo-700 dark:text-indigo-300'
                                : 'bg-muted text-muted-foreground',
                        ]"
                    >
                        <Building2 class="size-4" />
                    </div>
                    <div class="truncate">
                        <span
                            v-if="node.code"
                            class="font-mono text-[10px] font-bold text-muted-foreground uppercase"
                        >
                            {{ node.code }}
                        </span>
                        <span
                            v-else
                            class="text-[10px] font-semibold text-muted-foreground"
                        >
                            {{ depth === 0 ? 'Unit Induk' : 'Bagian / Sub-unit' }}
                        </span>
                    </div>
                </div>

                <Badge
                    :variant="node.is_active ? 'outline' : 'secondary'"
                    class="shrink-0 text-[9px] px-1.5 py-0"
                >
                    {{ node.is_active ? 'Aktif' : 'Nonaktif' }}
                </Badge>
            </div>

            <!-- Title of Unit / Bagian -->
            <div class="mt-2">
                <h4
                    class="font-bold text-foreground transition-colors group-hover:text-indigo-600 dark:group-hover:text-indigo-400"
                    :class="depth === 0 ? 'text-sm' : 'text-xs'"
                >
                    {{ node.name }}
                </h4>
            </div>

            <!-- Positions inside this Unit / Bagian -->
            <div
                v-if="filterPositions(node.positions, viewMode).length > 0"
                class="mt-3 space-y-1.5 border-t border-border/50 pt-2.5"
            >
                <div class="flex items-center justify-between text-[10px] font-semibold text-muted-foreground">
                    <span>Jabatan ({{ node.positions.length }})</span>
                </div>

                <div
                    v-for="pos in filterPositions(node.positions, viewMode)"
                    :key="pos.id"
                    class="flex cursor-pointer items-center justify-between gap-2 rounded-xl border border-border/50 bg-muted/30 p-2 text-xs transition-colors hover:bg-muted/70"
                    :class="{
                        'border-indigo-500 bg-indigo-50/50 dark:bg-indigo-950/40':
                            isPositionMatched(pos, searchQuery) && searchQuery,
                    }"
                    @click="emit('inspectPosition', pos)"
                >
                    <div class="flex items-center gap-2 truncate">
                        <component
                            :is="getLevelIcon(pos.level.code)"
                            class="size-3.5 shrink-0 text-indigo-600 dark:text-indigo-400"
                        />
                        <div class="truncate">
                            <p class="truncate font-semibold text-foreground text-[11px]">
                                {{ pos.name }}
                            </p>
                            <p class="truncate text-[10px] text-muted-foreground">
                                {{
                                    pos.active_assignment
                                        ? pos.active_assignment.user.name
                                        : 'Lowong'
                                }}
                            </p>
                        </div>
                    </div>

                    <Badge
                        variant="outline"
                        class="shrink-0 text-[8px] px-1.5 py-0 font-medium"
                        :class="getLevelBadgeClass(pos.level.code)"
                    >
                        {{ pos.level.name.split(' ')[0] }}
                    </Badge>
                </div>
            </div>

            <!-- Card Footer: Inspector & Collapse Button -->
            <div class="mt-3.5 flex items-center justify-between border-t border-border/60 pt-2 text-[11px]">
                <button
                    type="button"
                    class="inline-flex items-center gap-1 font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                    @click="emit('inspectUnit', node)"
                >
                    <span>Rincian</span>
                    <ExternalLink class="size-3" />
                </button>

                <button
                    v-if="node.children && node.children.length > 0"
                    type="button"
                    class="inline-flex items-center gap-1 rounded-lg bg-muted px-2 py-0.5 text-[10px] font-semibold text-foreground transition-colors hover:bg-muted-foreground/20"
                    @click="emit('toggleCollapse', node.id)"
                >
                    <span>{{ node.children.length }} Sub-unit</span>
                    <ChevronDown
                        class="size-3 transition-transform"
                        :class="{ '-rotate-90': collapsedUnitIds.has(node.id) }"
                    />
                </button>
            </div>
        </div>

        <!-- Recursive Children Tree Branches -->
        <div
            v-if="node.children && node.children.length > 0 && !collapsedUnitIds.has(node.id)"
            class="flex flex-col items-center"
        >
            <!-- Vertical Line Down -->
            <div class="h-6 w-0.5 bg-indigo-500/40" />

            <!-- Row of Child Nodes -->
            <div class="relative flex flex-wrap justify-center gap-6 pt-2">
                <OrganizationTreeNodeBranch
                    v-for="child in node.children"
                    :key="child.id"
                    :node="child"
                    :depth="depth + 1"
                    :search-query="searchQuery"
                    :view-mode="viewMode"
                    :collapsed-unit-ids="collapsedUnitIds"
                    @inspect-unit="emit('inspectUnit', $event)"
                    @inspect-position="emit('inspectPosition', $event)"
                    @toggle-collapse="emit('toggleCollapse', $event)"
                />
            </div>
        </div>
    </div>
</template>
