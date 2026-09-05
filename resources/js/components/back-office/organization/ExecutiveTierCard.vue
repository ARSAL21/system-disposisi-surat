<script setup lang="ts">
import {
    ArrowDownToLine,
    Briefcase,
    Building2,
    Crown,
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
    tier: 'walikota' | 'sekda';
    searchQuery: string;
    isEditMode: boolean;
    canMutate: boolean;
    chartOrientation?: 'vertical' | 'horizontal';
    isDragged?: boolean;
    isDropTarget?: boolean;
    isDropValid?: boolean;
    draggedUnit?: OrganizationTreeNode | null;
}>();

const emit = defineEmits<{
    inspectUnit: [unit: OrganizationTreeNode];
    inspectPosition: [position: OrganizationTreePosition];
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

// Primary executive position in this unit (Wali Kota or Sekda)
const primaryPosition = computed<OrganizationTreePosition | null>(() => {
    if (!props.node.positions || props.node.positions.length === 0) {
        return null;
    }

    return props.node.positions[0] ?? null;
});

const isMatched = computed<boolean>(() => {
    if (!props.searchQuery) {
        return false;
    }

    const q = props.searchQuery.toLowerCase();
    const matchUnit =
        props.node.name.toLowerCase().includes(q) ||
        (props.node.code?.toLowerCase().includes(q) ?? false);
    const matchPos = props.node.positions.some(
        (p) =>
            p.name.toLowerCase().includes(q) ||
            p.code.toLowerCase().includes(q) ||
            (p.active_assignment?.user.name.toLowerCase().includes(q) ?? false),
    );

    return matchUnit || matchPos;
});

function onDragStart(event: DragEvent) {
    if (!props.canMutate || props.tier === 'walikota') {
        return;
    }

    emit('dragStart', props.node, event);
}

function onDragOver(event: DragEvent) {
    if (!props.canMutate) {
        return;
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
    if (!props.canMutate) {
        return;
    }

    emit('drop', props.node, event);
}
</script>

<template>
    <div
        :data-unit-id="node.id"
        :draggable="canMutate && tier !== 'walikota'"
        class="group relative w-80 rounded-3xl border bg-card p-5 shadow-xl transition-all duration-300 hover:-translate-y-1 hover:shadow-2xl sm:w-96 dark:bg-slate-900"
        :class="[
            tier === 'walikota'
                ? 'border-amber-500/50 ring-1 ring-amber-500/30'
                : 'border-indigo-500/40 ring-1 ring-indigo-500/20',
            isMatched && searchQuery ? 'ring-4 ring-indigo-500/60' : '',
            isDragged ? 'scale-95 border-dashed opacity-40' : '',
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
        <!-- Drop Target Indicator Banner -->
        <div
            v-if="isDropTarget"
            class="absolute -top-3.5 left-1/2 z-20 flex -translate-x-1/2 items-center gap-1 rounded-full px-3 py-0.5 text-[10px] font-extrabold shadow-md"
            :class="
                isDropValid
                    ? 'animate-bounce bg-emerald-600 text-white'
                    : 'bg-rose-600 text-white'
            "
        >
            <span>{{
                isDropValid
                    ? '📥 Lepas untuk jadikan anak unit ini'
                    : '🚫 Tidak dapat memindahkan ke sini'
            }}</span>
        </div>

        <!-- Top Accent Gradient -->
        <div
            class="absolute inset-x-0 top-0 h-1.5 rounded-t-3xl"
            :class="[
                tier === 'walikota'
                    ? 'bg-gradient-to-r from-amber-500 via-yellow-400 to-amber-600'
                    : 'bg-gradient-to-r from-indigo-500 via-sky-400 to-indigo-600',
            ]"
        />

        <!-- Card Header: Title, Drag Handle & Badges -->
        <div class="flex items-start justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <!-- Drag Handle (Sekda can be moved if needed) -->
                <div
                    v-if="canMutate && tier !== 'walikota'"
                    class="-ml-1 cursor-grab p-0.5 text-muted-foreground hover:text-foreground active:cursor-grabbing"
                    title="Tarik untuk memindahkan unit ini"
                >
                    <GripVertical class="size-4" />
                </div>

                <div
                    class="flex size-9 shrink-0 items-center justify-center rounded-2xl font-bold shadow-sm"
                    :class="[
                        tier === 'walikota'
                            ? 'bg-amber-500/15 text-amber-600 dark:bg-amber-400/20 dark:text-amber-400'
                            : 'bg-indigo-500/15 text-indigo-600 dark:bg-indigo-400/20 dark:text-indigo-400',
                    ]"
                >
                    <Crown v-if="tier === 'walikota'" class="size-5" />
                    <Building2 v-else class="size-5" />
                </div>
                <div>
                    <span
                        class="block text-[10px] font-extrabold tracking-wider uppercase"
                        :class="
                            tier === 'walikota'
                                ? 'text-amber-600 dark:text-amber-400'
                                : 'text-indigo-600 dark:text-indigo-400'
                        "
                    >
                        {{
                            tier === 'walikota'
                                ? 'Pucuk Pimpinan Daerah'
                                : 'Sekretariat Daerah'
                        }}
                    </span>
                    <h3
                        class="text-base font-bold tracking-tight text-foreground sm:text-lg"
                    >
                        {{ node.name }}
                    </h3>
                </div>
            </div>

            <Badge
                :variant="node.is_active ? 'outline' : 'secondary'"
                class="shrink-0 px-2 py-0.5 text-[10px]"
                :class="
                    tier === 'walikota'
                        ? 'border-amber-500/30 text-amber-700 dark:text-amber-300'
                        : 'border-indigo-500/30 text-indigo-700 dark:text-indigo-300'
                "
            >
                {{ node.is_active ? 'Aktif' : 'Nonaktif' }}
            </Badge>
        </div>

        <!-- Official Officeholder Box (e.g. Wali Kota or Sekda) -->
        <div
            v-if="primaryPosition"
            class="mt-4 cursor-pointer rounded-2xl border border-border/70 bg-muted/40 p-3 transition-colors hover:bg-muted/70"
            @click="emit('inspectPosition', primaryPosition)"
        >
            <div class="flex items-center justify-between gap-2">
                <span
                    class="text-[11px] font-bold tracking-wider text-muted-foreground uppercase"
                >
                    {{ primaryPosition.name }}
                </span>
                <Badge
                    variant="outline"
                    class="bg-background px-1.5 py-0 text-[9px] font-semibold"
                >
                    {{ primaryPosition.level.name }}
                </Badge>
            </div>

            <div class="mt-2.5 flex items-center gap-3">
                <div
                    class="flex size-9 items-center justify-center rounded-xl font-bold text-white shadow-sm"
                    :class="
                        primaryPosition.active_assignment
                            ? tier === 'walikota'
                                ? 'bg-amber-600'
                                : 'bg-indigo-600'
                            : 'bg-slate-400'
                    "
                >
                    <UserCheck
                        v-if="primaryPosition.active_assignment"
                        class="size-4"
                    />
                    <UserX v-else class="size-4" />
                </div>
                <div class="truncate">
                    <p class="truncate text-xs font-bold text-foreground">
                        {{
                            primaryPosition.active_assignment
                                ? primaryPosition.active_assignment.user.name
                                : 'Lowong (Belum Ditugaskan)'
                        }}
                    </p>
                    <p class="truncate text-[11px] text-muted-foreground">
                        {{
                            primaryPosition.active_assignment
                                ? primaryPosition.active_assignment.user.email
                                : 'Klik untuk kelola pejabat'
                        }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Additional Positions if any -->
        <div v-if="node.positions.length > 1" class="mt-2 space-y-1.5">
            <div
                v-for="pos in node.positions.slice(1)"
                :key="pos.id"
                class="flex cursor-pointer items-center justify-between rounded-xl border border-border/50 bg-background p-2 text-xs hover:bg-muted/50"
                @click="emit('inspectPosition', pos)"
            >
                <div class="truncate">
                    <span
                        class="block truncate font-semibold text-foreground"
                        >{{ pos.name }}</span
                    >
                    <span class="text-[10px] text-muted-foreground">{{
                        pos.active_assignment?.user.name ?? 'Lowong'
                    }}</span>
                </div>
                <Badge variant="outline" class="px-1.5 py-0 text-[8px]">
                    {{ pos.level.name }}
                </Badge>
            </div>
        </div>

        <!-- Drop Target Ghost Slot Preview (Gambaran Tempat) -->
        <div
            v-if="isDropTarget && isDropValid"
            class="mt-3.5 flex animate-pulse flex-col items-center justify-center rounded-2xl border-2 border-dashed border-indigo-500 bg-indigo-500/15 p-3.5 text-center shadow-md transition-all duration-300"
        >
            <div
                class="mb-1.5 flex size-8 animate-bounce items-center justify-center rounded-xl bg-indigo-500/20 text-indigo-600 dark:text-indigo-400"
            >
                <ArrowDownToLine class="size-4" />
            </div>
            <span
                class="font-mono text-[9px] font-bold tracking-wider text-indigo-700 uppercase dark:text-indigo-300"
            >
                Gambaran Tempat Baru
            </span>
            <p
                class="mt-0.5 max-w-full truncate text-xs font-bold text-foreground"
            >
                {{ draggedUnit?.name || 'Unit Organisasi' }}
            </p>
            <span class="mt-0.5 text-[10px] text-muted-foreground">
                Lepas mouse untuk menempatkan langsung di bawah {{ node.name }}
            </span>
        </div>

        <!-- Super Admin Interactive Actions Toolbar (in Edit Mode) -->
        <div
            v-if="isEditMode && canMutate"
            class="mt-4 flex flex-wrap items-center gap-1.5 border-t border-border/60 pt-3 text-[11px]"
        >
            <Button
                size="sm"
                variant="outline"
                class="h-7 gap-1 rounded-lg border-indigo-500/30 px-2 text-[10px] text-indigo-700 hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-indigo-950/50"
                title="Tambah unit di bawah ini"
                @click.stop="emit('addSubUnit', node)"
            >
                <Plus class="size-3" />
                <span>+ Sub-Unit</span>
            </Button>

            <Button
                size="sm"
                variant="outline"
                class="h-7 gap-1 rounded-lg border-emerald-500/30 px-2 text-[10px] text-emerald-700 hover:bg-emerald-50 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                title="Tambah jabatan dalam unit ini"
                @click.stop="emit('addPosition', node)"
            >
                <Briefcase class="size-3" />
                <span>+ Jabatan</span>
            </Button>

            <Button
                v-if="tier !== 'walikota'"
                size="sm"
                variant="outline"
                class="h-7 gap-1 rounded-lg border-violet-500/30 px-2 text-[10px] text-violet-700 hover:bg-violet-50 dark:text-violet-300 dark:hover:bg-violet-950/50"
                title="Pindahkan unit ini ke induk lain"
                @click.stop="emit('reparentUnit', node)"
            >
                <GitFork class="size-3" />
                <span>Pindah</span>
            </Button>

            <Button
                size="sm"
                variant="ghost"
                class="h-7 gap-1 rounded-lg px-2 text-[10px] text-muted-foreground hover:text-foreground"
                title="Edit nama atau data unit"
                @click.stop="emit('editUnit', node)"
            >
                <Pencil class="size-3" />
                <span>Edit</span>
            </Button>

            <button
                type="button"
                class="ml-auto inline-flex items-center gap-1 text-[10px] font-semibold text-indigo-600 hover:underline"
                @click.stop="emit('inspectUnit', node)"
            >
                <span>Rincian</span>
                <ExternalLink class="size-2.5" />
            </button>
        </div>

        <!-- Normal Footer (View Mode) -->
        <div
            v-else
            class="mt-3 flex items-center justify-between border-t border-border/60 pt-2 text-[11px]"
        >
            <span class="font-mono text-[10px] text-muted-foreground">
                {{ node.code || 'UNIT_EKSEKUTIF' }}
            </span>
            <button
                type="button"
                class="inline-flex items-center gap-1 font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"
                @click="emit('inspectUnit', node)"
            >
                <span>Rincian Lengkap</span>
                <ExternalLink class="size-3" />
            </button>
        </div>
    </div>
</template>
