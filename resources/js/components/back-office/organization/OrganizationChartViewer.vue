<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    Briefcase,
    Building2,
    Check,
    Columns3,
    FolderTree,
    GitFork,
    KeyRound,
    Lock,
    Maximize2,
    Minimize2,
    Move,
    Pencil,
    Plus,
    RefreshCw,
    RotateCcw,
    Rows3,
    Search,
    Settings,
    ShieldAlert,
    Undo2,
    User,
    UserCheck,
    X,
    ZoomIn,
    ZoomOut,
} from '@lucide/vue';
import gsap from 'gsap';
import { computed, nextTick, ref, watch } from 'vue';
import BackOfficeConfirmPasswordModal from '@/components/back-office/auth/BackOfficeConfirmPasswordModal.vue';
import AssistantBranchTree from '@/components/back-office/organization/AssistantBranchTree.vue';
import ExecutiveTierCard from '@/components/back-office/organization/ExecutiveTierCard.vue';
import OrganizationTreeNodeBranch from '@/components/back-office/organization/OrganizationTreeNodeBranch.vue';
import ReparentUnitDialog from '@/components/back-office/organization/ReparentUnitDialog.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import type {
    OrganizationalUnitOption,
    OrganizationTreeData,
    OrganizationTreeNode,
    OrganizationTreePosition,
    PositionLevel,
} from '@/types';

interface MoveHistoryRecord {
    id: string;
    sourceId: number;
    sourceName: string;
    previousParentId: number | null;
    previousParentName: string;
    newParentId: number | null;
    newParentName: string;
    updateUrl: string;
    timestamp: number;
}

const props = defineProps<{
    tree: OrganizationTreeData;
    allUnits?: OrganizationalUnitOption[];
    levels?: PositionLevel[];
    assignmentsRoute: string;
    canMutate: boolean;
    activationUrl?: string;
}>();

const emit = defineEmits<{
    createUnit: [parentId?: number | null];
    createPosition: [unitId?: number | null];
    editUnit: [unit: OrganizationTreeNode];
    statusUnit: [unit: OrganizationTreeNode];
}>();

// Toolbar View Options
const viewMode = ref<'all' | 'units_only' | 'occupied_only' | 'vacant_only'>(
    'all',
);
const globalBranchLayout = ref<'horizontal' | 'vertical'>('horizontal');
const branchLayouts = ref<Record<number, 'horizontal' | 'vertical'>>({});
const searchQuery = ref<string>('');
const zoomLevel = ref<number>(1);
const collapsedUnitIds = ref<Set<number>>(new Set());

// Edit Mode & Step-Up Security State
const isEditMode = ref<boolean>(props.canMutate);
const isConfirmPasswordModalOpen = ref<boolean>(false);

watch(
    () => props.canMutate,
    (val) => {
        if (val) {
            isEditMode.value = true;
        }
    },
);

function handleToggleEditMode() {
    if (!props.canMutate) {
        isConfirmPasswordModalOpen.value = true;

        return;
    }

    isEditMode.value = !isEditMode.value;
}

function onPasswordConfirmed() {
    isConfirmPasswordModalOpen.value = false;
    isEditMode.value = true;
    router.reload();
}

// Inspector Modal State
const isInspectorOpen = ref<boolean>(false);
const inspectedNode = ref<{
    type: 'unit' | 'position';
    unit?: OrganizationTreeNode;
    position?: OrganizationTreePosition;
} | null>(null);

// Reparent Modal State
const isReparentOpen = ref<boolean>(false);
const unitToReparent = ref<OrganizationTreeNode | null>(null);

// Drag & Drop State
const draggedUnit = ref<OrganizationTreeNode | null>(null);
const hoveredTargetId = ref<number | null>(null);
const dropConfirmData = ref<{
    source: OrganizationTreeNode;
    target: OrganizationTreeNode;
} | null>(null);
const isDropping = ref<boolean>(false);
const dropError = ref<string | null>(null);

// Undo History State
const undoHistory = ref<MoveHistoryRecord[]>([]);
const latestMove = ref<MoveHistoryRecord | null>(null);
const showUndoToast = ref<boolean>(false);
const isUndoing = ref<boolean>(false);

// Reset Default Hierarchy State
const isResetModalOpen = ref<boolean>(false);
const isResetting = ref<boolean>(false);

const isDropConfirmOpen = computed({
    get: () => Boolean(dropConfirmData.value),
    set: (val: boolean) => {
        if (!val) {
            dropConfirmData.value = null;
        }
    },
});

// Fallback allUnits from tree if not directly provided
const computedAllUnits = computed<OrganizationalUnitOption[]>(() => {
    if (props.allUnits && props.allUnits.length > 0) {
        return props.allUnits;
    }

    const list: OrganizationalUnitOption[] = [];
    function collect(nodes: OrganizationTreeNode[]) {
        for (const n of nodes) {
            list.push({
                id: n.id,
                name: n.name,
                code: n.code,
                parent_id: n.parent_id,
            });

            if (n.children && n.children.length > 0) {
                collect(n.children);
            }
        }
    }
    collect(props.tree.root_units);

    return list;
});

function getUnitNameById(unitId: number | null): string {
    if (unitId === null) {
        return 'Tingkat Utama (Root)';
    }

    const found = computedAllUnits.value.find((u) => u.id === unitId);

    return found ? found.name : `Unit #${unitId}`;
}

// Branch Layout Helpers
function getBranchLayout(unitId: number): 'horizontal' | 'vertical' {
    return branchLayouts.value[unitId] ?? globalBranchLayout.value;
}

function toggleBranchLayout(unitId: number) {
    const current = getBranchLayout(unitId);
    branchLayouts.value[unitId] =
        current === 'horizontal' ? 'vertical' : 'horizontal';
}

function setAllBranchesLayout(layout: 'horizontal' | 'vertical') {
    globalBranchLayout.value = layout;
    branchLayouts.value = {};
}

// Calculate valid drop targets for the currently dragged unit
const validDropTargetIds = computed<Set<number>>(() => {
    const valid = new Set<number>();

    if (!draggedUnit.value) {
        return valid;
    }

    const invalidIds = new Set<number>();
    invalidIds.add(draggedUnit.value.id);

    if (draggedUnit.value.parent_id !== null) {
        invalidIds.add(draggedUnit.value.parent_id);
    }

    function collectDescendantIds(nodes: OrganizationTreeNode[]) {
        for (const node of nodes) {
            if (node.id === draggedUnit.value?.id) {
                markAllChildren(node.children);

                return;
            }

            if (node.children && node.children.length > 0) {
                collectDescendantIds(node.children);
            }
        }
    }

    function markAllChildren(children: OrganizationTreeNode[]) {
        for (const c of children) {
            invalidIds.add(c.id);

            if (c.children && c.children.length > 0) {
                markAllChildren(c.children);
            }
        }
    }

    collectDescendantIds(props.tree.root_units);

    function collectAllUnitIds(nodes: OrganizationTreeNode[]) {
        for (const node of nodes) {
            if (!invalidIds.has(node.id)) {
                valid.add(node.id);
            }

            if (node.children && node.children.length > 0) {
                collectAllUnitIds(node.children);
            }
        }
    }

    collectAllUnitIds(props.tree.root_units);

    return valid;
});

// Drag & Drop Handlers
function onDragStart(unit: OrganizationTreeNode, event?: DragEvent) {
    if (!props.canMutate) {
        return;
    }

    draggedUnit.value = unit;

    if (event && event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(unit.id));
    }
}

function onDragEnd() {
    draggedUnit.value = null;
    hoveredTargetId.value = null;
}

function onDragOver(targetUnit: OrganizationTreeNode, event?: DragEvent) {
    if (!props.canMutate || !draggedUnit.value) {
        return;
    }

    if (validDropTargetIds.value.has(targetUnit.id)) {
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }

        if (event && event.dataTransfer) {
            event.dataTransfer.dropEffect = 'move';
        }

        hoveredTargetId.value = targetUnit.id;
    } else {
        if (event && event.dataTransfer) {
            event.dataTransfer.dropEffect = 'none';
        }

        hoveredTargetId.value = null;
    }
}

function onDragLeave(targetUnit: OrganizationTreeNode) {
    if (hoveredTargetId.value === targetUnit.id) {
        hoveredTargetId.value = null;
    }
}

function onDrop(targetUnit: OrganizationTreeNode, event?: DragEvent) {
    if (event && typeof event.preventDefault === 'function') {
        event.preventDefault();
    }

    if (!props.canMutate || !draggedUnit.value) {
        return;
    }

    const source = draggedUnit.value;

    if (validDropTargetIds.value.has(targetUnit.id)) {
        dropConfirmData.value = {
            source,
            target: targetUnit,
        };
    }

    draggedUnit.value = null;
    hoveredTargetId.value = null;
}

function animateMovedCard(unitId: number) {
    nextTick(() => {
        const el = document.querySelector(`[data-unit-id="${unitId}"]`);

        if (el) {
            gsap.fromTo(
                el,
                {
                    scale: 0.88,
                    y: -14,
                    boxShadow:
                        '0 0 0 6px rgba(99, 102, 241, 0.7), 0 20px 25px -5px rgba(0, 0, 0, 0.1)',
                },
                {
                    scale: 1,
                    y: 0,
                    boxShadow:
                        '0 0 0 0px rgba(99, 102, 241, 0), 0 1px 3px 0 rgba(0, 0, 0, 0.1)',
                    duration: 0.8,
                    ease: 'elastic.out(1, 0.6)',
                    clearProps: 'boxShadow,transform',
                },
            );
        }
    });
}

function confirmDropMove() {
    if (!dropConfirmData.value) {
        return;
    }

    const { source, target } = dropConfirmData.value;
    isDropping.value = true;
    dropError.value = null;

    const prevParentId = source.parent_id;
    const prevParentName = getUnitNameById(prevParentId);
    const updateUrl =
        source.links?.update ?? `/back-office/organization/units/${source.id}`;

    router.patch(
        updateUrl,
        {
            name: source.name,
            parent_id: target.id,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                isDropping.value = false;
                dropConfirmData.value = null;

                // Push to Undo history
                const record: MoveHistoryRecord = {
                    id: `${source.id}-${Date.now()}`,
                    sourceId: source.id,
                    sourceName: source.name,
                    previousParentId: prevParentId,
                    previousParentName: prevParentName,
                    newParentId: target.id,
                    newParentName: target.name,
                    updateUrl,
                    timestamp: Date.now(),
                };
                undoHistory.value.unshift(record);
                latestMove.value = record;
                showUndoToast.value = true;
                animateMovedCard(source.id);
            },
            onError: (errors) => {
                isDropping.value = false;
                dropError.value =
                    errors.parent_id ||
                    errors.name ||
                    'Gagal memindahkan unit organisasi.';
            },
        },
    );
}

function onReparentSuccess(
    source: OrganizationTreeNode,
    prevParentId: number | null,
    nextParentId: number | null,
) {
    unitToReparent.value = null;

    if (prevParentId !== nextParentId) {
        const updateUrl =
            source.links?.update ??
            `/back-office/organization/units/${source.id}`;
        const record: MoveHistoryRecord = {
            id: `${source.id}-${Date.now()}`,
            sourceId: source.id,
            sourceName: source.name,
            previousParentId: prevParentId,
            previousParentName: getUnitNameById(prevParentId),
            newParentId: nextParentId,
            newParentName: getUnitNameById(nextParentId),
            updateUrl,
            timestamp: Date.now(),
        };
        undoHistory.value.unshift(record);
        latestMove.value = record;
        showUndoToast.value = true;
        animateMovedCard(source.id);
    }
}

// Undo Action
function undoMove(targetRecord?: MoveHistoryRecord) {
    const itemToUndo = targetRecord ?? undoHistory.value[0];

    if (!itemToUndo || isUndoing.value) {
        return;
    }

    isUndoing.value = true;

    router.patch(
        itemToUndo.updateUrl,
        {
            name: itemToUndo.sourceName,
            parent_id: itemToUndo.previousParentId,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                isUndoing.value = false;
                undoHistory.value = undoHistory.value.filter(
                    (h) => h.id !== itemToUndo.id,
                );
                latestMove.value = undoHistory.value[0] ?? null;

                if (undoHistory.value.length === 0) {
                    showUndoToast.value = false;
                }

                animateMovedCard(itemToUndo.sourceId);
            },
            onError: () => {
                isUndoing.value = false;
            },
        },
    );
}

// Reset Default Municipal Structure
const defaultMunicipalMap: Record<string, string> = {
    BAGIAN_TAPEM: 'ASISTEN_1',
    BAGIAN_KESRA: 'ASISTEN_1',
    BAGIAN_HUKUM: 'ASISTEN_1',
    BAGIAN_EKONOMI: 'ASISTEN_2',
    BAGIAN_PEMBANGUNAN: 'ASISTEN_2',
    BAGIAN_UMUM: 'ASISTEN_3',
    BAGIAN_ORGANISASI: 'ASISTEN_3',
    BAGIAN_PROTOCOL: 'ASISTEN_3',
};

const displacedDefaultUnits = computed(() => {
    const displaced: Array<{
        unit: OrganizationalUnitOption;
        expectedParentCode: string;
        expectedParent: OrganizationalUnitOption | undefined;
    }> = [];

    for (const [unitCode, parentCode] of Object.entries(defaultMunicipalMap)) {
        const unit = computedAllUnits.value.find((u) => u.code === unitCode);
        const parent = computedAllUnits.value.find(
            (u) => u.code === parentCode,
        );

        if (unit && parent && unit.parent_id !== parent.id) {
            displaced.push({
                unit,
                expectedParentCode: parentCode,
                expectedParent: parent,
            });
        }
    }

    return displaced;
});

async function executeResetDefault() {
    if (displacedDefaultUnits.value.length === 0) {
        isResetModalOpen.value = false;

        return;
    }

    isResetting.value = true;

    for (const item of displacedDefaultUnits.value) {
        if (!item.expectedParent) {
            continue;
        }

        await new Promise<void>((resolve) => {
            router.patch(
                `/back-office/organization/units/${item.unit.id}`,
                {
                    name: item.unit.name,
                    parent_id: item.expectedParent!.id,
                },
                {
                    preserveScroll: true,
                    preserveState: true,
                    onFinish: () => resolve(),
                },
            );
        });
    }

    isResetting.value = false;
    isResetModalOpen.value = false;
    undoHistory.value = [];
    showUndoToast.value = false;
}

// Zoom Controls
function zoomIn() {
    if (zoomLevel.value < 1.4) {
        zoomLevel.value = Number((zoomLevel.value + 0.1).toFixed(1));
    }
}

function zoomOut() {
    if (zoomLevel.value > 0.6) {
        zoomLevel.value = Number((zoomLevel.value - 0.1).toFixed(1));
    }
}

function resetZoom() {
    zoomLevel.value = 1;
}

// Expand / Collapse State
function toggleCollapse(unitId: number) {
    if (collapsedUnitIds.value.has(unitId)) {
        collapsedUnitIds.value.delete(unitId);
    } else {
        collapsedUnitIds.value.add(unitId);
    }
}

function expandAll() {
    collapsedUnitIds.value.clear();
}

function collapseAll() {
    const allIds = new Set<number>();
    function collectIds(nodes: OrganizationTreeNode[]) {
        for (const node of nodes) {
            if (node.children && node.children.length > 0) {
                allIds.add(node.id);
                collectIds(node.children);
            }
        }
    }
    collectIds(props.tree.root_units);
    collapsedUnitIds.value = allIds;
}

// Inspector Handlers
function inspectUnit(unit: OrganizationTreeNode) {
    inspectedNode.value = { type: 'unit', unit };
    isInspectorOpen.value = true;
}

function inspectPosition(position: OrganizationTreePosition) {
    inspectedNode.value = { type: 'position', position };
    isInspectorOpen.value = true;
}

// Super Admin Chart Actions
function openAddSubUnit(parentUnit: OrganizationTreeNode) {
    emit('createUnit', parentUnit.id);
}

function openAddPosition(unit: OrganizationTreeNode) {
    emit('createPosition', unit.id);
}

function openReparent(unit: OrganizationTreeNode) {
    unitToReparent.value = unit;
    isReparentOpen.value = true;
}

function openEditUnit(unit: OrganizationTreeNode) {
    emit('editUnit', unit);
}

// Hierarchy Analysis for 4-Tier Organogram:
const waliKotaNode = computed<OrganizationTreeNode | null>(() => {
    if (props.tree.root_units.length === 0) {
        return null;
    }

    return props.tree.root_units[0] ?? null;
});

const sekdaNode = computed<OrganizationTreeNode | null>(() => {
    if (
        !waliKotaNode.value ||
        !waliKotaNode.value.children ||
        waliKotaNode.value.children.length === 0
    ) {
        return null;
    }

    return waliKotaNode.value.children[0] ?? null;
});

const assistantNodes = computed<OrganizationTreeNode[]>(() => {
    if (!sekdaNode.value || !sekdaNode.value.children) {
        return [];
    }

    return sekdaNode.value.children;
});

const otherRootUnits = computed<OrganizationTreeNode[]>(() => {
    if (props.tree.root_units.length <= 1) {
        return [];
    }

    return props.tree.root_units.slice(1);
});

// Statistics
const totalUnitsCount = computed(() => {
    let count = 0;
    function countNodes(nodes: OrganizationTreeNode[]) {
        for (const node of nodes) {
            count++;

            if (node.children) {
                countNodes(node.children);
            }
        }
    }
    countNodes(props.tree.root_units);

    return count;
});

const totalPositionsCount = computed(() => {
    let count = props.tree.unassigned_positions.length;
    function countPos(nodes: OrganizationTreeNode[]) {
        for (const node of nodes) {
            count += node.positions.length;

            if (node.children) {
                countPos(node.children);
            }
        }
    }
    countPos(props.tree.root_units);

    return count;
});

const occupiedPositionsCount = computed(() => {
    let count = props.tree.unassigned_positions.filter(
        (p) => p.active_assignment !== null,
    ).length;
    function countOcc(nodes: OrganizationTreeNode[]) {
        for (const node of nodes) {
            count += node.positions.filter(
                (p) => p.active_assignment !== null,
            ).length;

            if (node.children) {
                countOcc(node.children);
            }
        }
    }
    countOcc(props.tree.root_units);

    return count;
});

function getLevelBadgeClass(code: string): string {
    switch (code) {
        case 'MAYOR':
        case 'REGIONAL_SECRETARY':
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
</script>

<template>
    <div class="space-y-6">
        <!-- ======================================================== -->
        <!-- 1. TOOLBAR & INTERACTIVE CONTROLS                        -->
        <!-- ======================================================== -->
        <section
            class="relative overflow-hidden rounded-3xl border border-border/80 bg-card p-5 shadow-lg shadow-black/5 backdrop-blur-2xl sm:p-6 dark:border-border/60 dark:bg-slate-900/90"
        >
            <div
                class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between"
            >
                <!-- Title & Meta -->
                <div>
                    <div
                        class="inline-flex items-center gap-1.5 text-xs font-bold tracking-wider text-indigo-600 uppercase dark:text-indigo-400"
                    >
                        <FolderTree class="size-4" />
                        <span>Bagan Interaktif Unit Organisasi</span>
                    </div>
                    <h3
                        class="mt-1 text-xl font-bold tracking-tight text-foreground sm:text-2xl"
                    >
                        Struktur Kepemimpinan & Pengelompokan Asisten
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        Piramida kepemimpinan dinas: Wali Kota &rarr; Sekda
                        &rarr; Asisten I, II, III &rarr; Bagian pelaksana.
                    </p>
                </div>

                <!-- Stats summary chips & Quick Actions -->
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <div
                        class="rounded-xl border border-border/70 bg-muted/40 px-3 py-1.5"
                    >
                        <span class="text-muted-foreground">Unit: </span>
                        <span class="font-bold text-foreground">{{
                            totalUnitsCount
                        }}</span>
                    </div>
                    <div
                        class="rounded-xl border border-border/70 bg-muted/40 px-3 py-1.5"
                    >
                        <span class="text-muted-foreground">Jabatan: </span>
                        <span class="font-bold text-foreground">{{
                            totalPositionsCount
                        }}</span>
                    </div>
                    <div
                        class="rounded-xl border border-border/70 bg-emerald-500/10 px-3 py-1.5 text-emerald-700 dark:text-emerald-300"
                    >
                        <span class="font-medium">Terisi: </span>
                        <span class="font-bold">{{
                            occupiedPositionsCount
                        }}</span>
                    </div>
                    <div
                        class="rounded-xl border border-border/70 bg-amber-500/10 px-3 py-1.5 text-amber-700 dark:text-amber-300"
                    >
                        <span class="font-medium">Lowong: </span>
                        <span class="font-bold">{{
                            totalPositionsCount - occupiedPositionsCount
                        }}</span>
                    </div>

                    <!-- Undo Action Button in Toolbar -->
                    <Button
                        v-if="canMutate && undoHistory.length > 0"
                        size="sm"
                        variant="outline"
                        class="h-9 animate-pulse gap-1.5 rounded-xl border-amber-500/50 bg-amber-500/10 text-xs font-bold text-amber-800 shadow-xs transition-all hover:bg-amber-500/20 dark:text-amber-300"
                        :disabled="isUndoing"
                        :title="`Batalkan pemindahan terakhir: ${latestMove?.sourceName} ke ${latestMove?.newParentName}`"
                        @click="undoMove()"
                    >
                        <Spinner v-if="isUndoing" class="size-3.5" />
                        <RotateCcw v-else class="size-3.5" />
                        <span
                            >Batalkan Pindah (Undo
                            {{ undoHistory.length }})</span
                        >
                    </Button>

                    <!-- Super Admin Mode Atur Bagan Toggle & Step-up Modal -->
                    <div class="ml-auto flex items-center gap-2">
                        <Button
                            size="sm"
                            :variant="
                                canMutate && isEditMode ? 'default' : 'outline'
                            "
                            class="h-9 gap-1.5 rounded-xl text-xs shadow-sm transition-all"
                            :class="[
                                canMutate && isEditMode
                                    ? 'bg-amber-600 font-bold text-white ring-2 ring-amber-500/30 hover:bg-amber-700'
                                    : !canMutate
                                      ? 'border-amber-500/50 bg-amber-500/10 font-bold text-amber-800 hover:bg-amber-500/20 dark:text-amber-300'
                                      : 'border-border/80',
                            ]"
                            @click="handleToggleEditMode"
                        >
                            <Lock
                                v-if="!canMutate"
                                class="size-3.5 text-amber-700 dark:text-amber-400"
                            />
                            <Settings
                                v-else
                                class="size-3.5"
                                :class="{ 'animate-spin': isEditMode }"
                            />
                            <span>{{
                                !canMutate
                                    ? 'Buka Kunci Atur Bagan'
                                    : isEditMode
                                      ? 'Selesai Atur Bagan'
                                      : 'Mode Atur Bagan'
                            }}</span>
                            <Badge
                                v-if="canMutate && isEditMode"
                                class="ml-1 bg-white px-1.5 py-0 text-[9px] font-extrabold text-amber-800"
                            >
                                Aktif
                            </Badge>
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Edit Mode Banner & Reset Action (Active Mutation Mode) -->
            <div
                v-if="canMutate"
                class="mt-4 flex flex-col justify-between gap-3 rounded-2xl border border-indigo-500/30 bg-indigo-500/10 p-3.5 text-xs text-indigo-900 sm:flex-row sm:items-center dark:text-indigo-200"
            >
                <div class="flex items-center gap-2.5">
                    <Move
                        class="size-4 shrink-0 text-indigo-600 dark:text-indigo-400"
                    />
                    <span>
                        <strong>Drag & Drop & Edit Aktif:</strong> Tarik kartu
                        unit mana saja untuk memindahkannya. Jika salah
                        memindahkan bagian, tombol <strong>Undo</strong> atau
                        menu <strong>Pindah</strong> dapat digunakan untuk
                        mengembalikan posisi dengan 1 klik.
                    </span>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <Button
                        size="sm"
                        variant="outline"
                        class="h-7 gap-1 rounded-lg border-amber-500/40 bg-background text-[11px] text-amber-700 hover:bg-amber-50 dark:text-amber-300 dark:hover:bg-amber-950/40"
                        title="Kembalikan penempatan 8 bagian ke asisten standar Pemerintah Kota"
                        @click="isResetModalOpen = true"
                    >
                        <RefreshCw
                            class="size-3 text-amber-600 dark:text-amber-400"
                        />
                        <span>Reset Susunan Standar</span>
                    </Button>

                    <Button
                        size="sm"
                        variant="outline"
                        class="h-7 gap-1 rounded-lg bg-background text-[11px]"
                        @click="emit('createUnit', null)"
                    >
                        <Plus class="size-3" />
                        <span>+ Unit Induk Baru</span>
                    </Button>
                </div>
            </div>

            <!-- Locked Mode Notice if canMutate is false -->
            <div
                v-else
                class="mt-4 flex flex-col justify-between gap-3 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-3.5 text-xs text-amber-900 sm:flex-row sm:items-center dark:text-amber-200"
            >
                <div class="flex items-center gap-2.5">
                    <Lock
                        class="size-4 shrink-0 text-amber-600 dark:text-amber-400"
                    />
                    <span>
                        <strong>Mode Pratinjau (Terkunci):</strong> Bagan saat
                        ini dalam mode baca. Untuk melakukan
                        <strong>Drag & Drop</strong>, mengubah hierarki, atau
                        mereset posisi bagian, silakan buka kunci pengaturan
                        dengan memasukkan kata sandi Anda.
                    </span>
                </div>

                <Button
                    size="sm"
                    class="h-7 shrink-0 gap-1.5 rounded-lg bg-amber-600 text-[11px] font-bold text-white shadow-xs hover:bg-amber-700"
                    @click="isConfirmPasswordModalOpen = true"
                >
                    <KeyRound class="size-3" />
                    <span>Buka Kunci Sekarang</span>
                </Button>
            </div>

            <!-- Controls: Search, Branch Shape Switcher, View Modes & Zoom -->
            <div
                class="mt-5 flex flex-col gap-3 border-t border-border/60 pt-4 xl:flex-row xl:items-center xl:justify-between"
            >
                <!-- Search Box -->
                <div class="relative w-full xl:max-w-xs">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari unit, jabatan, atau pejabat..."
                        class="h-9.5 rounded-xl pl-9 text-xs"
                    />
                </div>

                <!-- Shape & Layout Switchers -->
                <div class="flex flex-wrap items-center gap-2.5">
                    <!-- Bentuk Cabang Bagan Switcher (Bercabang Horizontal vs Bersusun Vertikal) -->
                    <div
                        class="flex items-center rounded-xl border border-indigo-500/40 bg-indigo-500/5 p-0.5 text-xs"
                    >
                        <button
                            type="button"
                            class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 font-bold transition-all"
                            :class="[
                                globalBranchLayout === 'horizontal'
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground',
                            ]"
                            title="Tampilkan bagian-bagian berdampingan secara horizontal dengan garis cabang (┌──┬──┐)"
                            @click="setAllBranchesLayout('horizontal')"
                        >
                            <Columns3 class="size-3.5" />
                            <span>Cabang Horizontal (┌─┬─┐)</span>
                        </button>
                        <button
                            type="button"
                            class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 font-bold transition-all"
                            :class="[
                                globalBranchLayout === 'vertical'
                                    ? 'bg-indigo-600 text-white shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground',
                            ]"
                            title="Tampilkan bagian-bagian bersusun ke bawah secara vertikal"
                            @click="setAllBranchesLayout('vertical')"
                        >
                            <Rows3 class="size-3.5" />
                            <span>Cabang Vertikal</span>
                        </button>
                    </div>

                    <!-- Filter View Modes -->
                    <div
                        class="flex rounded-xl border border-border/70 bg-muted/40 p-0.5 text-xs"
                    >
                        <button
                            type="button"
                            class="rounded-lg px-2 py-1 font-semibold transition-colors"
                            :class="[
                                viewMode === 'all'
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground',
                            ]"
                            @click="viewMode = 'all'"
                        >
                            Semua
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-2 py-1 font-semibold transition-colors"
                            :class="[
                                viewMode === 'units_only'
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground',
                            ]"
                            @click="viewMode = 'units_only'"
                        >
                            Unit
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-2 py-1 font-semibold transition-colors"
                            :class="[
                                viewMode === 'occupied_only'
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground',
                            ]"
                            @click="viewMode = 'occupied_only'"
                        >
                            Terisi
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-2 py-1 font-semibold transition-colors"
                            :class="[
                                viewMode === 'vacant_only'
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground',
                            ]"
                            @click="viewMode = 'vacant_only'"
                        >
                            Lowong
                        </button>
                    </div>

                    <!-- Expand / Collapse All -->
                    <Button
                        size="sm"
                        variant="outline"
                        class="h-9 gap-1.5 rounded-xl text-xs"
                        @click="
                            collapsedUnitIds.size > 0
                                ? expandAll()
                                : collapseAll()
                        "
                    >
                        <Maximize2
                            v-if="collapsedUnitIds.size > 0"
                            class="size-3.5"
                        />
                        <Minimize2 v-else class="size-3.5" />
                        <span>{{
                            collapsedUnitIds.size > 0 ? 'Buka Sub' : 'Tutup Sub'
                        }}</span>
                    </Button>

                    <!-- Zoom Controls -->
                    <div
                        class="flex items-center gap-1 rounded-xl border border-border/70 bg-muted/40 p-0.5"
                    >
                        <Button
                            size="icon"
                            variant="ghost"
                            class="size-8 rounded-lg"
                            :disabled="zoomLevel <= 0.6"
                            @click="zoomOut"
                        >
                            <ZoomOut class="size-3.5" />
                        </Button>
                        <span
                            class="w-11 text-center font-mono text-xs font-semibold"
                        >
                            {{ Math.round(zoomLevel * 100) }}%
                        </span>
                        <Button
                            size="icon"
                            variant="ghost"
                            class="size-8 rounded-lg"
                            :disabled="zoomLevel >= 1.4"
                            @click="zoomIn"
                        >
                            <ZoomIn class="size-3.5" />
                        </Button>
                        <Button
                            size="icon"
                            variant="ghost"
                            class="size-8 rounded-lg"
                            title="Reset Zoom"
                            @click="resetZoom"
                        >
                            <RotateCcw class="size-3.5" />
                        </Button>
                    </div>
                </div>
            </div>
        </section>

        <!-- ======================================================== -->
        <!-- 2. ORGANOGRAM CANVAS (TREE STRUCTURE WITH BUS BARS)      -->
        <!-- ======================================================== -->
        <section
            class="relative min-h-[700px] overflow-auto rounded-3xl border border-border/80 bg-slate-50/50 p-6 shadow-inner backdrop-blur-sm sm:p-10 dark:border-border/60 dark:bg-slate-950/60"
        >
            <!-- Canvas Ambient Pattern -->
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 [background-image:radial-gradient(#94a3b8_1px,transparent_1px)] [background-size:24px_24px] opacity-25 dark:[background-image:radial-gradient(#334155_1px,transparent_1px)]"
            />

            <!-- Empty State -->
            <div
                v-if="
                    tree.root_units.length === 0 &&
                    tree.unassigned_positions.length === 0
                "
                class="relative z-10 flex min-h-[400px] flex-col items-center justify-center p-8 text-center"
            >
                <div
                    class="flex size-14 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400"
                >
                    <Building2 class="size-7" />
                </div>
                <h3 class="mt-4 text-lg font-bold text-foreground">
                    Belum Ada Struktur Unit Organisasi
                </h3>
                <p class="mt-1 max-w-sm text-xs text-muted-foreground">
                    Struktur bagan organisasi belum dikonfigurasi. Buat unit
                    organisasi pertama untuk menyusun hierarki.
                </p>
                <Button
                    v-if="canMutate"
                    class="mt-5 gap-1.5 rounded-xl bg-indigo-600 font-semibold text-white"
                    @click="emit('createUnit', null)"
                >
                    <Plus class="size-4" />
                    <span>Tambah Unit Utama</span>
                </Button>
            </div>

            <!-- Organogram Viewport Container with Zoom Transform -->
            <div
                v-else
                class="relative z-10 mx-auto flex w-max min-w-full origin-top flex-col items-center gap-0 pb-12 transition-transform duration-200"
                :style="{ transform: `scale(${zoomLevel})` }"
            >
                <!-- ======================================================== -->
                <!-- TIER 1: PUCUK PIMPINAN DAERAH (WALI KOTA)                -->
                <!-- ======================================================== -->
                <div v-if="waliKotaNode" class="flex flex-col items-center">
                    <ExecutiveTierCard
                        :node="waliKotaNode"
                        tier="walikota"
                        :search-query="searchQuery"
                        :is-edit-mode="isEditMode"
                        :can-mutate="canMutate"
                        :is-dragged="draggedUnit?.id === waliKotaNode.id"
                        :is-drop-target="hoveredTargetId === waliKotaNode.id"
                        :is-drop-valid="validDropTargetIds.has(waliKotaNode.id)"
                        :dragged-unit="draggedUnit"
                        @inspect-unit="inspectUnit"
                        @inspect-position="inspectPosition"
                        @add-sub-unit="openAddSubUnit"
                        @add-position="openAddPosition"
                        @reparent-unit="openReparent"
                        @edit-unit="openEditUnit"
                        @drag-start="onDragStart"
                        @drag-end="onDragEnd"
                        @drag-over="onDragOver"
                        @drag-leave="onDragLeave"
                        @drop="onDrop"
                    />

                    <!-- Stem Down from Wali Kota to Sekda (│) -->
                    <div class="flex flex-col items-center">
                        <div
                            class="h-8 w-0.5 bg-gradient-to-b from-amber-500 to-indigo-500"
                        />
                        <div
                            class="size-2 rounded-full bg-indigo-600 shadow-sm shadow-indigo-500/50"
                        />
                    </div>
                </div>

                <!-- ======================================================== -->
                <!-- TIER 2: SEKRETARIAT DAERAH (SEKDA)                       -->
                <!-- ======================================================== -->
                <div v-if="sekdaNode" class="flex flex-col items-center">
                    <ExecutiveTierCard
                        :node="sekdaNode"
                        tier="sekda"
                        :search-query="searchQuery"
                        :is-edit-mode="isEditMode"
                        :can-mutate="canMutate"
                        :is-dragged="draggedUnit?.id === sekdaNode.id"
                        :is-drop-target="hoveredTargetId === sekdaNode.id"
                        :is-drop-valid="validDropTargetIds.has(sekdaNode.id)"
                        :dragged-unit="draggedUnit"
                        @inspect-unit="inspectUnit"
                        @inspect-position="inspectPosition"
                        @add-sub-unit="openAddSubUnit"
                        @add-position="openAddPosition"
                        @reparent-unit="openReparent"
                        @edit-unit="openEditUnit"
                        @drag-start="onDragStart"
                        @drag-end="onDragEnd"
                        @drag-over="onDragOver"
                        @drag-leave="onDragLeave"
                        @drop="onDrop"
                    />

                    <!-- Stem Down from Sekda to Asistens Bus Bar (│) -->
                    <div class="h-8 w-0.5 bg-indigo-500" />
                </div>

                <!-- ======================================================== -->
                <!-- TIER 3 & 4: 3 ASISTEN & SUBORDINATE BAGIAN (TREE BUS)    -->
                <!-- ======================================================== -->
                <div
                    v-if="assistantNodes.length > 0"
                    class="flex items-start justify-center pt-0"
                >
                    <div
                        v-for="(asisten, idx) in assistantNodes"
                        :key="asisten.id"
                        class="relative flex flex-col items-center px-4 sm:px-8 xl:px-12"
                    >
                        <!-- Horizontal Crossbar connecting 3 Asistens (┌─────┼─────┐) -->
                        <div
                            v-if="assistantNodes.length > 1"
                            class="absolute top-0 h-0.5 bg-indigo-500"
                            :class="[
                                idx === 0 ? 'right-0 left-1/2' : '',
                                idx === assistantNodes.length - 1
                                    ? 'right-1/2 left-0'
                                    : '',
                                idx > 0 && idx < assistantNodes.length - 1
                                    ? 'right-0 left-0'
                                    : '',
                            ]"
                        />

                        <!-- Drop stem down to Asisten card (│) -->
                        <div class="h-8 w-0.5 bg-indigo-500" />

                        <!-- Assistant Branch Tree (Asisten Card + Bagian-Bagian) -->
                        <AssistantBranchTree
                            :node="asisten"
                            :pillar-index="idx"
                            :search-query="searchQuery"
                            :view-mode="viewMode"
                            :collapsed-unit-ids="collapsedUnitIds"
                            :is-edit-mode="isEditMode"
                            :can-mutate="canMutate"
                            :branch-layout="getBranchLayout(asisten.id)"
                            :dragged-unit-id="draggedUnit?.id"
                            :dragged-unit="draggedUnit"
                            :hovered-target-id="hoveredTargetId"
                            :valid-drop-target-ids="validDropTargetIds"
                            @inspect-unit="inspectUnit"
                            @inspect-position="inspectPosition"
                            @toggle-collapse="toggleCollapse"
                            @add-sub-unit="openAddSubUnit"
                            @add-position="openAddPosition"
                            @reparent-unit="openReparent"
                            @edit-unit="openEditUnit"
                            @toggle-branch-layout="toggleBranchLayout"
                            @drag-start="onDragStart"
                            @drag-end="onDragEnd"
                            @drag-over="onDragOver"
                            @drag-leave="onDragLeave"
                            @drop="onDrop"
                        />
                    </div>
                </div>

                <!-- ======================================================== -->
                <!-- OTHER ROOT UNITS / ANOMALOUS BRANCHES (IF ANY)           -->
                <!-- ======================================================== -->
                <div
                    v-if="otherRootUnits.length > 0"
                    class="mt-12 w-full border-t border-border/60 pt-8"
                >
                    <div class="mb-4 text-center">
                        <span
                            class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                        >
                            Unit / Lembaga Lainnya
                        </span>
                    </div>

                    <div class="flex flex-wrap justify-center gap-8">
                        <OrganizationTreeNodeBranch
                            v-for="unit in otherRootUnits"
                            :key="unit.id"
                            :node="unit"
                            :depth="0"
                            :search-query="searchQuery"
                            :view-mode="viewMode"
                            :collapsed-unit-ids="collapsedUnitIds"
                            @inspect-unit="inspectUnit"
                            @inspect-position="inspectPosition"
                            @toggle-collapse="toggleCollapse"
                        />
                    </div>
                </div>
            </div>
        </section>

        <!-- ======================================================== -->
        <!-- 3. FLOATING UNDO ACTION TOAST BAR                        -->
        <!-- ======================================================== -->
        <transition
            enter-active-class="transform transition ease-out duration-300"
            enter-from-class="translate-y-8 opacity-0 scale-95"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="translate-y-8 opacity-0 scale-95"
        >
            <div
                v-if="showUndoToast && latestMove"
                class="fixed bottom-6 left-1/2 z-50 flex -translate-x-1/2 items-center gap-3 rounded-2xl border border-indigo-500/40 bg-card/95 p-3.5 pr-4 shadow-2xl ring-1 ring-black/10 backdrop-blur-xl dark:bg-slate-900/95"
            >
                <div
                    class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-indigo-500/15 font-bold text-indigo-600 dark:text-indigo-400"
                >
                    <Undo2 class="size-4" />
                </div>

                <div class="text-xs">
                    <p class="font-bold text-foreground">
                        {{ latestMove.sourceName }} berhasil dipindahkan
                    </p>
                    <p class="text-[11px] text-muted-foreground">
                        Dari
                        <span class="font-semibold text-foreground">{{
                            latestMove.previousParentName
                        }}</span>
                        &rarr; ke
                        <span
                            class="font-semibold text-indigo-600 dark:text-indigo-400"
                            >{{ latestMove.newParentName }}</span
                        >
                    </p>
                </div>

                <div class="ml-2 flex items-center gap-2">
                    <Button
                        size="sm"
                        class="h-8 gap-1.5 rounded-xl bg-indigo-600 text-xs font-bold text-white shadow-md hover:bg-indigo-700"
                        :disabled="isUndoing"
                        @click="undoMove(latestMove)"
                    >
                        <Spinner v-if="isUndoing" class="size-3" />
                        <RotateCcw v-else class="size-3" />
                        <span>Batalkan (Undo)</span>
                    </Button>

                    <Button
                        size="icon"
                        variant="ghost"
                        class="size-8 rounded-xl text-muted-foreground hover:text-foreground"
                        @click="showUndoToast = false"
                    >
                        <X class="size-4" />
                    </Button>
                </div>
            </div>
        </transition>

        <!-- ======================================================== -->
        <!-- 4. DRAG & DROP RE-PARENT CONFIRMATION MODAL              -->
        <!-- ======================================================== -->
        <Dialog v-model:open="isDropConfirmOpen">
            <DialogContent class="rounded-3xl p-6 sm:max-w-lg">
                <DialogHeader>
                    <div
                        class="flex items-center gap-2 font-mono text-xs font-bold text-indigo-600 uppercase dark:text-indigo-400"
                    >
                        <Move class="size-4" />
                        <span>Konfirmasi Pemindahan (Drag & Drop)</span>
                    </div>
                    <DialogTitle class="text-xl font-bold">
                        Pindahkan Posisi Unit Organisasi?
                    </DialogTitle>
                    <DialogDescription class="text-xs">
                        Tindakan ini akan memindahkan unit kerja dan seluruh
                        jajaran di bawahnya ke unit induk yang baru.
                    </DialogDescription>
                </DialogHeader>

                <div v-if="dropConfirmData" class="mt-4 space-y-4">
                    <!-- Visual Transfer Route -->
                    <div
                        class="rounded-2xl border border-indigo-500/20 bg-indigo-500/5 p-4"
                    >
                        <div
                            class="grid grid-cols-1 gap-3 text-xs sm:grid-cols-2"
                        >
                            <div
                                class="rounded-xl border border-border/70 bg-background p-3 shadow-xs"
                            >
                                <span
                                    class="block text-[10px] font-bold text-muted-foreground uppercase"
                                >
                                    Unit yang Dipindahkan:
                                </span>
                                <p
                                    class="mt-1 text-sm font-bold text-foreground"
                                >
                                    {{ dropConfirmData.source.name }}
                                </p>
                                <span
                                    class="font-mono text-[10px] text-muted-foreground"
                                >
                                    {{
                                        dropConfirmData.source.code || 'NO-CODE'
                                    }}
                                </span>
                            </div>

                            <div
                                class="rounded-xl border border-emerald-500/40 bg-background p-3 shadow-xs"
                            >
                                <span
                                    class="block text-[10px] font-bold text-emerald-600 uppercase dark:text-emerald-400"
                                >
                                    Menjadi Bawahan dari:
                                </span>
                                <p
                                    class="mt-1 text-sm font-bold text-emerald-700 dark:text-emerald-300"
                                >
                                    {{ dropConfirmData.target.name }}
                                </p>
                                <span
                                    class="font-mono text-[10px] text-muted-foreground"
                                >
                                    {{
                                        dropConfirmData.target.code || 'NO-CODE'
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Error if any -->
                    <div
                        v-if="dropError"
                        class="flex items-start gap-2.5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-700 dark:text-rose-300"
                    >
                        <ShieldAlert class="mt-0.5 size-4 shrink-0" />
                        <span>{{ dropError }}</span>
                    </div>
                </div>

                <DialogFooter class="mt-6 flex items-center justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        class="rounded-xl text-xs"
                        :disabled="isDropping"
                        @click="dropConfirmData = null"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        class="gap-1.5 rounded-xl bg-indigo-600 text-xs font-semibold text-white shadow-md hover:bg-indigo-700"
                        :disabled="isDropping"
                        @click="confirmDropMove"
                    >
                        <Spinner v-if="isDropping" class="size-3.5" />
                        <Check v-else class="size-3.5" />
                        <span>Ya, Pindahkan Unit</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ======================================================== -->
        <!-- 5. REPARENT MODAL DIALOG (VIA BUTTON)                    -->
        <!-- ======================================================== -->
        <ReparentUnitDialog
            v-model:open="isReparentOpen"
            :unit="unitToReparent"
            :all-units="computedAllUnits"
            :tree-root-units="tree.root_units"
            @success="onReparentSuccess"
        />

        <!-- ======================================================== -->
        <!-- 6. RESET DEFAULT MUNICIPAL STRUCTURE CONFIRMATION MODAL   -->
        <!-- ======================================================== -->
        <Dialog v-model:open="isResetModalOpen">
            <DialogContent class="rounded-3xl p-6 sm:max-w-lg">
                <DialogHeader>
                    <div
                        class="flex items-center gap-2 font-mono text-xs font-bold text-amber-600 uppercase dark:text-amber-400"
                    >
                        <RefreshCw class="size-4" />
                        <span>Reset Susunan Standar</span>
                    </div>
                    <DialogTitle class="text-xl font-bold">
                        Kembalikan ke Susunan Bagan Standar Pemkot?
                    </DialogTitle>
                    <DialogDescription class="text-xs">
                        Tindakan ini akan mengembalikan penempatan 8 Bagian ke
                        posisi Asisten I, II, dan III default Pemerintah Kota
                        Baubau.
                    </DialogDescription>
                </DialogHeader>

                <div class="mt-4 space-y-3 text-xs">
                    <div
                        v-if="displacedDefaultUnits.length > 0"
                        class="space-y-2 rounded-2xl border border-amber-500/30 bg-amber-500/10 p-3.5 text-amber-900 dark:text-amber-200"
                    >
                        <span class="block font-bold">
                            Ditemukan {{ displacedDefaultUnits.length }} Bagian
                            yang posisinya telah dipindahkan:
                        </span>
                        <ul class="list-disc space-y-1 pl-4 text-[11px]">
                            <li
                                v-for="item in displacedDefaultUnits"
                                :key="item.unit.id"
                            >
                                <strong>{{ item.unit.name }}</strong> &rarr;
                                akan dikembalikan ke
                                <strong>{{ item.expectedParent?.name }}</strong>
                            </li>
                        </ul>
                    </div>

                    <div
                        v-else
                        class="rounded-2xl border border-emerald-500/30 bg-emerald-500/10 p-3.5 font-medium text-emerald-800 dark:text-emerald-300"
                    >
                        Susunan bagan saat ini sudah sesuai dengan posisi
                        standar default 3 Asisten dan 8 Bagian Pemerintah Kota.
                    </div>

                    <!-- Standard Layout Overview -->
                    <div
                        class="space-y-1.5 rounded-2xl border border-border/70 bg-muted/40 p-3 text-[11px] text-muted-foreground"
                    >
                        <span class="block font-bold text-foreground"
                            >Format Standar Pemkot Baubau:</span
                        >
                        <p>
                            🌿 <strong>Asisten I:</strong> Bagian Tata
                            Pemerintahan, Bagian Kesra, Bagian Hukum
                        </p>
                        <p>
                            🌊 <strong>Asisten II:</strong> Bagian Perekonomian,
                            Bagian Pembangunan
                        </p>
                        <p>
                            🔮 <strong>Asisten III:</strong> Bagian Umum, Bagian
                            Organisasi, Bagian Protokol & Komunikasi Pimpinan
                        </p>
                    </div>
                </div>

                <DialogFooter class="mt-6 flex items-center justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        class="rounded-xl text-xs"
                        :disabled="isResetting"
                        @click="isResetModalOpen = false"
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        class="gap-1.5 rounded-xl bg-amber-600 text-xs font-semibold text-white shadow-md hover:bg-amber-700"
                        :disabled="
                            isResetting || displacedDefaultUnits.length === 0
                        "
                        @click="executeResetDefault"
                    >
                        <Spinner v-if="isResetting" class="size-3.5" />
                        <RefreshCw v-else class="size-3.5" />
                        <span>Kembalikan ke Standar</span>
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- ======================================================== -->
        <!-- 7. NODE INSPECTOR MODAL DIALOG                           -->
        <!-- ======================================================== -->
        <Dialog v-model:open="isInspectorOpen">
            <DialogContent class="rounded-3xl p-6 sm:max-w-xl">
                <!-- UNIT INSPECTION -->
                <template
                    v-if="inspectedNode?.type === 'unit' && inspectedNode.unit"
                >
                    <DialogHeader>
                        <div
                            class="flex items-center gap-2 font-mono text-xs font-bold text-indigo-600 uppercase dark:text-indigo-400"
                        >
                            <Building2 class="size-4" />
                            <span>Rincian Unit / Bagian Organisasi</span>
                        </div>
                        <DialogTitle class="text-xl font-bold">
                            {{ inspectedNode.unit.name }}
                        </DialogTitle>
                        <DialogDescription class="text-xs">
                            Kode:
                            {{
                                inspectedNode.unit.code ||
                                'Tidak ada kode khusus'
                            }}
                            · Status:
                            {{
                                inspectedNode.unit.is_active
                                    ? 'Aktif'
                                    : 'Nonaktif'
                            }}
                        </DialogDescription>
                    </DialogHeader>

                    <div class="mt-4 space-y-4">
                        <!-- Induk Unit Info & Move Trigger -->
                        <div
                            class="rounded-2xl border border-border/70 bg-muted/30 p-4"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <span
                                        class="block text-[10px] font-bold text-muted-foreground uppercase"
                                    >
                                        Unit Induk Langsung
                                    </span>
                                    <p
                                        class="mt-0.5 text-xs font-bold text-foreground"
                                    >
                                        {{
                                            inspectedNode.unit.parent_id
                                                ? (computedAllUnits.find(
                                                      (u) =>
                                                          u.id ===
                                                          inspectedNode?.unit
                                                              ?.parent_id,
                                                  )?.name ??
                                                  `Unit #${inspectedNode.unit.parent_id}`)
                                                : 'Tingkat Utama (Tanpa Induk)'
                                        }}
                                    </p>
                                </div>

                                <Button
                                    v-if="canMutate"
                                    size="sm"
                                    variant="outline"
                                    class="h-8 gap-1.5 rounded-xl border-violet-500/30 text-xs text-violet-700 hover:bg-violet-50 dark:text-violet-300 dark:hover:bg-violet-950/50"
                                    @click="openReparent(inspectedNode.unit)"
                                >
                                    <GitFork class="size-3.5" />
                                    <span>Pindah Induk</span>
                                </Button>
                            </div>
                        </div>

                        <!-- Sub-units summary -->
                        <div
                            class="rounded-2xl border border-border/70 bg-muted/30 p-4"
                        >
                            <div class="flex items-center justify-between">
                                <h4
                                    class="text-xs font-bold tracking-wider text-foreground uppercase"
                                >
                                    Sub-bagian / Unit Anak ({{
                                        inspectedNode.unit.children?.length ||
                                        0
                                    }})
                                </h4>
                                <Button
                                    v-if="canMutate"
                                    size="sm"
                                    variant="outline"
                                    class="h-7 gap-1 rounded-lg border-indigo-500/30 text-[11px] text-indigo-700 dark:text-indigo-300"
                                    @click="openAddSubUnit(inspectedNode.unit)"
                                >
                                    <Plus class="size-3" />
                                    <span>+ Sub-Unit</span>
                                </Button>
                            </div>

                            <div
                                v-if="
                                    inspectedNode.unit.children &&
                                    inspectedNode.unit.children.length > 0
                                "
                                class="mt-2.5 flex flex-wrap gap-2"
                            >
                                <span
                                    v-for="sub in inspectedNode.unit.children"
                                    :key="sub.id"
                                    class="rounded-xl border border-border/60 bg-background px-3 py-1 text-xs font-medium text-foreground"
                                >
                                    {{ sub.name }}
                                </span>
                            </div>
                            <p
                                v-else
                                class="mt-2 text-xs text-muted-foreground"
                            >
                                Unit ini belum memiliki anak sub-bagian
                                operasional langsung.
                            </p>
                        </div>

                        <!-- Positions inside unit -->
                        <div
                            class="rounded-2xl border border-border/70 bg-muted/30 p-4"
                        >
                            <div class="flex items-center justify-between">
                                <h4
                                    class="text-xs font-bold tracking-wider text-foreground uppercase"
                                >
                                    Daftar Jabatan Kerja ({{
                                        inspectedNode.unit.positions.length
                                    }})
                                </h4>
                                <Button
                                    v-if="canMutate"
                                    size="sm"
                                    variant="outline"
                                    class="h-7 gap-1 rounded-lg border-emerald-500/30 text-[11px] text-emerald-700 dark:text-emerald-300"
                                    @click="openAddPosition(inspectedNode.unit)"
                                >
                                    <Plus class="size-3" />
                                    <span>+ Jabatan</span>
                                </Button>
                            </div>

                            <div
                                class="mt-3 max-h-56 space-y-2 overflow-y-auto pr-1"
                            >
                                <div
                                    v-for="p in inspectedNode.unit.positions"
                                    :key="p.id"
                                    class="flex items-center justify-between rounded-xl border border-border/60 bg-background p-3 text-xs"
                                >
                                    <div class="space-y-0.5">
                                        <p class="font-bold text-foreground">
                                            {{ p.name }}
                                        </p>
                                        <p
                                            class="font-mono text-[11px] text-muted-foreground"
                                        >
                                            {{ p.code }}
                                        </p>
                                        <div
                                            class="flex items-center gap-1.5 pt-1 text-[11px]"
                                        >
                                            <span class="text-muted-foreground"
                                                >Pejabat:</span
                                            >
                                            <span
                                                class="font-semibold"
                                                :class="
                                                    p.active_assignment
                                                        ? 'text-foreground'
                                                        : 'text-amber-600'
                                                "
                                            >
                                                {{
                                                    p.active_assignment
                                                        ? p.active_assignment
                                                              .user.name
                                                        : 'Lowong (Belum Ditugaskan)'
                                                }}
                                            </span>
                                        </div>
                                    </div>
                                    <Badge
                                        variant="outline"
                                        :class="
                                            getLevelBadgeClass(p.level.code)
                                        "
                                    >
                                        {{ p.level.name }}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <!-- Footer Actions -->
                        <div
                            class="flex items-center justify-between border-t border-border/60 pt-2"
                        >
                            <Button
                                v-if="canMutate"
                                size="sm"
                                variant="outline"
                                class="gap-1.5 rounded-xl text-xs"
                                @click="openEditUnit(inspectedNode.unit)"
                            >
                                <Pencil class="size-3" />
                                <span>Edit Nama Unit</span>
                            </Button>

                            <Link
                                :href="assignmentsRoute"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-md hover:bg-indigo-700"
                            >
                                <User class="size-3.5" />
                                <span>Kelola Pejabat Unit</span>
                            </Link>
                        </div>
                    </div>
                </template>

                <!-- POSITION INSPECTION -->
                <template
                    v-else-if="
                        inspectedNode?.type === 'position' &&
                        inspectedNode.position
                    "
                >
                    <DialogHeader>
                        <div
                            class="flex items-center gap-2 font-mono text-xs font-bold text-indigo-600 uppercase dark:text-indigo-400"
                        >
                            <Briefcase class="size-4" />
                            <span>Rincian Jabatan & Penugasan</span>
                        </div>
                        <DialogTitle class="text-xl font-bold">
                            {{ inspectedNode.position.name }}
                        </DialogTitle>
                        <DialogDescription class="text-xs">
                            Kode: {{ inspectedNode.position.code }} · Level:
                            {{ inspectedNode.position.level.name }}
                        </DialogDescription>
                    </DialogHeader>

                    <div class="mt-4 space-y-4">
                        <!-- Active Official Card -->
                        <div
                            class="space-y-3 rounded-2xl border border-border/70 bg-muted/30 p-4"
                        >
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-bold tracking-wider text-foreground uppercase"
                                    >Pejabat yang Bertugas</span
                                >
                                <Badge
                                    :variant="
                                        inspectedNode.position.active_assignment
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        inspectedNode.position.active_assignment
                                            ? 'Terisi'
                                            : 'Lowong'
                                    }}
                                </Badge>
                            </div>

                            <div
                                v-if="inspectedNode.position.active_assignment"
                                class="flex items-center gap-3 rounded-xl border border-border/60 bg-background p-3"
                            >
                                <div
                                    class="flex size-10 items-center justify-center rounded-xl bg-indigo-600 text-sm font-bold text-white"
                                >
                                    {{
                                        inspectedNode.position.active_assignment.user.name
                                            .charAt(0)
                                            .toUpperCase()
                                    }}
                                </div>
                                <div class="space-y-0.5">
                                    <p
                                        class="text-sm font-bold text-foreground"
                                    >
                                        {{
                                            inspectedNode.position
                                                .active_assignment.user.name
                                        }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{
                                            inspectedNode.position
                                                .active_assignment.user.email
                                        }}
                                    </p>
                                    <p
                                        class="pt-0.5 text-[10px] text-muted-foreground"
                                    >
                                        Mulai Menjabat:
                                        {{
                                            new Date(
                                                inspectedNode.position
                                                    .active_assignment
                                                    .started_at,
                                            ).toLocaleDateString('id-ID', {
                                                day: 'numeric',
                                                month: 'long',
                                                year: 'numeric',
                                            })
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-else
                                class="rounded-xl border border-dashed border-amber-500/40 bg-amber-500/5 p-3 text-xs text-amber-700 dark:text-amber-300"
                            >
                                Jabatan ini belum memiliki pejabat aktif. Anda
                                dapat menugaskan aparatur sipil melalui menu
                                Kelola Pejabat.
                            </div>
                        </div>

                        <!-- Workflow Level Invariant details -->
                        <div
                            class="space-y-2 rounded-2xl border border-border/70 bg-muted/30 p-4 text-xs"
                        >
                            <span
                                class="font-bold tracking-wider text-foreground uppercase"
                                >Peran Alur Disposisi</span
                            >
                            <div class="flex items-center justify-between pt-1">
                                <span class="text-muted-foreground"
                                    >Level Workflow:</span
                                >
                                <Badge
                                    variant="outline"
                                    :class="
                                        getLevelBadgeClass(
                                            inspectedNode.position.level.code,
                                        )
                                    "
                                >
                                    {{ inspectedNode.position.level.name }}
                                </Badge>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground"
                                    >Urutan Hierarki:</span
                                >
                                <span
                                    class="font-mono font-bold text-foreground"
                                    >Order
                                    {{
                                        inspectedNode.position.level
                                            .hierarchy_order
                                    }}</span
                                >
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <Link
                                :href="assignmentsRoute"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-md hover:bg-indigo-700"
                            >
                                <UserCheck class="size-3.5" />
                                <span>{{
                                    inspectedNode.position.active_assignment
                                        ? 'Ganti Pejabat'
                                        : 'Tugaskan Pejabat'
                                }}</span>
                            </Link>
                        </div>
                    </div>
                </template>
            </DialogContent>
        </Dialog>

        <!-- ======================================================== -->
        <!-- 8. STEP-UP SECURITY CONFIRM PASSWORD MODAL               -->
        <!-- ======================================================== -->
        <BackOfficeConfirmPasswordModal
            v-model:open="isConfirmPasswordModalOpen"
            title="Buka Kunci Pengaturan Bagan Organisasi"
            description="Masukkan kata sandi akun Anda untuk mengaktifkan mode drag & drop dan perubahan struktur bagan selama 15 menit."
            @confirmed="onPasswordConfirmed"
        />
    </div>
</template>
