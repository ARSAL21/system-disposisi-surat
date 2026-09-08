<script setup lang="ts">
import {
    ArrowDownToLine,
    Briefcase,
    Building2,
    ChevronDown,
    CornerDownRight,
    GitFork,
    GripVertical,
    Pencil,
    Plus,
} from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { OrganizationTreeNode, OrganizationTreePosition } from '@/types';

const props = defineProps<{
    node: OrganizationTreeNode;
    searchQuery: string;
    viewMode: 'all' | 'units_only' | 'occupied_only' | 'vacant_only';
    isEditMode: boolean;
    canMutate: boolean;
    isDragged?: boolean;
    isDropTarget?: boolean;
    isDropValid?: boolean;
    isCollapsed?: boolean;
    draggedUnitId?: number;
    draggedUnit?: OrganizationTreeNode | null;
}>();

const emit = defineEmits<{
    inspectUnit: [unit: OrganizationTreeNode];
    inspectPosition: [position: OrganizationTreePosition];
    toggleCollapse: [unitId: number];
    addSubUnit: [parentUnit: OrganizationTreeNode];
    addPosition: [unit: OrganizationTreeNode];
    reparentUnit: [unit: OrganizationTreeNode];
    editUnit: [unit: OrganizationTreeNode];
    dragStart: [unit: OrganizationTreeNode, event: DragEvent];
    dragEnd: [unit: OrganizationTreeNode, event: DragEvent];
    dragOver: [unit: OrganizationTreeNode, event: DragEvent];
    dragLeave: [unit: OrganizationTreeNode, event: DragEvent];
    drop: [unit: OrganizationTreeNode, event: DragEvent];
}>();

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

const filteredPositions = computed<OrganizationTreePosition[]>(() => {
    if (props.viewMode === 'units_only') {
        return [];
    }

    if (props.viewMode === 'occupied_only') {
        return props.node.positions.filter((p) => p.active_assignment !== null);
    }

    if (props.viewMode === 'vacant_only') {
        return props.node.positions.filter((p) => p.active_assignment === null);
    }

    return props.node.positions;
});

const primaryPosition = computed<OrganizationTreePosition | null>(() => {
    if (!props.node.positions || props.node.positions.length === 0) {
        return null;
    }

    return props.node.positions[0] ?? null;
});

function isSubDragged(id: number): boolean {
    return props.draggedUnitId === id;
}

function onDragStart(event: DragEvent) {
    if (!props.canMutate) {
        return;
    }

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(props.node.id));
    }

    emit('dragStart', props.node, event);
}

function onDragOver(event: DragEvent) {
    if (!props.canMutate) {
        return;
    }

    if (props.isDropValid) {
        event.preventDefault();

        if (event.dataTransfer) {
            event.dataTransfer.dropEffect = 'move';
        }
    }

    emit('dragOver', props.node, event);
}

function onDragLeave(event: DragEvent) {
    if (!props.canMutate) {
        return;
    }

    emit('dragLeave', props.node, event);
}

function onDrop(event: DragEvent) {
    if (props.isDropValid) {
        event.preventDefault();
    }

    if (!props.canMutate) {
        return;
    }

    emit('drop', props.node, event);
}

function onSubDragStart(sub: OrganizationTreeNode, event: DragEvent) {
    if (!props.canMutate) {
        return;
    }

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(sub.id));
    }

    emit('dragStart', sub, event);
}
</script>

<template>
    <div
        :data-unit-id="node.id"
        :draggable="canMutate"
        class="group relative w-56 rounded-2xl border border-border/80 bg-card p-3.5 shadow-sm transition-all duration-300 ease-out hover:-translate-y-1.5 hover:border-indigo-500/60 hover:shadow-xl hover:shadow-indigo-500/5 sm:w-64 dark:bg-slate-900"
        :class="[
            isMatched && searchQuery
                ? 'border-indigo-500 ring-4 ring-indigo-500/40'
                : '',
            isDragged
                ? 'scale-95 border-dashed border-indigo-400 opacity-40'
                : '',
            isDropTarget && isDropValid
                ? 'scale-102 border-emerald-500 bg-emerald-500/10 ring-4 ring-emerald-500'
                : '',
            isDropTarget && !isDropValid
                ? 'cursor-not-allowed border-rose-500 opacity-80 ring-2 ring-rose-500'
                : '',
        ]"
        @dragstart="onDragStart"
        @dragend="emit('dragEnd', node, $event)"
        @dragover="onDragOver"
        @dragleave="onDragLeave"
        @drop="onDrop"
    >
        <!-- Drop Target Banner -->
        <div
            v-if="isDropTarget"
            class="absolute -top-3 left-1/2 z-20 flex -translate-x-1/2 items-center gap-1 rounded-full px-2.5 py-0.5 text-[9px] font-extrabold shadow-md"
            :class="
                isDropValid
                    ? 'animate-bounce bg-emerald-600 text-white'
                    : 'bg-rose-600 text-white'
            "
        >
            <span>{{
                isDropValid
                    ? '📥 Jadikan anak bagian ini'
                    : '🚫 Dilarang / Siklus'
            }}</span>
        </div>

        <!-- Card Header: Drag Grip, Title & Code -->
        <div class="flex items-start justify-between gap-1.5">
            <div class="flex items-start gap-1.5">
                <!-- Drag Grip -->
                <div
                    v-if="canMutate"
                    class="mt-0.5 -ml-1 cursor-grab p-0.5 text-muted-foreground transition-colors hover:text-indigo-600 active:cursor-grabbing dark:hover:text-indigo-400"
                    title="Tarik untuk memindahkan unit ini"
                >
                    <GripVertical class="size-3.5" />
                </div>

                <div>
                    <div class="flex items-center gap-1">
                        <Building2 class="size-3 shrink-0 text-indigo-500" />
                        <span
                            class="font-mono text-[9px] font-semibold text-muted-foreground"
                        >
                            {{ node.code || 'BAGIAN' }}
                        </span>
                    </div>
                    <h5
                        class="mt-0.5 line-clamp-2 cursor-pointer text-xs leading-tight font-bold text-foreground transition-colors hover:text-indigo-600"
                        @click="emit('inspectUnit', node)"
                    >
                        {{ node.name }}
                    </h5>
                </div>
            </div>

            <Badge
                :variant="node.is_active ? 'outline' : 'secondary'"
                class="shrink-0 px-1 py-0 text-[8px]"
            >
                {{ node.is_active ? 'Aktif' : 'Nonaktif' }}
            </Badge>
        </div>

        <!-- Primary Leader / Position (e.g. Kepala Bagian) -->
        <div
            v-if="primaryPosition"
            class="mt-2.5 cursor-pointer rounded-xl border border-border/60 bg-muted/30 p-2 text-xs transition-colors hover:bg-muted/70"
            @click="emit('inspectPosition', primaryPosition)"
        >
            <div
                class="mb-1 flex items-center justify-between text-[9px] text-muted-foreground"
            >
                <span class="truncate font-bold tracking-wider uppercase">{{
                    primaryPosition.name
                }}</span>
                <Badge variant="outline" class="shrink-0 px-1 py-0 text-[8px]">
                    {{ primaryPosition.level.name.split(' ')[0] }}
                </Badge>
            </div>
            <div class="flex items-center gap-1.5 truncate">
                <div
                    class="size-2 shrink-0 rounded-full"
                    :class="
                        primaryPosition.active_assignment
                            ? 'bg-emerald-500'
                            : 'bg-amber-500'
                    "
                />
                <span
                    class="truncate text-[10px] font-semibold text-foreground"
                >
                    {{
                        primaryPosition.active_assignment
                            ? primaryPosition.active_assignment.user.name
                            : 'Lowong'
                    }}
                </span>
            </div>
        </div>

        <!-- Other Positions (if > 1) -->
        <div
            v-if="filteredPositions.length > 1"
            class="mt-2 space-y-1 border-t border-border/50 pt-1.5"
        >
            <div
                v-for="pos in filteredPositions.slice(1)"
                :key="pos.id"
                class="flex cursor-pointer items-center justify-between gap-1.5 rounded-lg border border-border/40 bg-background p-1.5 text-[10px] transition-colors hover:bg-muted/50"
                @click="emit('inspectPosition', pos)"
            >
                <div class="flex items-center gap-1 truncate">
                    <Briefcase
                        class="size-2.5 shrink-0 text-muted-foreground"
                    />
                    <span class="truncate text-foreground">{{ pos.name }}</span>
                </div>
                <span
                    class="shrink-0 text-[9px]"
                    :class="
                        pos.active_assignment
                            ? 'text-muted-foreground'
                            : 'font-semibold text-amber-600'
                    "
                >
                    {{
                        pos.active_assignment
                            ? pos.active_assignment.user.name.split(' ')[0]
                            : 'Lowong'
                    }}
                </span>
            </div>
        </div>

        <!-- Subordinate Section: Nested Child Units (e.g. Bagian dipindahkan menjadi sub) -->
        <div
            v-if="node.children && node.children.length > 0"
            class="mt-2.5 border-t border-border/50 pt-2"
        >
            <button
                type="button"
                class="flex w-full items-center justify-between rounded-lg border border-indigo-500/20 bg-indigo-50/60 px-2 py-1.5 text-[10px] font-bold text-indigo-900 transition-colors hover:bg-indigo-100/70 dark:bg-indigo-950/40 dark:text-indigo-200 dark:hover:bg-indigo-900/50"
                @click="emit('toggleCollapse', node.id)"
            >
                <div class="flex items-center gap-1.5">
                    <CornerDownRight
                        class="size-3 text-indigo-600 dark:text-indigo-400"
                    />
                    <span>{{ node.children.length }} Sub-Bagian Terhubung</span>
                </div>
                <ChevronDown
                    class="size-3 transition-transform duration-200"
                    :class="{ '-rotate-90': isCollapsed }"
                />
            </button>

            <!-- Nested Subordinate Units (Fully Draggable & Interactive) -->
            <div v-if="!isCollapsed" class="mt-1.5 space-y-1.5 pl-1">
                <div
                    v-for="sub in node.children"
                    :key="sub.id"
                    :data-unit-id="sub.id"
                    :draggable="canMutate"
                    class="group/sub relative flex items-center justify-between rounded-xl border border-border/70 bg-background p-2 text-[9px] shadow-xs transition-all duration-200 hover:scale-[1.01] hover:border-indigo-500/60 hover:bg-indigo-50/50 hover:shadow-md dark:hover:bg-indigo-950/40"
                    :class="[
                        isSubDragged(sub.id)
                            ? 'scale-95 border-dashed border-indigo-400 opacity-40'
                            : '',
                    ]"
                    @dragstart.stop="onSubDragStart(sub, $event)"
                    @click="emit('inspectUnit', sub)"
                >
                    <div class="flex min-w-0 items-center gap-1.5 pr-1">
                        <!-- Drag Grip for Sub-unit -->
                        <div
                            v-if="canMutate"
                            class="shrink-0 cursor-grab rounded p-0.5 text-muted-foreground transition-colors group-hover/sub:text-indigo-600 active:cursor-grabbing dark:group-hover/sub:text-indigo-400"
                            title="Tarik untuk memindahkan kembali sub-bagian ini ke asisten atau unit lain"
                            @click.stop
                        >
                            <GripVertical class="size-3.5" />
                        </div>
                        <div class="truncate">
                            <span
                                class="block truncate font-bold text-foreground transition-colors group-hover/sub:text-indigo-600"
                            >
                                {{ sub.name }}
                            </span>
                            <span
                                class="font-mono text-[8px] text-muted-foreground"
                            >
                                {{ sub.code || 'SUB-UNIT' }} ·
                                {{ sub.positions.length }} Jabatan
                            </span>
                        </div>
                    </div>

                    <!-- Actions on Hover -->
                    <div class="flex shrink-0 items-center gap-1">
                        <button
                            v-if="canMutate"
                            type="button"
                            class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-2 py-0.5 text-[8px] font-bold text-white shadow-xs transition-all hover:bg-indigo-700"
                            title="Pindahkan atau kembalikan sub-bagian ini ke induk semula"
                            @click.stop="emit('reparentUnit', sub)"
                        >
                            <GitFork class="size-2.5" />
                            <span>Pindah</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ghost Placement Slot Preview (Gambaran Tempat Sub-Bagian) -->
        <div
            v-if="isDropTarget && isDropValid"
            class="mt-2.5 flex animate-pulse flex-col items-center justify-center rounded-xl border-2 border-dashed border-emerald-500 bg-emerald-500/15 p-2.5 text-center shadow-sm transition-all duration-300"
        >
            <div
                class="mb-1 flex size-6 animate-bounce items-center justify-center rounded-lg bg-emerald-500/20 text-emerald-600 dark:text-emerald-400"
            >
                <ArrowDownToLine class="size-3.5" />
            </div>
            <span
                class="font-mono text-[8px] font-bold tracking-wider text-emerald-700 uppercase dark:text-emerald-300"
            >
                Gambaran Tempat Sub-Bagian
            </span>
            <p
                class="mt-0.5 max-w-full truncate text-[10px] font-bold text-foreground"
            >
                {{ draggedUnit?.name || 'Unit Organisasi' }}
            </p>
            <span class="mt-0.5 text-[8px] leading-tight text-muted-foreground">
                Lepas mouse di sini untuk menempatkan sebagai sub-bagian
            </span>
        </div>

        <!-- Super Admin Actions Toolbar (Edit Mode) -->
        <div
            v-if="isEditMode && canMutate"
            class="mt-2.5 flex flex-wrap items-center gap-1 border-t border-border/50 pt-2 text-[9px]"
        >
            <Button
                size="sm"
                variant="outline"
                class="h-5 gap-0.5 rounded border-indigo-500/30 px-1 text-[8px] text-indigo-700 hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-indigo-950/40"
                title="Tambah sub-bagian"
                @click.stop="emit('addSubUnit', node)"
            >
                <Plus class="size-2.5" />
                <span>Sub-Unit</span>
            </Button>

            <Button
                size="sm"
                variant="outline"
                class="h-5 gap-0.5 rounded border-emerald-500/30 px-1 text-[8px] text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-950/40"
                title="Tambah jabatan"
                @click.stop="emit('addPosition', node)"
            >
                <Plus class="size-2.5" />
                <span>Jabatan</span>
            </Button>

            <Button
                size="sm"
                variant="outline"
                class="h-5 gap-0.5 rounded border-violet-500/30 px-1.5 text-[8px] text-violet-700 hover:bg-violet-50 dark:text-violet-300 dark:hover:bg-violet-950/40"
                title="Pindahkan bagian ini ke induk lain"
                @click.stop="emit('reparentUnit', node)"
            >
                <GitFork class="size-2.5" />
                <span>Pindah</span>
            </Button>

            <Button
                size="sm"
                variant="ghost"
                class="h-5 gap-0.5 rounded px-1 text-[8px] text-muted-foreground hover:text-foreground"
                title="Edit unit"
                @click.stop="emit('editUnit', node)"
            >
                <Pencil class="size-2.5" />
                <span>Edit</span>
            </Button>
        </div>
    </div>
</template>
