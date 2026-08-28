<script setup lang="ts">
import { LockKeyhole, Pencil, UserRound } from '@lucide/vue';
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
    <div class="grid gap-3 p-3 md:hidden">
        <article
            v-for="user in users"
            :key="user.id"
            class="rounded-2xl border p-4"
        >
            <div class="flex items-start gap-3">
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-200"
                >
                    <UserRound class="size-5" aria-hidden="true" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="font-semibold">{{ user.name }}</p>
                    <p class="mt-1 text-sm break-all text-muted-foreground">
                        {{ user.email }}
                    </p>
                </div>
            </div>
            <div class="mt-4 flex flex-wrap gap-1.5">
                <Badge :variant="user.is_active ? 'secondary' : 'outline'">
                    {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                </Badge>
                <Badge :variant="user.is_verified ? 'secondary' : 'outline'">
                    {{
                        user.is_verified ? 'Terverifikasi' : 'Belum verifikasi'
                    }}
                </Badge>
                <Badge
                    v-for="role in user.roles"
                    :key="role.id"
                    variant="outline"
                >
                    <LockKeyhole
                        v-if="role.is_protected"
                        class="size-3"
                        aria-hidden="true"
                    />
                    {{ role.name }}
                </Badge>
            </div>
            <Button
                type="button"
                variant="outline"
                class="mt-4 min-h-11 w-full"
                :disabled="!canMutate || !user.capabilities.synchronize_roles"
                @click="$emit('edit', user)"
            >
                <Pencil class="size-4" aria-hidden="true" />
                Atur assignment role
            </Button>
        </article>
    </div>
</template>
