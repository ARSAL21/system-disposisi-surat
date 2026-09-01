<script setup lang="ts">
import {
    ChevronDown,
    KeyRound,
    LockKeyhole,
    Maximize2,
    Minimize2,
    Pencil,
    Plus,
    Search,
    Shield,
    ShieldCheck,
    Trash2,
    UserCheck,
    UsersRound,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import CreateRoleDialog from '@/components/back-office/authorization/roles/CreateRoleDialog.vue';
import DeleteRoleDialog from '@/components/back-office/authorization/roles/DeleteRoleDialog.vue';
import RenameRoleDialog from '@/components/back-office/authorization/roles/RenameRoleDialog.vue';
import RolePermissionMatrix from '@/components/back-office/authorization/roles/RolePermissionMatrix.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import type {
    AuthorizationMutationSecurity,
    AuthorizationPermission,
    AuthorizationRole,
    AuthorizationRoutes,
} from '@/types';

const props = defineProps<{
    roles: AuthorizationRole[];
    permissions: AuthorizationPermission[];
    security: AuthorizationMutationSecurity;
    routes: AuthorizationRoutes;
}>();

// Filter & Search state
const search = ref('');
const roleTypeFilter = ref<'all' | 'custom' | 'protected'>('all');

// Expanded cards state (Set of role IDs)
// By default, expand all custom roles or the first role
const expandedRoleIds = ref<Set<number>>(
    new Set(
        props.roles.filter((r) => !r.is_protected).map((r) => r.id).length > 0
            ? props.roles.filter((r) => !r.is_protected).map((r) => r.id)
            : props.roles.slice(0, 1).map((r) => r.id),
    ),
);

// Dialog states for Create / Rename / Delete
const createOpen = ref(false);
const renameOpen = ref(false);
const deleteOpen = ref(false);
const selectedRoleForAction = ref<AuthorizationRole | null>(null);

function openRename(role: AuthorizationRole) {
    selectedRoleForAction.value = role;
    renameOpen.value = true;
}

function openDelete(role: AuthorizationRole) {
    selectedRoleForAction.value = role;
    deleteOpen.value = true;
}

// Expand / Collapse Helpers
function toggleExpand(roleId: number) {
    if (expandedRoleIds.value.has(roleId)) {
        expandedRoleIds.value.delete(roleId);
    } else {
        expandedRoleIds.value.add(roleId);
    }
}

function expandAll() {
    expandedRoleIds.value = new Set(props.roles.map((r) => r.id));
}

function collapseAll() {
    expandedRoleIds.value.clear();
}

// Filtered roles computation
const filteredRoles = computed(() => {
    let result = props.roles;

    if (roleTypeFilter.value === 'custom') {
        result = result.filter((r) => !r.is_protected);
    } else if (roleTypeFilter.value === 'protected') {
        result = result.filter((r) => r.is_protected);
    }

    const query = search.value.trim().toLowerCase();

    if (query) {
        result = result.filter((role) => {
            const matchName = role.name.toLowerCase().includes(query);
            const matchPermissions = role.permissions.some((permName) =>
                permName.toLowerCase().includes(query),
            );

            return matchName || matchPermissions;
        });
    }

    return result;
});

// Summary metrics
const customRolesCount = computed(
    () => props.roles.filter((r) => !r.is_protected).length,
);
const protectedRolesCount = computed(
    () => props.roles.filter((r) => r.is_protected).length,
);
</script>

<template>
    <section class="space-y-6" aria-label="Pengelolaan Role dan Permission">
        <!-- ======================================================== -->
        <!-- 1. TOOLBAR, SEARCH & FILTER CONTROL PANEL                -->
        <!-- ======================================================== -->
        <Card class="overflow-hidden border-border/80 bg-card p-5 shadow-sm sm:p-6 dark:bg-slate-900/90">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <!-- Title & Context -->
                <div>
                    <div class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400">
                        <ShieldCheck class="size-4" />
                        <span>Manajemen Hak Akses & Matriks Permission</span>
                    </div>
                    <h3 class="mt-1 text-lg font-bold tracking-tight text-foreground sm:text-xl">
                        Daftar Role & Konfigurasi Hak Akses Langsung
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        Atur capability dan permission untuk setiap role langsung melalui checkbox interaktif di bawah.
                    </p>
                </div>

                <!-- Create Custom Role Button -->
                <div class="flex items-center gap-2">
                    <Button
                        type="button"
                        class="h-10 gap-2 rounded-xl bg-indigo-600 px-4 font-semibold text-white shadow-sm hover:bg-indigo-700"
                        :disabled="!security.can_mutate"
                        @click="createOpen = true"
                    >
                        <Plus class="size-4" />
                        <span>Buat Custom Role</span>
                    </Button>
                </div>
            </div>

            <!-- Filters & Search Bar Row -->
            <div class="mt-5 flex flex-col gap-3 border-t border-border/60 pt-4 sm:flex-row sm:items-center sm:justify-between">
                <!-- Search Box -->
                <div class="relative w-full sm:max-w-xs">
                    <Search class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        v-model="search"
                        type="text"
                        placeholder="Cari role atau permission..."
                        class="h-9.5 rounded-xl pl-9 text-xs"
                    />
                </div>

                <!-- Filter Tabs & Expand/Collapse Toggle -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex rounded-xl border border-border/70 bg-muted/40 p-0.5 text-xs">
                        <button
                            type="button"
                            class="rounded-lg px-3 py-1 font-semibold transition-colors"
                            :class="[
                                roleTypeFilter === 'all'
                                    ? 'bg-background text-foreground shadow-xs'
                                    : 'text-muted-foreground hover:text-foreground',
                            ]"
                            @click="roleTypeFilter = 'all'"
                        >
                            Semua Role ({{ roles.length }})
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-3 py-1 font-semibold transition-colors"
                            :class="[
                                roleTypeFilter === 'custom'
                                    ? 'bg-background text-foreground shadow-xs'
                                    : 'text-muted-foreground hover:text-foreground',
                            ]"
                            @click="roleTypeFilter = 'custom'"
                        >
                            Custom Role ({{ customRolesCount }})
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-3 py-1 font-semibold transition-colors"
                            :class="[
                                roleTypeFilter === 'protected'
                                    ? 'bg-background text-foreground shadow-xs'
                                    : 'text-muted-foreground hover:text-foreground',
                            ]"
                            @click="roleTypeFilter = 'protected'"
                        >
                            Sistem / Protected ({{ protectedRolesCount }})
                        </button>
                    </div>

                    <!-- Global Expand/Collapse Button -->
                    <Button
                        size="sm"
                        variant="outline"
                        class="h-9 rounded-xl text-xs gap-1.5"
                        @click="expandedRoleIds.size > 0 ? collapseAll() : expandAll()"
                    >
                        <Minimize2 v-if="expandedRoleIds.size > 0" class="size-3.5" />
                        <Maximize2 v-else class="size-3.5" />
                        <span>{{ expandedRoleIds.size > 0 ? 'Tutup Semua' : 'Buka Semua' }}</span>
                    </Button>
                </div>
            </div>
        </Card>

        <!-- ======================================================== -->
        <!-- 2. UNIFIED ROLE CARDS WITH INLINE PERMISSION MATRIX      -->
        <!-- ======================================================== -->
        <div v-if="filteredRoles.length > 0" class="space-y-4">
            <Card
                v-for="role in filteredRoles"
                :key="role.id"
                class="overflow-hidden border-border/80 transition-all duration-200 hover:border-border"
                :class="[
                    role.is_protected
                        ? 'bg-card/70 dark:bg-slate-900/60'
                        : 'bg-card dark:bg-slate-900',
                ]"
            >
                <!-- Role Card Header -->
                <div
                    class="flex flex-col gap-3 p-5 sm:flex-row sm:items-center sm:justify-between border-b border-border/50 bg-gradient-to-r from-slate-50/50 to-transparent dark:from-slate-950/40"
                >
                    <!-- Role Identity & Badges -->
                    <div class="flex items-start sm:items-center gap-3.5 min-w-0">
                        <div
                            class="flex size-11 shrink-0 items-center justify-center rounded-2xl shadow-xs"
                            :class="[
                                role.is_protected
                                    ? 'bg-violet-500/15 text-violet-700 dark:text-violet-300'
                                    : role.is_assigned_to_actor
                                      ? 'bg-amber-500/15 text-amber-700 dark:text-amber-300'
                                      : 'bg-indigo-500/15 text-indigo-700 dark:text-indigo-300',
                            ]"
                        >
                            <LockKeyhole v-if="role.is_protected" class="size-5" />
                            <UserCheck v-else-if="role.is_assigned_to_actor" class="size-5" />
                            <Shield v-else class="size-5" />
                        </div>

                        <div class="min-w-0 space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h4 class="text-base font-bold text-foreground truncate">
                                    {{ role.name }}
                                </h4>

                                <Badge
                                    v-if="role.is_protected"
                                    variant="outline"
                                    class="border-violet-500/30 bg-violet-500/10 text-violet-700 dark:text-violet-300 text-[10px] font-bold"
                                >
                                    Protected (Sistem)
                                </Badge>
                                <Badge
                                    v-else
                                    variant="secondary"
                                    class="text-[10px] font-semibold"
                                >
                                    Custom Role
                                </Badge>

                                <Badge
                                    v-if="role.is_assigned_to_actor"
                                    variant="outline"
                                    class="border-amber-500/30 bg-amber-500/10 text-amber-700 dark:text-amber-300 text-[10px] font-bold"
                                >
                                    Akun Anda
                                </Badge>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                <span class="inline-flex items-center gap-1">
                                    <UsersRound class="size-3.5" />
                                    <span>{{ role.user_count }} pengguna</span>
                                </span>
                                <span>·</span>
                                <span class="inline-flex items-center gap-1">
                                    <KeyRound class="size-3.5" />
                                    <span class="font-semibold text-foreground">{{ role.permissions.length }}</span>
                                    <span>/ {{ permissions.length }} permission aktif</span>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Role Card Actions -->
                    <div class="flex items-center gap-2 self-end sm:self-center">
                        <!-- Custom Role Rename/Delete Actions -->
                        <template v-if="!role.is_protected">
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-8.5 rounded-xl text-xs gap-1.5"
                                :disabled="!security.can_mutate || !role.capabilities.rename"
                                @click="openRename(role)"
                            >
                                <Pencil class="size-3.5" />
                                <span>Ubah Nama</span>
                            </Button>

                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                class="h-8.5 rounded-xl text-xs gap-1.5 text-destructive hover:bg-destructive/10 hover:text-destructive"
                                :disabled="!security.can_mutate || !role.capabilities.delete || role.user_count > 0"
                                :title="role.user_count > 0 ? 'Cabut semua assignment pengguna sebelum menghapus role' : undefined"
                                @click="openDelete(role)"
                            >
                                <Trash2 class="size-3.5" />
                                <span>Hapus</span>
                            </Button>
                        </template>

                        <!-- Expand / Collapse Button -->
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            class="h-8.5 rounded-xl text-xs gap-1.5 font-semibold"
                            @click="toggleExpand(role.id)"
                        >
                            <span>{{ expandedRoleIds.has(role.id) ? 'Tutup Matriks' : 'Lihat & Atur Permission' }}</span>
                            <ChevronDown
                                class="size-3.5 transition-transform duration-200"
                                :class="{ 'rotate-180': expandedRoleIds.has(role.id) }"
                            />
                        </Button>
                    </div>
                </div>

                <!-- Role Card Body: Direct Interactive Permission Matrix -->
                <CardContent v-if="expandedRoleIds.has(role.id)" class="p-5 sm:p-6">
                    <RolePermissionMatrix
                        :role="role"
                        :permissions="permissions"
                        :can-mutate="security.can_mutate"
                    />
                </CardContent>
            </Card>
        </div>

        <!-- Empty Search State -->
        <Card v-else class="flex flex-col items-center justify-center p-12 text-center border-dashed">
            <div class="flex size-12 items-center justify-center rounded-2xl bg-muted text-muted-foreground">
                <Search class="size-6" />
            </div>
            <h4 class="mt-4 text-base font-bold text-foreground">
                Tidak Ada Role Ditemukan
            </h4>
            <p class="mt-1 text-xs text-muted-foreground max-w-sm">
                Tidak ada role yang cocok dengan pencarian atau filter yang dipilih. Silakan ubah filter atau kata kunci Anda.
            </p>
        </Card>

        <!-- Dialogs -->
        <CreateRoleDialog v-model:open="createOpen" :store-url="routes.store" />
        <RenameRoleDialog v-model:open="renameOpen" :role="selectedRoleForAction" />
        <DeleteRoleDialog v-model:open="deleteOpen" :role="selectedRoleForAction" />
    </section>
</template>
