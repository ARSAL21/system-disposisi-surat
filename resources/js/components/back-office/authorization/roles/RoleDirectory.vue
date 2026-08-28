<script setup lang="ts">
import { LockKeyhole, Search, Shield, UsersRound } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AuthorizationRole } from '@/types';

defineProps<{
    roles: AuthorizationRole[];
    selectedRoleId: number | null;
    search: string;
}>();

defineEmits<{
    select: [roleId: number];
    'update:search': [value: string];
}>();
</script>

<template>
    <Card class="overflow-hidden py-0 shadow-sm">
        <CardHeader class="border-b p-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <CardTitle class="text-base">Direktori role</CardTitle>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Pilih role untuk memeriksa capability.
                    </p>
                </div>
                <Badge variant="secondary" class="tabular-nums">
                    {{ roles.length }} role
                </Badge>
            </div>
            <div class="relative mt-4">
                <Label for="role-search" class="sr-only">Cari role</Label>
                <Search
                    class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    id="role-search"
                    :model-value="search"
                    class="h-11 pl-9"
                    placeholder="Cari nama role..."
                    autocomplete="off"
                    @update:model-value="$emit('update:search', String($event))"
                />
            </div>
        </CardHeader>

        <CardContent class="p-2">
            <div v-if="roles.length" class="space-y-1" role="list">
                <button
                    v-for="role in roles"
                    :key="role.id"
                    type="button"
                    class="group flex min-h-16 w-full cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-left transition-colors duration-200 outline-none hover:bg-blue-50 focus-visible:ring-2 focus-visible:ring-ring dark:hover:bg-blue-950/30"
                    :class="
                        selectedRoleId === role.id
                            ? 'bg-blue-50 ring-1 ring-blue-200 dark:bg-blue-950/35 dark:ring-blue-900'
                            : ''
                    "
                    :aria-current="
                        selectedRoleId === role.id ? 'true' : undefined
                    "
                    role="listitem"
                    @click="$emit('select', role.id)"
                >
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-600 transition-colors group-hover:bg-blue-100 group-hover:text-blue-700 dark:bg-slate-800 dark:text-slate-300 dark:group-hover:bg-blue-900/60 dark:group-hover:text-blue-200"
                    >
                        <LockKeyhole
                            v-if="role.is_protected"
                            class="size-4"
                            aria-hidden="true"
                        />
                        <Shield v-else class="size-4" aria-hidden="true" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <span class="truncate text-sm font-semibold">
                                {{ role.name }}
                            </span>
                            <Badge
                                v-if="role.is_protected"
                                variant="outline"
                                class="border-violet-200 bg-violet-50 text-[10px] text-violet-700 dark:border-violet-900 dark:bg-violet-950/40 dark:text-violet-300"
                            >
                                Protected
                            </Badge>
                        </span>
                        <span
                            class="mt-1 flex items-center gap-1.5 text-xs text-muted-foreground"
                        >
                            <UsersRound class="size-3.5" aria-hidden="true" />
                            {{ role.user_count }} pengguna ·
                            {{ role.permissions.length }} permission
                        </span>
                    </span>
                </button>
            </div>

            <div v-else class="px-4 py-12 text-center">
                <Search
                    class="mx-auto size-8 text-muted-foreground/60"
                    aria-hidden="true"
                />
                <p class="mt-3 text-sm font-medium">Role tidak ditemukan</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Coba gunakan kata kunci yang lebih singkat.
                </p>
            </div>
        </CardContent>
    </Card>
</template>
