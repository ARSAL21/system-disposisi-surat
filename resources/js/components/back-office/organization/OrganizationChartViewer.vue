<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Briefcase,
    Building2,
    Crown,
    FolderTree,
    Maximize2,
    Minimize2,
    Plus,
    RotateCcw,
    Search,
    User,
    UserCheck,
    UserX,
    ZoomIn,
    ZoomOut,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import OrganizationTreeNodeBranch from '@/components/back-office/organization/OrganizationTreeNodeBranch.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import type {
    OrganizationTreeData,
    OrganizationTreeNode,
    OrganizationTreePosition,
} from '@/types';

const props = defineProps<{
    tree: OrganizationTreeData;
    assignmentsRoute: string;
    canMutate: boolean;
}>();

const emit = defineEmits<{
    createUnit: [];
    createPosition: [];
}>();

// Filter & View Options
const viewMode = ref<'all' | 'units_only' | 'occupied_only' | 'vacant_only'>(
    'all',
);
const searchQuery = ref<string>('');
const zoomLevel = ref<number>(1);
const collapsedUnitIds = ref<Set<number>>(new Set());

// Modal Inspector State
const isInspectorOpen = ref<boolean>(false);
const inspectedNode = ref<{
    type: 'unit' | 'position';
    unit?: OrganizationTreeNode;
    position?: OrganizationTreePosition;
} | null>(null);

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

// Node Inspector Handlers
function inspectUnit(unit: OrganizationTreeNode) {
    inspectedNode.value = { type: 'unit', unit };
    isInspectorOpen.value = true;
}

function inspectPosition(position: OrganizationTreePosition) {
    inspectedNode.value = { type: 'position', position };
    isInspectorOpen.value = true;
}

// Helpers
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

function isPositionMatched(pos: OrganizationTreePosition): boolean {
    if (!searchQuery.value) {
return true;
}

    const query = searchQuery.value.toLowerCase();

    return (
        pos.name.toLowerCase().includes(query) ||
        pos.code.toLowerCase().includes(query) ||
        (pos.active_assignment?.user.name.toLowerCase().includes(query) ?? false)
    );
}

// Statistics Overview
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
</script>

<template>
    <div class="space-y-6">
        <!-- ======================================================== -->
        <!-- 1. TOOLBAR & INTERACTIVE CONTROLS                        -->
        <!-- ======================================================== -->
        <section
            class="relative overflow-hidden rounded-3xl border border-border/80 bg-card p-5 shadow-lg shadow-black/5 backdrop-blur-2xl sm:p-6 dark:border-border/60 dark:bg-slate-900/90"
        >
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <!-- Title & Meta -->
                <div>
                    <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                        <FolderTree class="size-4" />
                        <span>Visualisasi Struktur & Bagan Organisasi</span>
                    </div>
                    <h3 class="mt-1 text-xl font-bold tracking-tight text-foreground sm:text-2xl">
                        Bagan Lengkap Seluruh Unit & Pejabat
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        Peta visual lengkap hubungan antar seluruh bagian, bidang, subbagian, dan aparatur pemegang jabatan.
                    </p>
                </div>

                <!-- Stats summary chips -->
                <div class="flex flex-wrap items-center gap-2 text-xs">
                    <div class="rounded-xl border border-border/70 bg-muted/40 px-3 py-1.5">
                        <span class="text-muted-foreground">Total Bagian / Unit: </span>
                        <span class="font-bold text-foreground">{{ totalUnitsCount }}</span>
                    </div>
                    <div class="rounded-xl border border-border/70 bg-muted/40 px-3 py-1.5">
                        <span class="text-muted-foreground">Total Jabatan: </span>
                        <span class="font-bold text-foreground">{{ totalPositionsCount }}</span>
                    </div>
                    <div class="rounded-xl border border-border/70 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300 px-3 py-1.5">
                        <span class="font-medium">Terisi: </span>
                        <span class="font-bold">{{ occupiedPositionsCount }}</span>
                    </div>
                    <div class="rounded-xl border border-border/70 bg-amber-500/10 text-amber-700 dark:text-amber-300 px-3 py-1.5">
                        <span class="font-medium">Lowong: </span>
                        <span class="font-bold">{{ totalPositionsCount - occupiedPositionsCount }}</span>
                    </div>
                </div>
            </div>

            <!-- Second Row: Filters, Search, and Zoom Controls -->
            <div class="mt-5 flex flex-col gap-3 pt-4 border-t border-border/60 md:flex-row md:items-center md:justify-between">
                <!-- Search Box -->
                <div class="relative w-full md:max-w-xs">
                    <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="searchQuery"
                        type="text"
                        placeholder="Cari bagian, jabatan, atau pejabat..."
                        class="h-9.5 rounded-xl pl-9 text-xs"
                    />
                </div>

                <!-- Filter View Modes -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex rounded-xl border border-border/70 bg-muted/40 p-0.5 text-xs">
                        <button
                            type="button"
                            class="rounded-lg px-2.5 py-1 font-semibold transition-colors"
                            :class="[
                                viewMode === 'all'
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground',
                            ]"
                            @click="viewMode = 'all'"
                        >
                            Semua Bagian
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-2.5 py-1 font-semibold transition-colors"
                            :class="[
                                viewMode === 'units_only'
                                    ? 'bg-background text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground',
                            ]"
                            @click="viewMode = 'units_only'"
                        >
                            Unit Saja
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-2.5 py-1 font-semibold transition-colors"
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
                            class="rounded-lg px-2.5 py-1 font-semibold transition-colors"
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
                        class="h-9 rounded-xl text-xs gap-1.5"
                        @click="collapsedUnitIds.size > 0 ? expandAll() : collapseAll()"
                    >
                        <Maximize2 v-if="collapsedUnitIds.size > 0" class="size-3.5" />
                        <Minimize2 v-else class="size-3.5" />
                        <span>{{ collapsedUnitIds.size > 0 ? 'Buka Semua Cabang' : 'Tutup Sub-bagian' }}</span>
                    </Button>

                    <!-- Zoom Controls -->
                    <div class="flex items-center gap-1 rounded-xl border border-border/70 bg-muted/40 p-0.5">
                        <Button
                            size="icon"
                            variant="ghost"
                            class="size-8 rounded-lg"
                            :disabled="zoomLevel <= 0.6"
                            @click="zoomOut"
                        >
                            <ZoomOut class="size-3.5" />
                        </Button>
                        <span class="w-12 text-center font-mono text-xs font-semibold">
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
        <!-- 2. ORGANOGRAM CANVAS / INTERACTIVE CHART VIEW            -->
        <!-- ======================================================== -->
        <section
            class="relative min-h-[520px] overflow-auto rounded-3xl border border-border/80 bg-slate-50/50 p-6 shadow-inner backdrop-blur-sm sm:p-10 dark:border-border/60 dark:bg-slate-950/60"
        >
            <!-- Canvas Ambient Pattern -->
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 [background-image:radial-gradient(#94a3b8_1px,transparent_1px)] [background-size:24px_24px] opacity-25 dark:[background-image:radial-gradient(#334155_1px,transparent_1px)]"
            />

            <!-- Empty State if no tree data -->
            <div
                v-if="tree.root_units.length === 0 && tree.unassigned_positions.length === 0"
                class="relative z-10 flex min-h-[380px] flex-col items-center justify-center text-center p-8"
            >
                <div class="flex size-14 items-center justify-center rounded-2xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                    <Building2 class="size-7" />
                </div>
                <h3 class="mt-4 text-lg font-bold text-foreground">
                    Belum Ada Unit Organisasi
                </h3>
                <p class="mt-1 max-w-sm text-xs text-muted-foreground">
                    Struktur bagan organisasi belum dikonfigurasi. Buat unit organisasi pertama untuk menyusun bagan hirarki.
                </p>
                <Button
                    v-if="canMutate"
                    class="mt-5 gap-1.5 rounded-xl bg-indigo-600 font-semibold text-white"
                    @click="emit('createUnit')"
                >
                    <Plus class="size-4" />
                    <span>Tambah Unit Utama</span>
                </Button>
            </div>

            <!-- Tree Viewport Container with Zoom Transform -->
            <div
                v-else
                class="relative z-10 mx-auto flex w-max min-w-full flex-col items-center gap-10 transition-transform duration-200 origin-top"
                :style="{ transform: `scale(${zoomLevel})` }"
            >
                <!-- ======================================================== -->
                <!-- TIER 0: UNASSIGNED EXECUTIVE POSITIONS (e.g. Wali Kota) -->
                <!-- ======================================================== -->
                <div
                    v-if="tree.unassigned_positions.length > 0 && viewMode !== 'units_only'"
                    class="flex flex-col items-center"
                >
                    <div class="mb-3 flex items-center gap-2">
                        <span class="inline-flex items-center gap-1 rounded-full border border-amber-500/30 bg-amber-500/10 px-2.5 py-0.5 text-[11px] font-bold text-amber-700 dark:border-amber-400/30 dark:bg-amber-400/10 dark:text-amber-300">
                            <Crown class="size-3" />
                            <span>Pimpinan Eksekutif / Non-Unit</span>
                        </span>
                    </div>

                    <div class="flex flex-wrap justify-center gap-4">
                        <div
                            v-for="pos in tree.unassigned_positions"
                            :key="pos.id"
                            class="group relative w-72 cursor-pointer rounded-2xl border border-amber-500/40 bg-card p-4 shadow-md transition-all duration-300 hover:-translate-y-1 hover:border-amber-500 hover:shadow-xl dark:border-amber-500/30 dark:bg-slate-900"
                            :class="{ 'ring-2 ring-indigo-500': isPositionMatched(pos) && searchQuery }"
                            @click="inspectPosition(pos)"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <Badge
                                    variant="outline"
                                    class="text-[10px] font-semibold"
                                    :class="getLevelBadgeClass(pos.level.code)"
                                >
                                    {{ pos.level.name }}
                                </Badge>
                                <span class="font-mono text-[10px] text-muted-foreground">{{ pos.code }}</span>
                            </div>

                            <h4 class="mt-2 text-sm font-bold text-foreground group-hover:text-amber-600 dark:group-hover:text-amber-400">
                                {{ pos.name }}
                            </h4>

                            <!-- Official Info -->
                            <div class="mt-3 flex items-center gap-2.5 rounded-xl border border-border/60 bg-muted/30 p-2 text-xs">
                                <div
                                    class="flex size-7 items-center justify-center rounded-lg font-bold text-white shadow-sm"
                                    :class="pos.active_assignment ? 'bg-amber-600' : 'bg-slate-400 dark:bg-slate-700'"
                                >
                                    <UserCheck v-if="pos.active_assignment" class="size-4" />
                                    <UserX v-else class="size-4" />
                                </div>
                                <div class="truncate">
                                    <p class="truncate font-semibold text-foreground">
                                        {{ pos.active_assignment ? pos.active_assignment.user.name : 'Belum Terisi (Lowong)' }}
                                    </p>
                                    <p class="text-[10px] text-muted-foreground">
                                        {{ pos.active_assignment ? pos.active_assignment.user.email : 'Klik untuk kelola' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Connector Line Down to Root Units -->
                    <div class="mt-4 flex flex-col items-center">
                        <div class="h-6 w-0.5 bg-indigo-500/50" />
                        <div class="size-2 rounded-full bg-indigo-600" />
                    </div>
                </div>

                <!-- ======================================================== -->
                <!-- TIER 1+: RECURSIVE UNITS & SUB-SECTIONS (BAGIAN / SEKSI) -->
                <!-- ======================================================== -->
                <div class="flex flex-wrap justify-center gap-8">
                    <OrganizationTreeNodeBranch
                        v-for="unit in tree.root_units"
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
        </section>

        <!-- ======================================================== -->
        <!-- 3. NODE INSPECTOR MODAL DIALOG                           -->
        <!-- ======================================================== -->
        <Dialog v-model:open="isInspectorOpen">
            <DialogContent class="sm:max-w-xl rounded-3xl p-6">
                <!-- UNIT INSPECTION -->
                <template v-if="inspectedNode?.type === 'unit' && inspectedNode.unit">
                    <DialogHeader>
                        <div class="flex items-center gap-2 text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400 uppercase">
                            <Building2 class="size-4" />
                            <span>Rincian Unit / Bagian Organisasi</span>
                        </div>
                        <DialogTitle class="text-xl font-bold">
                            {{ inspectedNode.unit.name }}
                        </DialogTitle>
                        <DialogDescription class="text-xs">
                            Kode: {{ inspectedNode.unit.code || 'Tidak ada kode khusus' }} · Status:
                            {{ inspectedNode.unit.is_active ? 'Aktif' : 'Nonaktif' }}
                        </DialogDescription>
                    </DialogHeader>

                    <div class="mt-4 space-y-4">
                        <!-- Sub-units summary -->
                        <div class="rounded-2xl border border-border/70 bg-muted/30 p-4">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-foreground">
                                Sub-bagian / Seksi Terkait ({{ inspectedNode.unit.children?.length || 0 }})
                            </h4>
                            <div v-if="inspectedNode.unit.children && inspectedNode.unit.children.length > 0" class="mt-2 flex flex-wrap gap-2">
                                <span
                                    v-for="sub in inspectedNode.unit.children"
                                    :key="sub.id"
                                    class="rounded-xl border border-border/60 bg-background px-3 py-1 text-xs font-medium text-foreground"
                                >
                                    {{ sub.name }}
                                </span>
                            </div>
                            <p v-else class="mt-1 text-xs text-muted-foreground">
                                Unit ini tidak memiliki anak sub-bagian (unit operasional langsung).
                            </p>
                        </div>

                        <!-- Positions inside unit -->
                        <div class="rounded-2xl border border-border/70 bg-muted/30 p-4">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold uppercase tracking-wider text-foreground">
                                    Daftar Jabatan Kerja ({{ inspectedNode.unit.positions.length }})
                                </h4>
                            </div>
                            <div class="mt-3 space-y-2 max-h-56 overflow-y-auto pr-1">
                                <div
                                    v-for="p in inspectedNode.unit.positions"
                                    :key="p.id"
                                    class="flex items-center justify-between rounded-xl border border-border/60 bg-background p-3 text-xs"
                                >
                                    <div class="space-y-0.5">
                                        <p class="font-bold text-foreground">{{ p.name }}</p>
                                        <p class="text-[11px] text-muted-foreground font-mono">{{ p.code }}</p>
                                        <div class="flex items-center gap-1.5 text-[11px] pt-1">
                                            <span class="text-muted-foreground">Pejabat:</span>
                                            <span class="font-semibold" :class="p.active_assignment ? 'text-foreground' : 'text-amber-600'">
                                                {{ p.active_assignment ? p.active_assignment.user.name : 'Lowong (Belum Ditugaskan)' }}
                                            </span>
                                        </div>
                                    </div>
                                    <Badge variant="outline" :class="getLevelBadgeClass(p.level.code)">
                                        {{ p.level.name }}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <Link
                                :href="assignmentsRoute"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-border/80 bg-background px-4 py-2 text-xs font-semibold text-foreground hover:bg-muted"
                            >
                                <User class="size-3.5" />
                                <span>Kelola Pejabat Unit</span>
                            </Link>
                        </div>
                    </div>
                </template>

                <!-- POSITION INSPECTION -->
                <template v-else-if="inspectedNode?.type === 'position' && inspectedNode.position">
                    <DialogHeader>
                        <div class="flex items-center gap-2 text-xs font-mono font-bold text-indigo-600 dark:text-indigo-400 uppercase">
                            <Briefcase class="size-4" />
                            <span>Rincian Jabatan & Penugasan</span>
                        </div>
                        <DialogTitle class="text-xl font-bold">
                            {{ inspectedNode.position.name }}
                        </DialogTitle>
                        <DialogDescription class="text-xs">
                            Kode: {{ inspectedNode.position.code }} · Level: {{ inspectedNode.position.level.name }}
                        </DialogDescription>
                    </DialogHeader>

                    <div class="mt-4 space-y-4">
                        <!-- Active Official Card -->
                        <div class="rounded-2xl border border-border/70 bg-muted/30 p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-foreground">Pejabat yang Bertugas</span>
                                <Badge :variant="inspectedNode.position.active_assignment ? 'default' : 'secondary'">
                                    {{ inspectedNode.position.active_assignment ? 'Terisi' : 'Lowong' }}
                                </Badge>
                            </div>

                            <div v-if="inspectedNode.position.active_assignment" class="flex items-center gap-3 rounded-xl bg-background p-3 border border-border/60">
                                <div class="flex size-10 items-center justify-center rounded-xl bg-indigo-600 text-white font-bold text-sm">
                                    {{ inspectedNode.position.active_assignment.user.name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="space-y-0.5">
                                    <p class="font-bold text-sm text-foreground">
                                        {{ inspectedNode.position.active_assignment.user.name }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ inspectedNode.position.active_assignment.user.email }}
                                    </p>
                                    <p class="text-[10px] text-muted-foreground pt-0.5">
                                        Mulai Menjabat: {{ new Date(inspectedNode.position.active_assignment.started_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' }) }}
                                    </p>
                                </div>
                            </div>

                            <div v-else class="rounded-xl border border-dashed border-amber-500/40 bg-amber-500/5 p-3 text-xs text-amber-700 dark:text-amber-300">
                                Jabatan ini belum memiliki pejabat aktif. Anda dapat menugaskan aparatur sipil melalui menu Kelola Pejabat.
                            </div>
                        </div>

                        <!-- Workflow Level Invariant details -->
                        <div class="rounded-2xl border border-border/70 bg-muted/30 p-4 space-y-2 text-xs">
                            <span class="font-bold uppercase tracking-wider text-foreground">Peran Alur Disposisi</span>
                            <div class="flex items-center justify-between pt-1">
                                <span class="text-muted-foreground">Level Workflow:</span>
                                <Badge variant="outline" :class="getLevelBadgeClass(inspectedNode.position.level.code)">
                                    {{ inspectedNode.position.level.name }}
                                </Badge>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">Urutan Hierarki:</span>
                                <span class="font-mono font-bold text-foreground">Order {{ inspectedNode.position.level.hierarchy_order }}</span>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <Link
                                :href="assignmentsRoute"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-md hover:bg-indigo-700"
                            >
                                <UserCheck class="size-3.5" />
                                <span>{{ inspectedNode.position.active_assignment ? 'Ganti Pejabat' : 'Tugaskan Pejabat' }}</span>
                            </Link>
                        </div>
                    </div>
                </template>
            </DialogContent>
        </Dialog>
    </div>
</template>
