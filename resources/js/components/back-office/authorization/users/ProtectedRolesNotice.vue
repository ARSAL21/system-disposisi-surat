<script setup lang="ts">
import { LockKeyhole } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import type { AuthorizationUserRole } from '@/types';

defineProps<{ roles: AuthorizationUserRole[] }>();
</script>

<template>
    <div
        v-if="roles.length"
        class="rounded-2xl border border-violet-200 bg-violet-50/70 p-4 dark:border-violet-900/70 dark:bg-violet-950/25"
    >
        <p class="flex items-center gap-2 text-sm font-semibold">
            <LockKeyhole class="size-4" aria-hidden="true" />
            Protected role
        </p>
        <div class="mt-3 flex flex-wrap gap-2">
            <Badge
                v-for="role in roles"
                :key="role.id"
                variant="outline"
                class="border-violet-200 bg-white/70 text-violet-700 dark:border-violet-900 dark:bg-violet-950/30 dark:text-violet-300"
            >
                <LockKeyhole class="size-3" aria-hidden="true" />
                {{ role.name }}
            </Badge>
        </div>
        <p class="mt-3 text-sm leading-6 text-muted-foreground">
            Gunakan command <code>authorization:super-admin</code> untuk
            perubahan yang terkontrol.
        </p>
    </div>
</template>
