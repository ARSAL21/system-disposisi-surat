<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Check, GitFork, ShieldAlert } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import type { OrganizationalUnitOption, OrganizationTreeNode } from '@/types';

const props = defineProps<{
    open: boolean;
    unit: OrganizationTreeNode | null;
    allUnits: OrganizationalUnitOption[];
    treeRootUnits: OrganizationTreeNode[];
}>();

const emit = defineEmits<{
    'update:open': [open: boolean];
    success: [
        source: OrganizationTreeNode,
        previousParentId: number | null,
        newParentId: number | null,
    ];
}>();

const selectedParentId = ref<number | null>(null);
const processing = ref<boolean>(false);
const errorMessage = ref<string | null>(null);

// Find all descendant IDs of the current unit to avoid cycles
const invalidTargetIds = computed<Set<number>>(() => {
    const set = new Set<number>();

    if (!props.unit) {
        return set;
    }

    set.add(props.unit.id);

    function collectDescendants(nodes: OrganizationTreeNode[]) {
        for (const n of nodes) {
            if (n.id === props.unit?.id) {
                addChildren(n.children);

                return;
            }

            if (n.children && n.children.length > 0) {
                collectDescendants(n.children);
            }
        }
    }

    function addChildren(children: OrganizationTreeNode[]) {
        for (const c of children) {
            set.add(c.id);

            if (c.children && c.children.length > 0) {
                addChildren(c.children);
            }
        }
    }

    collectDescendants(props.treeRootUnits);

    return set;
});

// Valid parent unit options
const validParentOptions = computed(() => {
    return props.allUnits.filter((opt) => !invalidTargetIds.value.has(opt.id));
});

// Quick parent target presets (e.g. Asistens, Sekda, Root)
const quickParentOptions = computed(() => {
    const list: Array<{ id: number | null; name: string; badge?: string }> = [];

    // Filter valid units that are top leadership or assistants
    for (const opt of validParentOptions.value) {
        const lower = opt.name.toLowerCase();

        if (lower.includes('asisten 1') || lower.includes('asisten i')) {
            list.push({ id: opt.id, name: '🌿 Asisten I', badge: 'Pemkesra' });
        } else if (
            lower.includes('asisten 2') ||
            lower.includes('asisten ii')
        ) {
            list.push({ id: opt.id, name: '🌊 Asisten II', badge: 'Ekbang' });
        } else if (
            lower.includes('asisten 3') ||
            lower.includes('asisten iii')
        ) {
            list.push({
                id: opt.id,
                name: '🔮 Asisten III',
                badge: 'Adm Umum',
            });
        } else if (
            lower.includes('sekretariat daerah') ||
            lower.includes('sekda')
        ) {
            list.push({ id: opt.id, name: '🏛️ Sekda', badge: 'Tingkat Kota' });
        }
    }

    // Add Root option
    list.push({
        id: null,
        name: '👑 Tingkat Utama (Root / Tanpa Induk)',
        badge: 'Wali Kota',
    });

    return list;
});

const currentParentName = computed(() => {
    if (!props.unit || props.unit.parent_id === null) {
        return 'Paling Atas (Tingkat Utama / Tanpa Induk)';
    }

    const found = props.allUnits.find((u) => u.id === props.unit?.parent_id);

    return found ? found.name : `Unit #${props.unit.parent_id}`;
});

const targetParentName = computed(() => {
    if (selectedParentId.value === null) {
        return 'Tingkat Utama (Tanpa Induk)';
    }

    const found = props.allUnits.find((u) => u.id === selectedParentId.value);

    return found ? found.name : `Unit #${selectedParentId.value}`;
});

watch(
    () => props.unit,
    (unit) => {
        if (unit) {
            selectedParentId.value = unit.parent_id;
            errorMessage.value = null;
        }
    },
    { immediate: true },
);

function submit() {
    if (!props.unit) {
        return;
    }

    processing.value = true;
    errorMessage.value = null;

    const source = props.unit;
    const prevParentId = source.parent_id;
    const nextParentId = selectedParentId.value;

    router.patch(
        source.links?.update ?? `/back-office/organization/units/${source.id}`,
        {
            name: source.name,
            parent_id: nextParentId,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                processing.value = false;
                emit('update:open', false);
                emit('success', source, prevParentId, nextParentId);
            },
            onError: (errors) => {
                processing.value = false;
                errorMessage.value =
                    errors.parent_id ||
                    errors.name ||
                    'Gagal memindahkan unit organisasi. Silakan periksa batasan hierarki.';
            },
        },
    );
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="rounded-3xl p-6 sm:max-w-lg">
            <DialogHeader>
                <div
                    class="flex items-center gap-2 font-mono text-xs font-bold text-indigo-600 uppercase dark:text-indigo-400"
                >
                    <GitFork class="size-4" />
                    <span>Pengaturan Hirarki & Bagan</span>
                </div>
                <DialogTitle class="text-xl font-bold">
                    Pindahkan Unit / Bagian
                </DialogTitle>
                <DialogDescription class="text-xs">
                    Atur ulang posisi unit ini dalam struktur bagan atau
                    kembalikan ke asisten induk yang sesuai.
                </DialogDescription>
            </DialogHeader>

            <div v-if="unit" class="mt-4 space-y-4">
                <!-- Visual Current -> Target Transfer Box -->
                <div
                    class="rounded-2xl border border-indigo-500/20 bg-indigo-500/5 p-4"
                >
                    <div
                        class="mb-1 flex items-center justify-between text-xs font-semibold text-muted-foreground"
                    >
                        <span>Unit yang Diatur:</span>
                        <span
                            class="font-mono text-[11px] text-indigo-600 dark:text-indigo-400"
                            >{{ unit.code || 'NO-CODE' }}</span
                        >
                    </div>
                    <p class="text-sm font-bold text-foreground">
                        {{ unit.name }}
                    </p>

                    <div
                        class="mt-3 grid grid-cols-1 gap-2 border-t border-indigo-500/15 pt-3 text-xs sm:grid-cols-2"
                    >
                        <div
                            class="rounded-xl border border-border/60 bg-background/80 p-2.5"
                        >
                            <span
                                class="block text-[10px] font-medium text-muted-foreground"
                                >Induk Saat Ini:</span
                            >
                            <span
                                class="mt-0.5 block truncate font-semibold text-foreground"
                                :title="currentParentName"
                            >
                                {{ currentParentName }}
                            </span>
                        </div>
                        <div
                            class="rounded-xl border border-indigo-500/30 bg-background/80 p-2.5"
                        >
                            <span
                                class="block text-[10px] font-medium text-indigo-600 dark:text-indigo-400"
                                >Induk Baru:</span
                            >
                            <span
                                class="mt-0.5 block truncate font-bold text-indigo-700 dark:text-indigo-300"
                                :title="targetParentName"
                            >
                                {{ targetParentName }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Error Alert if any -->
                <div
                    v-if="errorMessage"
                    class="flex items-start gap-2.5 rounded-2xl border border-rose-500/30 bg-rose-500/10 p-3 text-xs text-rose-700 dark:text-rose-300"
                >
                    <ShieldAlert class="mt-0.5 size-4 shrink-0" />
                    <span>{{ errorMessage }}</span>
                </div>

                <!-- 1-Click Quick Preset Target Buttons (Misahkan / Kembalikan Langsung) -->
                <div class="space-y-1.5">
                    <span
                        class="block text-[11px] font-bold tracking-wider text-muted-foreground uppercase"
                    >
                        Pilihan Cepat Target Induk (1-Klik):
                    </span>
                    <div class="flex flex-wrap gap-1.5">
                        <button
                            v-for="opt in quickParentOptions"
                            :key="opt.id ?? 'root'"
                            type="button"
                            class="flex items-center gap-1.5 rounded-xl border px-2.5 py-1.5 text-xs font-semibold transition-all"
                            :class="[
                                selectedParentId === opt.id
                                    ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm ring-2 ring-indigo-500/30'
                                    : 'border-border/80 bg-muted/40 text-foreground hover:bg-muted/80',
                            ]"
                            @click="selectedParentId = opt.id"
                        >
                            <span>{{ opt.name }}</span>
                            <span
                                v-if="opt.badge"
                                class="rounded px-1 py-0 text-[9px]"
                                :class="
                                    selectedParentId === opt.id
                                        ? 'bg-white/20 text-white'
                                        : 'border bg-background text-muted-foreground'
                                "
                            >
                                {{ opt.badge }}
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Select New Parent Dropdown -->
                <div class="space-y-1.5 pt-1">
                    <Label for="parent-select" class="text-xs font-semibold">
                        Atau Pilih Dari Semua Unit Induk:
                    </Label>
                    <div class="relative">
                        <select
                            id="parent-select"
                            v-model="selectedParentId"
                            class="w-full rounded-xl border border-border/80 bg-background px-3 py-2.5 text-xs text-foreground shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 focus:outline-none"
                        >
                            <option :value="null">
                                -- Tingkat Utama (Root / Tanpa Induk) --
                            </option>
                            <option
                                v-for="option in validParentOptions"
                                :key="option.id"
                                :value="option.id"
                            >
                                {{ option.name }} ({{
                                    option.code || 'NO-CODE'
                                }})
                            </option>
                        </select>
                    </div>
                    <p class="text-[11px] text-muted-foreground">
                        Unit anak / keturunan yang berpotensi menyebabkan siklus
                        tidak valid otomatis disembunyikan.
                    </p>
                </div>
            </div>

            <DialogFooter class="mt-6 flex items-center justify-end gap-2">
                <Button
                    type="button"
                    variant="outline"
                    class="rounded-xl text-xs"
                    :disabled="processing"
                    @click="emit('update:open', false)"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    class="gap-1.5 rounded-xl bg-indigo-600 text-xs font-semibold text-white shadow-md hover:bg-indigo-700"
                    :disabled="
                        processing ||
                        (unit ? selectedParentId === unit.parent_id : true)
                    "
                    @click="submit"
                >
                    <Spinner v-if="processing" class="size-3.5" />
                    <Check v-else class="size-3.5" />
                    <span>Simpan Perubahan Hirarki</span>
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
