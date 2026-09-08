<script setup lang="ts">
import {
    Briefcase,
    ChevronDown,
    ExternalLink,
    GitFork,
    GripVertical,
    Pencil,
    Plus,
    UserCheck,
    UserX,
} from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { OrganizationTreeNode, OrganizationTreePosition } from '@/types';

const props = defineProps<{
    node: OrganizationTreeNode;
    pillarIndex: number;
    searchQuery: string;
    viewMode: 'all' | 'units_only' | 'occupied_only' | 'vacant_only';
    collapsedUnitIds: Set<number>;
    isEditMode: boolean;
    canMutate: boolean;
    chartOrientation?: 'vertical' | 'horizontal';
    draggedUnitId?: number | null;
    hoveredTargetId?: number | null;
    validDropTargetIds?: Set<number>;
}>();

const emit = defineEmits<{
    inspectUnit: [unit: OrganizationTreeNode];
    inspectPosition: [position: OrganizationTreePosition];
    toggleCollapse: [unitId: number];
    addSubUnit: [unit: OrganizationTreeNode];
    addPosition: [unit: OrganizationTreeNode];
    reparentUnit: [unit: OrganizationTreeNode];
    editUnit: [unit: OrganizationTreeNode];
    dragStart: [unit: OrganizationTreeNode, event: DragEvent];
    dragEnd: [unit: OrganizationTreeNode, event: DragEvent];
    dragOver: [unit: OrganizationTreeNode, event: DragEvent];
    dragLeave: [unit: OrganizationTreeNode, event: DragEvent];
    drop: [unit: OrganizationTreeNode, event: DragEvent];
}>();

// Color themes per pillar
const pillarTheme = computed(() => {
    switch (props.pillarIndex % 3) {
        case 0:
            return {
                border: 'border-emerald-500/40',
                headerBorder: 'border-emerald-500',
                badge: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/20',
                gradient: 'from-emerald-500 via-teal-500 to-emerald-600',
                accentText: 'text-emerald-600 dark:text-emerald-400',
                accentBg:
                    'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
                stemColor: 'bg-emerald-500/50',
                pillarBg: 'bg-emerald-500/[0.02] dark:bg-emerald-950/[0.08]',
            };
        case 1:
            return {
                border: 'border-sky-500/40',
                headerBorder: 'border-sky-500',
                badge: 'bg-sky-500/10 text-sky-700 dark:text-sky-300 border-sky-500/20',
                gradient: 'from-sky-500 via-blue-500 to-indigo-600',
                accentText: 'text-sky-600 dark:text-sky-400',
                accentBg: 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
                stemColor: 'bg-sky-500/50',
                pillarBg: 'bg-sky-500/[0.02] dark:bg-sky-950/[0.08]',
            };
        default:
            return {
                border: 'border-purple-500/40',
                headerBorder: 'border-purple-500',
                badge: 'bg-purple-500/10 text-purple-700 dark:text-purple-300 border-purple-500/20',
                gradient: 'from-purple-500 via-violet-500 to-indigo-600',
                accentText: 'text-purple-600 dark:text-purple-400',
                accentBg:
                    'bg-purple-500/10 text-purple-600 dark:text-purple-400',
                stemColor: 'bg-purple-500/50',
                pillarBg: 'bg-purple-500/[0.02] dark:bg-purple-950/[0.08]',
            };
    }
});

const primaryPosition = computed<OrganizationTreePosition | null>(() => {
    if (!props.node.positions || props.node.positions.length === 0) {
        return null;
    }

    return props.node.positions[0] ?? null;
});

function isPositionMatched(
    pos: OrganizationTreePosition,
    query: string,
): boolean {
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

function isUnitMatched(unit: OrganizationTreeNode, query: string): boolean {
    if (!query) {
        return false;
    }

    const q = query.toLowerCase();
    const matchName = unit.name.toLowerCase().includes(q);
    const matchCode = unit.code ? unit.code.toLowerCase().includes(q) : false;
    const matchPos = unit.positions.some((p) => isPositionMatched(p, query));

    return matchName || matchCode || matchPos;
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

function isUnitDragged(unitId: number): boolean {
    return props.draggedUnitId === unitId;
}

function isUnitDropTarget(unitId: number): boolean {
    return props.hoveredTargetId === unitId;
}

function isUnitDropValid(unitId: number): boolean {
    return props.validDropTargetIds?.has(unitId) ?? false;
}
</script>

<template>
    <div
        class="rounded-3xl border border-border/80 p-4 shadow-sm transition-all duration-300"
        :class="[
            pillarTheme.pillarBg,
            chartOrientation === 'horizontal'
                ? 'flex w-auto min-w-[500px] flex-row items-start gap-4'
                : 'flex w-full max-w-sm flex-col items-center',
        ]"
    >
        <!-- ======================================================== -->
        <!-- 1. ASSISTANT HEADER CARD                                -->
        <!-- ======================================================== -->
        <div
            :draggable="canMutate"
            class="group relative rounded-2xl border bg-card p-4 shadow-md transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg dark:bg-slate-900"
            :class="[
                chartOrientation === 'horizontal' ? 'w-80 shrink-0' : 'w-full',
                pillarTheme.border,
                isUnitMatched(node, searchQuery) && searchQuery
                    ? 'ring-2 ring-indigo-500'
                    : '',
                isUnitDragged(node.id)
                    ? 'scale-95 border-dashed opacity-40'
                    : '',
                isUnitDropTarget(node.id) && isUnitDropValid(node.id)
                    ? 'scale-102 border-emerald-500 bg-emerald-500/10 ring-4 ring-emerald-500'
                    : '',
                isUnitDropTarget(node.id) && !isUnitDropValid(node.id)
                    ? 'cursor-not-allowed border-rose-500 opacity-80 ring-2 ring-rose-500'
                    : '',
            ]"
            @dragstart="emit('dragStart', node, $event)"
            @dragend="emit('dragEnd', node, $event)"
            @dragover="emit('dragOver', node, $event)"
            @dragleave="emit('dragLeave', node, $event)"
            @drop="emit('drop', node, $event)"
        >
            <!-- Drop Target Indicator Banner -->
            <div
                v-if="isUnitDropTarget(node.id)"
                class="absolute -top-3 left-1/2 z-20 flex -translate-x-1/2 items-center gap-1 rounded-full px-2.5 py-0.5 text-[9px] font-extrabold shadow-md"
                :class="
                    isUnitDropValid(node.id)
                        ? 'animate-bounce bg-emerald-600 text-white'
                        : 'bg-rose-600 text-white'
                "
            >
                <span>{{
                    isUnitDropValid(node.id)
                        ? '📥 Pindahkan ke Asisten ini'
                        : '🚫 Tidak dapat memindahkan'
                }}</span>
            </div>

            <!-- Top Gradient Bar -->
            <div
                class="absolute inset-x-0 top-0 h-1.5 rounded-t-2xl bg-gradient-to-r"
                :class="pillarTheme.gradient"
            />

            <!-- Header Row -->
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-2">
                    <!-- Drag Handle -->
                    <div
                        v-if="canMutate"
                        class="-ml-1 cursor-grab p-0.5 text-muted-foreground hover:text-foreground active:cursor-grabbing"
                        title="Tarik untuk memindahkan asisten ini"
                    >
                        <GripVertical class="size-3.5" />
                    </div>

                    <div
                        class="flex size-8 shrink-0 items-center justify-center rounded-xl font-bold shadow-xs"
                        :class="pillarTheme.accentBg"
                    >
                        <GitFork class="size-4" />
                    </div>
                    <div>
                        <span
                            class="block text-[9px] font-extrabold tracking-wider uppercase"
                            :class="pillarTheme.accentText"
                        >
                            Pilar Asisten {{ pillarIndex + 1 }}
                        </span>
                        <h4
                            class="text-xs font-bold text-foreground sm:text-sm"
                        >
                            {{ node.name }}
                        </h4>
                    </div>
                </div>

                <Badge
                    :variant="node.is_active ? 'outline' : 'secondary'"
                    class="shrink-0 px-1.5 py-0 text-[9px]"
                >
                    {{ node.is_active ? 'Aktif' : 'Nonaktif' }}
                </Badge>
            </div>

            <!-- Assistant Official Card -->
            <div
                v-if="primaryPosition"
                class="mt-3 cursor-pointer rounded-xl border border-border/60 bg-muted/30 p-2.5 text-xs transition-colors hover:bg-muted/60"
                @click="emit('inspectPosition', primaryPosition)"
            >
                <div
                    class="mb-1.5 flex items-center justify-between text-[10px] font-semibold text-muted-foreground"
                >
                    <span>{{ primaryPosition.name }}</span>
                    <Badge
                        variant="outline"
                        class="px-1 py-0 text-[8px]"
                        :class="pillarTheme.badge"
                    >
                        {{ primaryPosition.level.name }}
                    </Badge>
                </div>
                <div class="flex items-center gap-2">
                    <div
                        class="flex size-7 shrink-0 items-center justify-center rounded-lg text-xs font-bold text-white shadow-xs"
                        :class="
                            primaryPosition.active_assignment
                                ? 'bg-indigo-600'
                                : 'bg-slate-400'
                        "
                    >
                        <UserCheck
                            v-if="primaryPosition.active_assignment"
                            class="size-3.5"
                        />
                        <UserX v-else class="size-3.5" />
                    </div>
                    <div class="truncate">
                        <p
                            class="truncate text-[11px] font-semibold text-foreground"
                        >
                            {{
                                primaryPosition.active_assignment
                                    ? primaryPosition.active_assignment.user
                                          .name
                                    : 'Lowong (Belum Ditugaskan)'
                            }}
                        </p>
                        <p class="truncate text-[9px] text-muted-foreground">
                            {{
                                primaryPosition.active_assignment
                                    ? primaryPosition.active_assignment.user
                                          .email
                                    : 'Klik untuk kelola'
                            }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Bagian Count Chip -->
            <div
                class="mt-3 flex items-center justify-between border-t border-border/50 pt-2 text-[10px] text-muted-foreground"
            >
                <span class="font-medium">
                    Membawahi:
                    <strong class="text-foreground"
                        >{{ node.children?.length || 0 }} Bagian</strong
                    >
                </span>

                <button
                    type="button"
                    class="inline-flex items-center gap-1 font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                    @click="emit('inspectUnit', node)"
                >
                    <span>Rincian</span>
                    <ExternalLink class="size-2.5" />
                </button>
            </div>

            <!-- Super Admin Actions Toolbar (in Edit Mode) -->
            <div
                v-if="isEditMode && canMutate"
                class="mt-2.5 flex flex-wrap items-center gap-1 border-t border-border/50 pt-2 text-[10px]"
            >
                <Button
                    size="sm"
                    variant="outline"
                    class="h-6 gap-1 rounded-md border-indigo-500/30 px-1.5 text-[9px] text-indigo-700 dark:text-indigo-300"
                    title="Tambah bagian di bawah asisten ini"
                    @click.stop="emit('addSubUnit', node)"
                >
                    <Plus class="size-2.5" />
                    <span>+ Bagian</span>
                </Button>

                <Button
                    size="sm"
                    variant="outline"
                    class="h-6 gap-1 rounded-md border-violet-500/30 px-1.5 text-[9px] text-violet-700 dark:text-violet-300"
                    title="Pindahkan asisten ini"
                    @click.stop="emit('reparentUnit', node)"
                >
                    <GitFork class="size-2.5" />
                    <span>Pindah</span>
                </Button>

                <Button
                    size="sm"
                    variant="ghost"
                    class="h-6 gap-1 rounded-md px-1.5 text-[9px] text-muted-foreground"
                    title="Edit nama"
                    @click.stop="emit('editUnit', node)"
                >
                    <Pencil class="size-2.5" />
                    <span>Edit</span>
                </Button>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- 2. STEM LINE DOWN OR RIGHT TO SUBORDINATE BAGIAN         -->
        <!-- ======================================================== -->
        <div
            v-if="chartOrientation === 'horizontal'"
            class="my-auto h-0.5 w-6 shrink-0"
            :class="pillarTheme.stemColor"
        />
        <div v-else class="my-1 h-6 w-0.5" :class="pillarTheme.stemColor" />

        <!-- ======================================================== -->
        <!-- 3. BAGIAN-BAGIAN UNDER THIS ASSISTANT                    -->
        <!-- ======================================================== -->
        <div
            class="space-y-3"
            :class="
                chartOrientation === 'horizontal' ? 'w-80 shrink-0' : 'w-full'
            "
        >
            <div
                v-for="bagian in node.children"
                :key="bagian.id"
                :draggable="canMutate"
                class="group relative rounded-2xl border border-border/80 bg-card p-3.5 shadow-xs transition-all duration-200 hover:-translate-y-0.5 hover:border-indigo-500/50 hover:shadow-md dark:bg-slate-900"
                :class="[
                    isUnitMatched(bagian, searchQuery) && searchQuery
                        ? 'border-indigo-500 ring-2 ring-indigo-500/50'
                        : '',
                    isUnitDragged(bagian.id)
                        ? 'scale-95 border-dashed opacity-40'
                        : '',
                    isUnitDropTarget(bagian.id) && isUnitDropValid(bagian.id)
                        ? 'scale-102 border-emerald-500 bg-emerald-500/10 ring-4 ring-emerald-500'
                        : '',
                    isUnitDropTarget(bagian.id) && !isUnitDropValid(bagian.id)
                        ? 'cursor-not-allowed border-rose-500 opacity-80 ring-2 ring-rose-500'
                        : '',
                ]"
                @dragstart="emit('dragStart', bagian, $event)"
                @dragend="emit('dragEnd', bagian, $event)"
                @dragover="emit('dragOver', bagian, $event)"
                @dragleave="emit('dragLeave', bagian, $event)"
                @drop="emit('drop', bagian, $event)"
            >
                <!-- Drop Target Indicator Banner -->
                <div
                    v-if="isUnitDropTarget(bagian.id)"
                    class="absolute -top-3 left-1/2 z-20 flex -translate-x-1/2 items-center gap-1 rounded-full px-2.5 py-0.5 text-[9px] font-extrabold shadow-md"
                    :class="
                        isUnitDropValid(bagian.id)
                            ? 'animate-bounce bg-emerald-600 text-white'
                            : 'bg-rose-600 text-white'
                    "
                >
                    <span>{{
                        isUnitDropValid(bagian.id)
                            ? '📥 Pindahkan ke bagian ini'
                            : '🚫 Tidak dapat memindahkan'
                    }}</span>
                </div>

                <!-- Bagian Title & Badge -->
                <div class="flex items-start justify-between gap-2">
                    <div class="flex items-start gap-1.5 truncate">
                        <!-- Drag Handle for Bagian -->
                        <div
                            v-if="canMutate"
                            class="mt-0.5 -ml-1 cursor-grab p-0.5 text-muted-foreground hover:text-foreground active:cursor-grabbing"
                            title="Tarik untuk memindahkan bagian ini ke asisten lain"
                        >
                            <GripVertical class="size-3.5" />
                        </div>

                        <div class="truncate">
                            <span
                                class="block font-mono text-[9px] font-bold text-muted-foreground uppercase"
                            >
                                {{ bagian.code || 'BAGIAN' }}
                            </span>
                            <h5
                                class="text-xs font-bold text-foreground transition-colors group-hover:text-indigo-600 dark:group-hover:text-indigo-400"
                            >
                                {{ bagian.name }}
                            </h5>
                        </div>
                    </div>
                    <Badge
                        :variant="bagian.is_active ? 'outline' : 'secondary'"
                        class="shrink-0 px-1 py-0 text-[8px]"
                    >
                        {{ bagian.is_active ? 'Aktif' : 'Nonaktif' }}
                    </Badge>
                </div>

                <!-- Pejabat / Positions in this Bagian -->
                <div
                    v-if="
                        filterPositions(bagian.positions, viewMode).length > 0
                    "
                    class="mt-2.5 space-y-1.5 border-t border-border/50 pt-2"
                >
                    <div
                        v-for="pos in filterPositions(
                            bagian.positions,
                            viewMode,
                        )"
                        :key="pos.id"
                        class="flex cursor-pointer items-center justify-between gap-2 rounded-xl border border-border/50 bg-muted/20 p-2 text-xs transition-colors hover:bg-muted/60"
                        @click="emit('inspectPosition', pos)"
                    >
                        <div class="flex items-center gap-1.5 truncate">
                            <Briefcase
                                class="size-3 shrink-0 text-indigo-600 dark:text-indigo-400"
                            />
                            <div class="truncate">
                                <p
                                    class="truncate text-[10px] font-semibold text-foreground"
                                >
                                    {{ pos.name }}
                                </p>
                                <p
                                    class="truncate text-[9px] text-muted-foreground"
                                >
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
                            class="shrink-0 px-1 py-0 text-[8px]"
                        >
                            {{ pos.level.name.split(' ')[0] }}
                        </Badge>
                    </div>
                </div>

                <!-- Sub-sections (e.g. Seksi / Subbagian) if any -->
                <div
                    v-if="bagian.children && bagian.children.length > 0"
                    class="mt-2 border-t border-border/50 pt-1.5"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between rounded-lg bg-muted/40 px-2 py-1 text-[10px] font-semibold text-foreground hover:bg-muted/70"
                        @click="emit('toggleCollapse', bagian.id)"
                    >
                        <span
                            >{{ bagian.children.length }} Sub-Bagian /
                            Seksi</span
                        >
                        <ChevronDown
                            class="size-3 transition-transform"
                            :class="{
                                '-rotate-90': collapsedUnitIds.has(bagian.id),
                            }"
                        />
                    </button>

                    <div
                        v-if="!collapsedUnitIds.has(bagian.id)"
                        class="mt-2 space-y-1.5 border-l-2 border-indigo-500/30 pl-2"
                    >
                        <div
                            v-for="sub in bagian.children"
                            :key="sub.id"
                            class="flex cursor-pointer items-center justify-between rounded-lg border border-border/50 bg-background p-1.5 text-[10px] hover:border-indigo-500/40"
                            @click="emit('inspectUnit', sub)"
                        >
                            <span
                                class="truncate font-medium text-foreground"
                                >{{ sub.name }}</span
                            >
                            <span class="text-[9px] text-muted-foreground"
                                >{{ sub.positions.length }} Jabatan</span
                            >
                        </div>
                    </div>
                </div>

                <!-- Super Admin Actions Toolbar for Bagian (Edit Mode) -->
                <div
                    v-if="isEditMode && canMutate"
                    class="mt-2.5 flex flex-wrap items-center gap-1 border-t border-border/50 pt-2 text-[9px]"
                >
                    <Button
                        size="sm"
                        variant="outline"
                        class="h-5 gap-0.5 rounded border-indigo-500/30 px-1.5 text-[8px] text-indigo-700 dark:text-indigo-300"
                        title="Tambah sub-bagian / seksi"
                        @click.stop="emit('addSubUnit', bagian)"
                    >
                        <Plus class="size-2.5" />
                        <span>Sub-Unit</span>
                    </Button>

                    <Button
                        size="sm"
                        variant="outline"
                        class="h-5 gap-0.5 rounded border-emerald-500/30 px-1.5 text-[8px] text-emerald-700 dark:text-emerald-300"
                        title="Tambah jabatan di bagian ini"
                        @click.stop="emit('addPosition', bagian)"
                    >
                        <Briefcase class="size-2.5" />
                        <span>Jabatan</span>
                    </Button>

                    <Button
                        size="sm"
                        variant="outline"
                        class="h-5 gap-0.5 rounded border-violet-500/30 px-1.5 text-[8px] text-violet-700 dark:text-violet-300"
                        title="Pindahkan bagian ini ke asisten lain"
                        @click.stop="emit('reparentUnit', bagian)"
                    >
                        <GitFork class="size-2.5" />
                        <span>Pindah</span>
                    </Button>

                    <Button
                        size="sm"
                        variant="ghost"
                        class="h-5 gap-0.5 rounded px-1.5 text-[8px] text-muted-foreground"
                        title="Edit data bagian"
                        @click.stop="emit('editUnit', bagian)"
                    >
                        <Pencil class="size-2.5" />
                        <span>Edit</span>
                    </Button>

                    <button
                        type="button"
                        class="ml-auto inline-flex items-center gap-0.5 font-semibold text-indigo-600 hover:underline"
                        @click.stop="emit('inspectUnit', bagian)"
                    >
                        <span>Rincian</span>
                        <ExternalLink class="size-2.5" />
                    </button>
                </div>

                <!-- Bagian Card Footer (Normal View Mode) -->
                <div
                    v-else
                    class="mt-2 flex items-center justify-between border-t border-border/50 pt-1.5 text-[10px]"
                >
                    <span class="text-muted-foreground"
                        >{{ bagian.positions.length }} Jabatan</span
                    >
                    <button
                        type="button"
                        class="inline-flex items-center gap-1 font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                        @click="emit('inspectUnit', bagian)"
                    >
                        <span>Rincian</span>
                        <ExternalLink class="size-2.5" />
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
