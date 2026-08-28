<script setup lang="ts">
import {
    BadgeCheck,
    LockKeyhole,
    MailWarning,
    Pencil,
    UserX,
} from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { AuthorizationUser } from '@/types';

defineProps<{
    users: AuthorizationUser[];
    canMutate: boolean;
}>();

defineEmits<{ edit: [user: AuthorizationUser] }>();
</script>

<template>
    <div class="hidden overflow-x-auto md:block">
        <table class="w-full text-left text-sm">
            <caption class="sr-only">
                Akun internal beserta status dan assignment role
            </caption>
            <thead class="border-b bg-slate-50/70 dark:bg-slate-900/50">
                <tr
                    class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    <th scope="col" class="px-5 py-3.5">Akun internal</th>
                    <th scope="col" class="px-5 py-3.5">Status</th>
                    <th scope="col" class="px-5 py-3.5">Role</th>
                    <th scope="col" class="px-5 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                <tr
                    v-for="user in users"
                    :key="user.id"
                    class="transition-colors duration-200 hover:bg-blue-50/40 dark:hover:bg-blue-950/15"
                >
                    <td class="px-5 py-4">
                        <div class="flex min-w-52 items-center gap-3">
                            <span
                                class="flex size-10 shrink-0 items-center justify-center rounded-full bg-blue-100 font-semibold text-blue-700 dark:bg-blue-950 dark:text-blue-200"
                                aria-hidden="true"
                            >
                                {{ user.name.slice(0, 1).toUpperCase() }}
                            </span>
                            <div class="min-w-0">
                                <p class="truncate font-semibold">
                                    {{ user.name }}
                                </p>
                                <p
                                    class="mt-0.5 truncate text-muted-foreground"
                                >
                                    {{ user.email }}
                                </p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-4">
                        <div class="flex flex-col items-start gap-1.5">
                            <Badge
                                :variant="
                                    user.is_active ? 'secondary' : 'outline'
                                "
                                :class="
                                    user.is_active
                                        ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300'
                                        : 'text-muted-foreground'
                                "
                            >
                                <BadgeCheck
                                    v-if="user.is_active"
                                    class="size-3"
                                    aria-hidden="true"
                                />
                                <UserX
                                    v-else
                                    class="size-3"
                                    aria-hidden="true"
                                />
                                {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                            </Badge>
                            <span
                                class="flex items-center gap-1 text-xs text-muted-foreground"
                            >
                                <BadgeCheck
                                    v-if="user.is_verified"
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                                <MailWarning
                                    v-else
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                                {{
                                    user.is_verified
                                        ? 'Terverifikasi'
                                        : 'Belum verifikasi'
                                }}
                            </span>
                        </div>
                    </td>
                    <td class="max-w-sm px-5 py-4">
                        <div
                            v-if="user.roles.length"
                            class="flex flex-wrap gap-1.5"
                        >
                            <Badge
                                v-for="role in user.roles"
                                :key="role.id"
                                variant="outline"
                                :class="
                                    role.is_protected
                                        ? 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900 dark:bg-violet-950/40 dark:text-violet-300'
                                        : ''
                                "
                            >
                                <LockKeyhole
                                    v-if="role.is_protected"
                                    class="size-3"
                                    aria-hidden="true"
                                />
                                {{ role.name }}
                            </Badge>
                        </div>
                        <span v-else class="text-sm text-muted-foreground">
                            Belum memiliki role
                        </span>
                    </td>
                    <td class="px-5 py-4 text-right">
                        <Button
                            type="button"
                            variant="outline"
                            class="min-h-11 cursor-pointer"
                            :disabled="
                                !canMutate ||
                                !user.capabilities.synchronize_roles
                            "
                            @click="$emit('edit', user)"
                        >
                            <Pencil class="size-4" aria-hidden="true" />
                            Atur role
                        </Button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
