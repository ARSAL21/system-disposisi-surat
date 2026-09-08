<script setup lang="ts">
import {
    ArrowDownToLine,
    ChevronDown,
    ChevronRight,
    Columns3,
    GitFork,
    GripVertical,
    Pencil,
    Plus,
    Rows3,
    User,
} from '@lucide/vue';
import { computed } from 'vue';
import SubordinateUnitCard from '@/components/back-office/organization/SubordinateUnitCard.vue';
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
    branchLayout: 'horizontal' | 'vertical';
    draggedUnitId?: number;
    draggedUnit?: OrganizationTreeNode | null;
    hoveredTargetId?: number | null;
    validDropTargetIds?: Set<number>;
}>();

const emit = defineEmits<{
    inspectUnit: [unit: OrganizationTreeNode];
    inspectPosition: [position: OrganizationTreePosition];
    toggleCollapse: [unitId: number];
    addSubUnit: [parentUnit: OrganizationTreeNode];
    addPosition: [unit: OrganizationTreeNode];
    reparentUnit: [unit: OrganizationTreeNode];
    editUnit: [unit: OrganizationTreeNode];
    toggleBranchLayout: [unitId: number];
    dragStart: [unit: OrganizationTreeNode, event: DragEvent];
    dragEnd: [unit: OrganizationTreeNode, event: DragEvent];
    dragOver: [unit: OrganizationTreeNode, event: DragEvent];
    dragLeave: [unit: OrganizationTreeNode, event: DragEvent];
    drop: [unit: OrganizationTreeNode, event: DragEvent];
}>();

// Theme palettes for the 3 Assistants
const pillarThemes = [
    {
        name: 'Asisten I - Bidang Pemerintahan & Kesra',
        code: 'ASISTEN_1',
        border: 'border-emerald-500/40 ring-1 ring-emerald-500/20',
        gradient: 'from-emerald-600 via-teal-500 to-emerald-500',
        badgeClass:
            'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 border-emerald-500/30',
        busColor: 'bg-emerald-500',
        stemColor: 'bg-emerald-500',
        slotBg: 'border-emerald-500 bg-emerald-500/15 text-emerald-800 dark:text-emerald-200',
    },
    {
        name: 'Asisten II - Bidang Perekonomian & Pembangunan',
        code: 'ASISTEN_2',
        border: 'border-sky-500/40 ring-1 ring-sky-500/20',
        gradient: 'from-sky-600 via-cyan-500 to-sky-500',
        badgeClass:
            'bg-sky-500/10 text-sky-700 dark:text-sky-300 border-sky-500/30',
        busColor: 'bg-sky-500',
        stemColor: 'bg-sky-500',
        slotBg: 'border-sky-500 bg-sky-500/15 text-sky-800 dark:text-sky-200',
    },
    {
        name: 'Asisten III - Bidang Administrasi Umum',
        code: 'ASISTEN_3',
        border: 'border-violet-500/40 ring-1 ring-violet-500/20',
        gradient: 'from-violet-600 via-purple-500 to-indigo-500',
        badgeClass:
            'bg-violet-500/10 text-violet-700 dark:text-violet-300 border-violet-500/30',
        busColor: 'bg-violet-500',
        stemColor: 'bg-violet-500',
        slotBg: 'border-indigo-500 bg-indigo-500/15 text-indigo-800 dark:text-indigo-200',
    },
];

const pillarTheme = computed(() => {
    return (
        pillarThemes[props.pillarIndex % pillarThemes.length] ?? pillarThemes[0]
    );
});

const isMatched = computed<boolean>(() => {
    if (!props.searchQuery) {
        return false;
    }

    const q = props.searchQuery.toLowerCase();
    const matchName = props.node.name.toLowerCase().includes(q);
    const matchCode = props.node.code
        ? props.node.code.toLowerCase().includes(q)
        : false;
    const matchPos = props.node.positions.some(
        (p) =>
            p.name.toLowerCase().includes(q) ||
            p.code.toLowerCase().includes(q) ||
            (p.active_assignment?.user.name.toLowerCase().includes(q) ?? false),
    );

    return matchName || matchCode || matchPos;
});

const asistenPosition = computed<OrganizationTreePosition | null>(() => {
    if (!props.node.positions || props.node.positions.length === 0) {
        return null;
    }

    return props.node.positions[0] ?? null;
});

function isUnitDragged(unitId: number): boolean {
    return props.draggedUnitId === unitId;
}

function isUnitDropTarget(unitId: number): boolean {
    return props.hoveredTargetId === unitId;
}

function isUnitDropValid(unitId: number): boolean {
    return props.validDropTargetIds
        ? props.validDropTargetIds.has(unitId)
        : false;
}

const showDropSlot = computed(() => {
    return isUnitDropTarget(props.node.id) && isUnitDropValid(props.node.id);
});

const totalChildrenCount = computed(() => {
    return (props.node.children?.length ?? 0) + (showDropSlot.value ? 1 : 0);
});

function onAssistantDragStart(event: DragEvent) {
    if (!props.canMutate) {
        return;
    }

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(props.node.id));
    }

    emit('dragStart', props.node, event);
}

function onAssistantDragOver(event: DragEvent) {
    if (!props.canMutate) {
        return;
    }

    if (isUnitDropValid(props.node.id)) {
        event.preventDefault();

        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = 'move';
        }
    }

    emit('dragOver', props.node, event);
}

function onAssistantDrop(event: DragEvent) {
    if (isUnitDropValid(props.node.id)) {
        event.preventDefault();
    }

    if (!props.canMutate) {
        return;
    }

    emit('drop', props.node, event);
}
</script>

<template>
    <div class="flex flex-col items-center">
        <!-- ======================================================== -->
        <!-- 1. ASISTEN HEADER CARD                                  -->
        <!-- ======================================================== -->
        <div
            :data-unit-id="node.id"
            :draggable="canMutate"
            class="group relative w-72 rounded-2xl border bg-card p-4 shadow-md transition-all duration-300 ease-out hover:-translate-y-1 hover:shadow-xl sm:w-80 dark:bg-slate-900"
            :class="[
                pillarTheme.border,
                isMatched && searchQuery ? 'ring-4 ring-indigo-500/50' : '',
                isUnitDragged(node.id)
                    ? 'scale-95 border-dashed border-indigo-400 opacity-40'
                    : '',
                isUnitDropTarget(node.id) && isUnitDropValid(node.id)
                    ? 'scale-102 border-emerald-500 bg-emerald-500/10 ring-4 ring-emerald-500'
                    : '',
                isUnitDropTarget(node.id) && !isUnitDropValid(node.id)
                    ? 'cursor-not-allowed border-rose-500 opacity-80 ring-2 ring-rose-500'
                    : '',
            ]"
            @dragstart="onAssistantDragStart"
            @dragend="emit('dragEnd', node, $event)"
            @dragover="onAssistantDragOver"
            @dragleave="emit('dragLeave', node, $event)"
            @drop="onAssistantDrop"
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

            <!-- Card Header: Title & Badges -->
            <div class="flex items-start justify-between gap-2">
                <div class="flex items-center gap-2">
                    <div
                        v-if="canMutate"
                        class="-ml-1 cursor-grab p-0.5 text-muted-foreground transition-colors hover:text-indigo-600 active:cursor-grabbing dark:hover:text-indigo-400"
                        title="Tarik untuk memindahkan asisten ini"
                    >
                        <GripVertical class="size-4" />
                    </div>

                    <div>
                        <div class="flex items-center gap-1.5">
                            <span
                                class="font-mono text-[10px] font-bold tracking-wider text-muted-foreground uppercase"
                            >
                                Tier III · Pilar Asisten
                            </span>
                            <Badge
                                variant="outline"
                                class="h-4 px-1.5 py-0 font-mono text-[9px] font-semibold"
                                :class="pillarTheme.badgeClass"
                            >
                                {{ node.code || 'ASISTEN' }}
                            </Badge>
                        </div>
                        <h4
                            class="mt-0.5 cursor-pointer text-sm font-bold text-foreground transition-colors hover:text-indigo-600"
                            @click="emit('inspectUnit', node)"
                        >
                            {{ node.name }}
                        </h4>
                    </div>
                </div>

                <!-- Toggle Shape & Collapse Buttons -->
                <div class="flex items-center gap-1">
                    <!-- Ubah Bentuk Cabang: Horizontal (Bercabang) vs Vertikal (Bersusun) -->
                    <button
                        type="button"
                        class="flex size-7 items-center justify-center rounded-lg border border-border/70 bg-muted/30 text-muted-foreground transition-colors hover:bg-muted/80 hover:text-foreground"
                        :title="
                            branchLayout === 'horizontal'
                                ? 'Bentuk: Bercabang Horizontal (Klik untuk jadikan bersusun vertikal)'
                                : 'Bentuk: Bersusun Vertikal (Klik untuk jadikan bercabang horizontal)'
                        "
                        @click.stop="emit('toggleBranchLayout', node.id)"
                    >
                        <Columns3
                            v-if="branchLayout === 'horizontal'"
                            class="size-3.5 text-indigo-600 dark:text-indigo-400"
                        />
                        <Rows3
                            v-else
                            class="size-3.5 text-sky-600 dark:text-sky-400"
                        />
                    </button>

                    <!-- Collapse / Expand Children Button -->
                    <button
                        v-if="node.children && node.children.length > 0"
                        type="button"
                        class="flex size-7 items-center justify-center rounded-lg border border-border/70 bg-muted/30 text-muted-foreground transition-colors hover:bg-muted/80 hover:text-foreground"
                        :title="
                            collapsedUnitIds.has(node.id)
                                ? 'Buka bagian-bagian di bawah asisten ini'
                                : 'Tutup bagian-bagian di bawah asisten ini'
                        "
                        @click.stop="emit('toggleCollapse', node.id)"
                    >
                        <ChevronRight
                            v-if="collapsedUnitIds.has(node.id)"
                            class="size-3.5"
                        />
                        <ChevronDown v-else class="size-3.5" />
                    </button>
                </div>
            </div>

            <!-- Asisten Official Info -->
            <div
                class="mt-2.5 rounded-xl border border-border/60 bg-muted/20 p-2 text-xs"
            >
                <div v-if="asistenPosition" class="space-y-1">
                    <div class="flex items-center justify-between text-[11px]">
                        <span
                            class="max-w-[170px] truncate font-semibold text-foreground"
                            :title="asistenPosition.name"
                        >
                            {{ asistenPosition.name }}
                        </span>
                        <Badge
                            variant="secondary"
                            class="h-4 px-1.5 py-0 text-[9px]"
                            :class="
                                asistenPosition.active_assignment
                                    ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                    : 'bg-amber-500/10 text-amber-700'
                            "
                        >
                            {{
                                asistenPosition.active_assignment
                                    ? 'Terisi'
                                    : 'Lowong'
                            }}
                        </Badge>
                    </div>

                    <div
                        v-if="asistenPosition.active_assignment"
                        class="flex cursor-pointer items-center gap-1.5 pt-0.5 text-[11px] text-muted-foreground hover:text-foreground"
                        @click="emit('inspectPosition', asistenPosition)"
                    >
                        <User class="size-3 shrink-0 text-indigo-500" />
                        <span class="truncate font-medium">{{
                            asistenPosition.active_assignment.user.name
                        }}</span>
                    </div>
                </div>
                <div v-else class="text-[11px] text-muted-foreground italic">
                    Belum ada jabatan asisten terdaftar.
                </div>
            </div>

            <!-- Subordinate Bagian Summary Count -->
            <div
                class="mt-2 flex items-center justify-between pt-1 text-[11px] font-medium text-muted-foreground"
            >
                <span>Membawahi {{ node.children?.length || 0 }} Bagian</span>
                <span
                    class="font-mono text-[10px] text-indigo-600 dark:text-indigo-400"
                >
                    {{
                        branchLayout === 'horizontal'
                            ? '┌─┬─┐ Bercabang'
                            : '│ Bersusun'
                    }}
                </span>
            </div>

            <!-- Super Admin Actions Toolbar (Edit Mode) -->
            <div
                v-if="isEditMode && canMutate"
                class="mt-2.5 flex flex-wrap items-center gap-1 border-t border-border/50 pt-2 text-[10px]"
            >
                <Button
                    size="sm"
                    variant="outline"
                    class="h-6 gap-1 rounded-md border-indigo-500/30 px-1.5 text-[9px] text-indigo-700 hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-indigo-950/40"
                    title="Tambah bagian di bawah asisten ini"
                    @click.stop="emit('addSubUnit', node)"
                >
                    <Plus class="size-2.5" />
                    <span>+ Bagian</span>
                </Button>

                <Button
                    size="sm"
                    variant="outline"
                    class="h-6 gap-1 rounded-md border-violet-500/30 px-1.5 text-[9px] text-violet-700 hover:bg-violet-50 dark:text-violet-300 dark:hover:bg-violet-950/40"
                    title="Pindahkan asisten ini"
                    @click.stop="emit('reparentUnit', node)"
                >
                    <GitFork class="size-2.5" />
                    <span>Pindah</span>
                </Button>

                <Button
                    size="sm"
                    variant="ghost"
                    class="h-6 gap-1 rounded-md px-1.5 text-[9px] text-muted-foreground hover:text-foreground"
                    title="Edit nama"
                    @click.stop="emit('editUnit', node)"
                >
                    <Pencil class="size-2.5" />
                    <span>Edit</span>
                </Button>
            </div>
        </div>

        <!-- Outgoing Stem Down from Asisten -->
        <div
            class="h-6 w-0.5 transition-all duration-300"
            :class="pillarTheme.busColor"
        />

        <!-- ======================================================== -->
        <!-- 2. SUBORDINATE BAGIANS (HORIZONTAL TREE OR VERTICAL)     -->
        <!-- ======================================================== -->
        <div
            v-if="
                ((node.children && node.children.length > 0) || showDropSlot) &&
                !collapsedUnitIds.has(node.id)
            "
        >
            <!-- A. HORIZONTAL BUS BAR BRANCHING (┌─────┼─────┐) -->
            <TransitionGroup
                v-if="branchLayout === 'horizontal'"
                tag="div"
                name="tree-node"
                class="flex items-start justify-center gap-3 pt-0 transition-all duration-500 ease-out sm:gap-4"
            >
                <!-- Existing Children Cards -->
                <div
                    v-for="(bagian, idx) in node.children"
                    :key="bagian.id"
                    class="relative flex flex-col items-center transition-all duration-500 ease-out"
                >
                    <!-- Horizontal Bus Connector Line Segment -->
                    <div
                        v-if="totalChildrenCount > 1"
                        class="absolute top-0 h-0.5 transition-all duration-400 ease-out"
                        :class="[
                            pillarTheme.busColor,
                            idx === 0 ? 'right-0 left-1/2' : '',
                            idx === totalChildrenCount - 1
                                ? 'right-1/2 left-0'
                                : '',
                            idx > 0 && idx < totalChildrenCount - 1
                                ? 'right-0 left-0'
                                : '',
                        ]"
                    />

                    <!-- Vertical Drop Line from Bus Bar to Child Card -->
                    <div
                        class="h-6 w-0.5 transition-all duration-300"
                        :class="pillarTheme.stemColor"
                    />

                    <!-- Bagian Card -->
                    <SubordinateUnitCard
                        :node="bagian"
                        :search-query="searchQuery"
                        :view-mode="viewMode"
                        :is-edit-mode="isEditMode"
                        :can-mutate="canMutate"
                        :is-dragged="isUnitDragged(bagian.id)"
                        :is-drop-target="isUnitDropTarget(bagian.id)"
                        :is-drop-valid="isUnitDropValid(bagian.id)"
                        :is-collapsed="collapsedUnitIds.has(bagian.id)"
                        :dragged-unit-id="draggedUnitId"
                        :dragged-unit="draggedUnit"
                        @inspect-unit="emit('inspectUnit', $event)"
                        @inspect-position="emit('inspectPosition', $event)"
                        @toggle-collapse="emit('toggleCollapse', $event)"
                        @add-sub-unit="emit('addSubUnit', $event)"
                        @add-position="emit('addPosition', $event)"
                        @reparent-unit="emit('reparentUnit', $event)"
                        @edit-unit="emit('editUnit', $event)"
                        @drag-start="(unit, ev) => emit('dragStart', unit, ev)"
                        @drag-end="(unit, ev) => emit('dragEnd', unit, ev)"
                        @drag-over="(unit, ev) => emit('dragOver', unit, ev)"
                        @drag-leave="(unit, ev) => emit('dragLeave', unit, ev)"
                        @drop="(unit, ev) => emit('drop', unit, ev)"
                    />
                </div>

                <!-- Ghost Placement Placeholder Slot (Gambaran Tempat Baru Sebelum Dilepas) -->
                <div
                    v-if="showDropSlot"
                    key="drop-slot"
                    class="relative flex animate-in flex-col items-center transition-all duration-500 ease-out zoom-in-95 fade-in"
                >
                    <!-- Horizontal Bus Connector Line for the Slot -->
                    <div
                        v-if="totalChildrenCount > 1"
                        class="absolute top-0 h-0.5 transition-all duration-400 ease-out"
                        :class="[
                            pillarTheme.busColor,
                            node.children && node.children.length === 0
                                ? 'hidden'
                                : 'right-1/2 left-0',
                        ]"
                    />

                    <!-- Vertical Drop Line from Bus Bar to Ghost Slot -->
                    <div
                        class="h-6 w-0.5 transition-all duration-300"
                        :class="pillarTheme.stemColor"
                    />

                    <!-- The Ghost Card Slot -->
                    <div
                        class="flex min-h-[140px] w-56 animate-pulse flex-col items-center justify-center rounded-2xl border-2 border-dashed p-4 text-center shadow-lg backdrop-blur-sm transition-all duration-300 sm:w-64"
                        :class="pillarTheme.slotBg"
                        @dragover.prevent="onAssistantDragOver"
                        @drop.prevent="onAssistantDrop"
                    >
                        <div
                            class="mb-2 flex size-9 items-center justify-center rounded-xl bg-background/80 text-indigo-600 shadow-xs dark:text-indigo-400"
                        >
                            <ArrowDownToLine class="size-4.5 animate-bounce" />
                        </div>
                        <span
                            class="block font-mono text-[9px] font-bold tracking-wider uppercase opacity-90"
                        >
                            Gambaran Tempat Baru
                        </span>
                        <p
                            class="mt-1 line-clamp-1 max-w-full text-xs font-bold text-foreground"
                        >
                            {{ draggedUnit?.name || 'Unit Organisasi' }}
                        </p>
                        <p
                            class="mt-1 text-[10px] leading-tight text-muted-foreground"
                        >
                            Lepas mouse di sini untuk menempatkan di bawah
                            {{ node.name }}
                        </p>
                    </div>
                </div>
            </TransitionGroup>

            <!-- B. VERTICAL STACK BRANCHING (Bersusun ke bawah) -->
            <TransitionGroup
                v-else
                tag="div"
                name="tree-node"
                class="flex flex-col items-center space-y-2.5 transition-all duration-500 ease-out"
            >
                <div
                    v-for="(bagian, idx) in node.children"
                    :key="bagian.id"
                    class="flex flex-col items-center transition-all duration-500 ease-out"
                >
                    <div
                        v-if="idx > 0"
                        class="h-4 w-0.5 transition-all duration-300"
                        :class="pillarTheme.stemColor"
                    />
                    <SubordinateUnitCard
                        :node="bagian"
                        :search-query="searchQuery"
                        :view-mode="viewMode"
                        :is-edit-mode="isEditMode"
                        :can-mutate="canMutate"
                        :is-dragged="isUnitDragged(bagian.id)"
                        :is-drop-target="isUnitDropTarget(bagian.id)"
                        :is-drop-valid="isUnitDropValid(bagian.id)"
                        :is-collapsed="collapsedUnitIds.has(bagian.id)"
                        :dragged-unit-id="draggedUnitId"
                        :dragged-unit="draggedUnit"
                        @inspect-unit="emit('inspectUnit', $event)"
                        @inspect-position="emit('inspectPosition', $event)"
                        @toggle-collapse="emit('toggleCollapse', $event)"
                        @add-sub-unit="emit('addSubUnit', $event)"
                        @add-position="emit('addPosition', $event)"
                        @reparent-unit="emit('reparentUnit', $event)"
                        @edit-unit="emit('editUnit', $event)"
                        @drag-start="(unit, ev) => emit('dragStart', unit, ev)"
                        @drag-end="(unit, ev) => emit('dragEnd', unit, ev)"
                        @drag-over="(unit, ev) => emit('dragOver', unit, ev)"
                        @drag-leave="(unit, ev) => emit('dragLeave', unit, ev)"
                        @drop="(unit, ev) => emit('drop', unit, ev)"
                    />
                </div>

                <!-- Ghost Slot in Vertical Mode -->
                <div
                    v-if="showDropSlot"
                    key="drop-slot-vert"
                    class="flex animate-in flex-col items-center transition-all duration-500 ease-out zoom-in-95 fade-in"
                >
                    <div
                        class="h-4 w-0.5 transition-all duration-300"
                        :class="pillarTheme.stemColor"
                    />
                    <div
                        class="flex min-h-[120px] w-56 animate-pulse flex-col items-center justify-center rounded-2xl border-2 border-dashed p-4 text-center shadow-lg backdrop-blur-sm transition-all duration-300 sm:w-64"
                        :class="pillarTheme.slotBg"
                        @dragover.prevent="onAssistantDragOver"
                        @drop.prevent="onAssistantDrop"
                    >
                        <div
                            class="mb-1.5 flex size-8 items-center justify-center rounded-xl bg-background/80 text-indigo-600 shadow-xs dark:text-indigo-400"
                        >
                            <ArrowDownToLine class="size-4 animate-bounce" />
                        </div>
                        <span
                            class="block font-mono text-[9px] font-bold tracking-wider uppercase opacity-90"
                        >
                            Gambaran Tempat Baru
                        </span>
                        <p
                            class="mt-0.5 line-clamp-1 max-w-full text-xs font-bold text-foreground"
                        >
                            {{ draggedUnit?.name || 'Unit Organisasi' }}
                        </p>
                        <p class="mt-0.5 text-[10px] text-muted-foreground">
                            Lepas mouse di sini untuk menempatkan kartu
                        </p>
                    </div>
                </div>
            </TransitionGroup>
        </div>
    </div>
</template>

<style scoped>
.tree-node-move,
.tree-node-enter-active,
.tree-node-leave-active {
    transition: all 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

.tree-node-enter-from,
.tree-node-leave-to {
    opacity: 0;
    transform: scale(0.92) translateY(8px);
}

.tree-node-leave-active {
    position: absolute;
}
</style>
