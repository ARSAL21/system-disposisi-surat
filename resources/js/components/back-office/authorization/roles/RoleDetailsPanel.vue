<script setup lang="ts">
import {
    KeyRound,
    LockKeyhole,
    Pencil,
    ShieldCheck,
    Trash2,
    UsersRound,
} from '@lucide/vue';
import RolePermissionMatrix from '@/components/back-office/authorization/roles/RolePermissionMatrix.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { AuthorizationPermission, AuthorizationRole } from '@/types';

defineProps<{
    role: AuthorizationRole | null;
    permissions: AuthorizationPermission[];
    canMutate: boolean;
}>();

defineEmits<{
    rename: [];
    delete: [];
    editPermissions: [];
}>();
</script>

<template>
    <Card v-if="role" class="overflow-hidden py-0 shadow-sm">
        <CardHeader
            class="border-b bg-gradient-to-r from-slate-50 to-blue-50/60 p-5 sm:p-6 dark:from-slate-950 dark:to-blue-950/25"
        >
            <div
                class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between"
            >
                <div class="flex min-w-0 gap-4">
                    <span
                        class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-sm shadow-blue-600/20"
                    >
                        <LockKeyhole
                            v-if="role.is_protected"
                            class="size-5"
                            aria-hidden="true"
                        />
                        <ShieldCheck v-else class="size-5" aria-hidden="true" />
                    </span>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <CardTitle class="text-xl break-all">
                                {{ role.name }}
                            </CardTitle>
                            <Badge
                                :variant="
                                    role.is_protected ? 'outline' : 'secondary'
                                "
                                :class="
                                    role.is_protected
                                        ? 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900 dark:bg-violet-950/40 dark:text-violet-300'
                                        : ''
                                "
                            >
                                {{ role.is_protected ? 'Protected' : 'Custom' }}
                            </Badge>
                        </div>
                        <CardDescription
                            class="mt-2 flex flex-wrap gap-x-4 gap-y-1"
                        >
                            <span class="flex items-center gap-1.5">
                                <UsersRound class="size-4" aria-hidden="true" />
                                {{ role.user_count }} pengguna
                            </span>
                            <span class="flex items-center gap-1.5">
                                <KeyRound class="size-4" aria-hidden="true" />
                                {{ role.permissions.length }} permission
                            </span>
                        </CardDescription>
                    </div>
                </div>

                <div v-if="!role.is_protected" class="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        class="min-h-11 cursor-pointer"
                        :disabled="!canMutate || !role.capabilities.rename"
                        @click="$emit('rename')"
                    >
                        <Pencil class="size-4" aria-hidden="true" />
                        Rename
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        class="min-h-11 cursor-pointer text-destructive hover:text-destructive"
                        :disabled="
                            !canMutate ||
                            !role.capabilities.delete ||
                            role.user_count > 0
                        "
                        :title="
                            role.user_count > 0
                                ? 'Cabut seluruh assignment sebelum menghapus role.'
                                : undefined
                        "
                        @click="$emit('delete')"
                    >
                        <Trash2 class="size-4" aria-hidden="true" />
                        Hapus
                    </Button>
                </div>
            </div>
        </CardHeader>

        <CardContent class="space-y-6 p-5 sm:p-6">
            <div
                v-if="role.is_protected"
                class="flex gap-3 rounded-2xl border border-violet-200 bg-violet-50/70 p-4 text-sm dark:border-violet-900/70 dark:bg-violet-950/25"
            >
                <LockKeyhole
                    class="mt-0.5 size-4 shrink-0 text-violet-700 dark:text-violet-300"
                    aria-hidden="true"
                />
                <p class="leading-6 text-violet-900 dark:text-violet-100">
                    Mapping role ini read-only dan selalu dikembalikan ke
                    katalog resmi melalui <code>authorization:sync</code>.
                </p>
            </div>
            <div
                v-else-if="role.is_assigned_to_actor"
                class="flex gap-3 rounded-2xl border border-amber-200 bg-amber-50/70 p-4 text-sm dark:border-amber-900/70 dark:bg-amber-950/25"
            >
                <LockKeyhole
                    class="mt-0.5 size-4 shrink-0 text-amber-700 dark:text-amber-300"
                    aria-hidden="true"
                />
                <p class="leading-6 text-amber-900 dark:text-amber-100">
                    Role ini sedang Anda pegang. Self-change diblokir untuk
                    mencegah privilege escalation dan administrative lockout.
                </p>
            </div>

            <RolePermissionMatrix
                :role="role"
                :permissions="permissions"
                :can-mutate="canMutate"
                @edit="$emit('editPermissions')"
            />
        </CardContent>
    </Card>
</template>
