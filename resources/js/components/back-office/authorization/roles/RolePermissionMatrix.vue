<script setup lang="ts">
import { Check, Circle, KeyRound } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { AuthorizationPermission, AuthorizationRole } from '@/types';

defineProps<{
    role: AuthorizationRole;
    permissions: AuthorizationPermission[];
    canMutate: boolean;
}>();

defineEmits<{ edit: [] }>();
</script>

<template>
    <section aria-labelledby="role-permissions-heading">
        <div
            class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between"
        >
            <div>
                <h2 id="role-permissions-heading" class="font-semibold">
                    Permission role
                </h2>
                <p class="mt-1 text-sm leading-6 text-muted-foreground">
                    Capability resmi yang efektif melalui role ini.
                </p>
            </div>
            <Button
                v-if="!role.is_protected"
                type="button"
                class="min-h-11 cursor-pointer"
                :disabled="
                    !canMutate || !role.capabilities.synchronize_permissions
                "
                @click="$emit('edit')"
            >
                <KeyRound class="size-4" aria-hidden="true" />
                Atur permission
            </Button>
        </div>

        <div class="mt-4 grid gap-3 md:grid-cols-2">
            <article
                v-for="permission in permissions"
                :key="permission.name"
                class="flex gap-3 rounded-2xl border p-4 transition-colors duration-200"
                :class="
                    role.permissions.includes(permission.name)
                        ? 'border-blue-200 bg-blue-50/60 dark:border-blue-900 dark:bg-blue-950/25'
                        : 'border-slate-200 bg-slate-50/50 dark:border-slate-800 dark:bg-slate-900/40'
                "
            >
                <span
                    class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full"
                    :class="
                        role.permissions.includes(permission.name)
                            ? 'bg-blue-600 text-white'
                            : 'bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-400'
                    "
                >
                    <Check
                        v-if="role.permissions.includes(permission.name)"
                        class="size-4"
                        aria-hidden="true"
                    />
                    <Circle v-else class="size-3" aria-hidden="true" />
                </span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold">{{ permission.label }}</p>
                    <code
                        class="mt-1 block text-xs break-all text-blue-700 dark:text-blue-300"
                        >{{ permission.name }}</code
                    >
                    <p class="mt-2 text-sm leading-6 text-muted-foreground">
                        {{ permission.description }}
                    </p>
                </div>
            </article>
        </div>
    </section>
</template>
