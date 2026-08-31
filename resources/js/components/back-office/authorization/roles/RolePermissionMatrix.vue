<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    CheckCheck,
    FolderLock,
    Info,
    KeyRound,
    LockKeyhole,
    RotateCcw,
    Save,
    ShieldAlert,
    Undo2,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Spinner } from '@/components/ui/spinner';
import type { AuthorizationPermission, AuthorizationRole } from '@/types';

const props = defineProps<{
    role: AuthorizationRole;
    permissions: AuthorizationPermission[];
    canMutate: boolean;
}>();

const emit = defineEmits<{
    saved: [];
}>();

// Selected permissions state (local to this role)
const selectedPermissions = ref<string[]>([...props.role.permissions]);
const isSaving = ref<boolean>(false);
const activeCategoryFilter = ref<string>('all');

// Keep in sync when prop changes from server
watch(
    () => props.role.permissions,
    (newPerms) => {
        selectedPermissions.value = [...newPerms];
    },
    { deep: true },
);

// Check if role can be modified
const isEditable = computed(() => {
    return (
        props.canMutate &&
        !props.role.is_protected &&
        !props.role.is_assigned_to_actor &&
        props.role.capabilities.synchronize_permissions
    );
});

// Dirty state tracking (has the user changed checkboxes?)
const isDirty = computed(() => {
    const current = [...selectedPermissions.value].sort();
    const original = [...props.role.permissions].sort();

    if (current.length !== original.length) {
return true;
}

    return current.some((val, index) => val !== original[index]);
});

// Group permissions by category
const groupedPermissions = computed(() => {
    const groups: Record<string, AuthorizationPermission[]> = {};

    for (const perm of props.permissions) {
        const groupName = perm.group || 'Lainnya';

        if (!groups[groupName]) {
            groups[groupName] = [];
        }

        groups[groupName].push(perm);
    }

    return groups;
});

const categoryList = computed(() => {
    return Object.keys(groupedPermissions.value);
});

const filteredCategories = computed(() => {
    if (activeCategoryFilter.value === 'all') {
        return groupedPermissions.value;
    }

    return {
        [activeCategoryFilter.value]:
            groupedPermissions.value[activeCategoryFilter.value] || [],
    };
});

// Checkbox helpers
function isChecked(permissionName: string): boolean {
    return selectedPermissions.value.includes(permissionName);
}

function togglePermission(permissionName: string, checked: boolean) {
    if (!isEditable.value) {
return;
}

    if (checked) {
        if (!selectedPermissions.value.includes(permissionName)) {
            selectedPermissions.value = [
                ...selectedPermissions.value,
                permissionName,
            ];
        }
    } else {
        selectedPermissions.value = selectedPermissions.value.filter(
            (name) => name !== permissionName,
        );
    }
}

function selectAllInGroup(perms: AuthorizationPermission[]) {
    if (!isEditable.value) {
return;
}

    const names = perms.map((p) => p.name);
    selectedPermissions.value = Array.from(
        new Set([...selectedPermissions.value, ...names]),
    );
}

function deselectAllInGroup(perms: AuthorizationPermission[]) {
    if (!isEditable.value) {
return;
}

    const names = new Set(perms.map((p) => p.name));
    selectedPermissions.value = selectedPermissions.value.filter(
        (name) => !names.has(name),
    );
}

function resetChanges() {
    selectedPermissions.value = [...props.role.permissions];
}

// Save directly via PUT request
function savePermissions() {
    if (!isEditable.value || isSaving.value) {
return;
}

    isSaving.value = true;
    router.put(
        props.role.links.permissions,
        {
            permissions: selectedPermissions.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                isSaving.value = false;
                toast.success(
                    `Permission untuk role ${props.role.name} berhasil disimpan.`,
                );
                emit('saved');
            },
            onError: (errors) => {
                isSaving.value = false;
                const msg =
                    errors.permissions ||
                    errors.error ||
                    'Gagal menyimpan konfigurasi permission.';
                toast.error(msg);
            },
        },
    );
}
</script>

<template>
    <div class="space-y-4">
        <!-- Status banners for non-editable cases -->
        <div
            v-if="role.is_protected"
            class="flex items-center gap-3 rounded-xl border border-violet-500/20 bg-violet-500/10 px-4 py-2.5 text-xs text-violet-700 dark:text-violet-300"
        >
            <LockKeyhole class="size-4 shrink-0" />
            <div class="flex-1">
                <span class="font-bold">Role Terlindungi (Sistem):</span>
                Hak akses role ini terstandarisasi read-only dan otomatis disinkronisasi melalui katalog resmi.
            </div>
        </div>

        <div
            v-else-if="role.is_assigned_to_actor"
            class="flex items-center gap-3 rounded-xl border border-amber-500/20 bg-amber-500/10 px-4 py-2.5 text-xs text-amber-700 dark:text-amber-300"
        >
            <ShieldAlert class="size-4 shrink-0" />
            <div class="flex-1">
                <span class="font-bold">Akun Anda Sendiri:</span>
                Modifikasi hak akses role yang sedang Anda pegang dinonaktifkan untuk mencegah privilege escalation & accidental lockout.
            </div>
        </div>

        <div
            v-else-if="!canMutate"
            class="flex items-center gap-3 rounded-xl border border-blue-500/20 bg-blue-500/10 px-4 py-2.5 text-xs text-blue-700 dark:text-blue-300"
        >
            <FolderLock class="size-4 shrink-0" />
            <div class="flex-1">
                <span class="font-bold">Mode Perubahan Terkunci:</span>
                Aktifkan konfirmasi password di banner keamanan atas untuk mengedit permission role ini.
            </div>
        </div>

        <!-- Category Filter Pills -->
        <div class="flex flex-wrap items-center gap-1.5 pt-1">
            <span class="text-[11px] font-semibold text-muted-foreground mr-1">Kategori:</span>
            <button
                type="button"
                class="rounded-lg px-2.5 py-1 text-xs font-medium transition-colors"
                :class="[
                    activeCategoryFilter === 'all'
                        ? 'bg-primary text-primary-foreground shadow-xs font-semibold'
                        : 'bg-muted/60 text-muted-foreground hover:bg-muted hover:text-foreground',
                ]"
                @click="activeCategoryFilter = 'all'"
            >
                Semua Kategori ({{ permissions.length }})
            </button>
            <button
                v-for="cat in categoryList"
                :key="cat"
                type="button"
                class="rounded-lg px-2.5 py-1 text-xs font-medium transition-colors"
                :class="[
                    activeCategoryFilter === cat
                        ? 'bg-primary text-primary-foreground shadow-xs font-semibold'
                        : 'bg-muted/60 text-muted-foreground hover:bg-muted hover:text-foreground',
                ]"
                @click="activeCategoryFilter = cat"
            >
                {{ cat }} ({{ groupedPermissions[cat]?.length || 0 }})
            </button>
        </div>

        <!-- Categorized Permission Matrix Grid -->
        <div class="space-y-5 pt-2">
            <div
                v-for="(perms, groupName) in filteredCategories"
                :key="groupName"
                class="rounded-2xl border border-border/70 bg-muted/20 p-4"
            >
                <!-- Group Header -->
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-border/50 pb-2.5">
                    <div class="flex items-center gap-2">
                        <div class="flex size-6 items-center justify-center rounded-lg bg-primary/10 text-primary">
                            <KeyRound class="size-3.5" />
                        </div>
                        <h4 class="text-xs font-bold text-foreground uppercase tracking-wider">
                            {{ groupName }}
                        </h4>
                        <span class="text-[11px] text-muted-foreground">
                            ({{ perms.filter((p) => isChecked(p.name)).length }} / {{ perms.length }} aktif)
                        </span>
                    </div>

                    <!-- Quick Group Select / Deselect if Editable -->
                    <div v-if="isEditable" class="flex items-center gap-1.5 text-xs">
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-medium text-primary hover:bg-primary/10"
                            @click="selectAllInGroup(perms)"
                        >
                            <CheckCheck class="size-3" />
                            <span>Pilih Semua</span>
                        </button>
                        <span class="text-muted-foreground/40">·</span>
                        <button
                            type="button"
                            class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-[11px] font-medium text-muted-foreground hover:text-destructive hover:bg-destructive/10"
                            @click="deselectAllInGroup(perms)"
                        >
                            <RotateCcw class="size-3" />
                            <span>Kosongkan</span>
                        </button>
                    </div>
                </div>

                <!-- Permission Checkbox Items Grid -->
                <div class="mt-3 grid gap-2.5 sm:grid-cols-2 lg:grid-cols-3">
                    <label
                        v-for="perm in perms"
                        :key="perm.name"
                        :for="`perm-${role.id}-${perm.name}`"
                        class="group relative flex cursor-pointer select-none items-start gap-3 rounded-xl border p-3 transition-all duration-200"
                        :class="[
                            isChecked(perm.name)
                                ? 'border-indigo-500/50 bg-indigo-50/60 shadow-xs dark:border-indigo-500/40 dark:bg-indigo-950/30'
                                : 'border-border/60 bg-card/60 hover:border-border hover:bg-muted/40 dark:bg-slate-900/40',
                            !isEditable ? 'cursor-not-allowed opacity-80' : 'hover:shadow-xs',
                        ]"
                    >
                        <div class="pt-0.5 shrink-0">
                            <Checkbox
                                :id="`perm-${role.id}-${perm.name}`"
                                :model-value="isChecked(perm.name)"
                                :disabled="!isEditable"
                                class="size-4.5 rounded-md"
                                @update:model-value="togglePermission(perm.name, Boolean($event))"
                            />
                        </div>

                        <div class="min-w-0 flex-1 space-y-1">
                            <div class="flex items-start justify-between gap-1">
                                <span
                                    class="text-xs font-semibold leading-tight text-foreground transition-colors group-hover:text-primary"
                                    :class="{ 'text-indigo-700 dark:text-indigo-300 font-bold': isChecked(perm.name) }"
                                >
                                    {{ perm.label }}
                                </span>
                            </div>

                            <p class="font-mono text-[10px] text-muted-foreground/80 break-all">
                                {{ perm.name }}
                            </p>

                            <p class="text-[11px] leading-relaxed text-muted-foreground line-clamp-2">
                                {{ perm.description }}
                            </p>
                        </div>
                    </label>
                </div>
            </div>
        </div>

        <!-- Sticky / Inline Action Bar when Dirty -->
        <div
            v-if="isDirty && isEditable"
            class="sticky bottom-4 z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-indigo-500/40 bg-card p-4 shadow-xl shadow-indigo-500/10 backdrop-blur-xl dark:bg-slate-900"
        >
            <div class="flex items-center gap-2">
                <div class="flex size-8 items-center justify-center rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400">
                    <Info class="size-4" />
                </div>
                <div>
                    <p class="text-xs font-bold text-foreground">
                        Perubahan Belum Disimpan
                    </p>
                    <p class="text-[11px] text-muted-foreground">
                        {{ selectedPermissions.length }} permission dipilih untuk role <span class="font-semibold text-foreground">{{ role.name }}</span>.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    class="h-9 rounded-xl text-xs gap-1.5"
                    :disabled="isSaving"
                    @click="resetChanges"
                >
                    <Undo2 class="size-3.5" />
                    <span>Batalkan</span>
                </Button>

                <Button
                    type="button"
                    size="sm"
                    class="h-9 rounded-xl bg-indigo-600 px-4 text-xs font-semibold text-white shadow-md hover:bg-indigo-700 gap-1.5"
                    :disabled="isSaving"
                    @click="savePermissions"
                >
                    <Spinner v-if="isSaving" class="size-3.5" />
                    <Save v-else class="size-3.5" />
                    <span>{{ isSaving ? 'Menyimpan...' : 'Simpan Permission' }}</span>
                </Button>
            </div>
        </div>
    </div>
</template>
