<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Search, UsersRound } from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';
import { ref, watch } from 'vue';
import AuthorizationUserPagination from '@/components/back-office/authorization/users/AuthorizationUserPagination.vue';
import InternalUserDirectory from '@/components/back-office/authorization/users/InternalUserDirectory.vue';
import UserRoleAssignmentDialog from '@/components/back-office/authorization/users/UserRoleAssignmentDialog.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type {
    AuthorizationFilters,
    AuthorizationMutationSecurity,
    AuthorizationRole,
    AuthorizationUser,
    PaginatedAuthorizationUsers,
} from '@/types';

const props = defineProps<{
    users: PaginatedAuthorizationUsers;
    roles: AuthorizationRole[];
    filters: AuthorizationFilters;
    security: AuthorizationMutationSecurity;
    indexUrl: string;
}>();

const search = ref(props.filters.user_search);
const selectedUser = ref<AuthorizationUser | null>(null);
const assignmentOpen = ref(false);
const applySearch = useDebounceFn((value: string) => {
    router.get(
        props.indexUrl,
        { tab: 'users', user_search: value || undefined },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['users', 'filters'],
        },
    );
}, 300);

watch(search, (value) => applySearch(value.trim()));

function editUser(user: AuthorizationUser): void {
    selectedUser.value = user;
    assignmentOpen.value = true;
}
</script>

<template>
    <Card class="overflow-hidden py-0 shadow-sm">
        <CardHeader class="border-b p-5 sm:p-6">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
            >
                <div>
                    <CardTitle class="flex items-center gap-2 text-lg">
                        <UsersRound
                            class="size-5 text-violet-600"
                            aria-hidden="true"
                        />
                        Assignment pengguna
                    </CardTitle>
                    <p
                        class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground"
                    >
                        Role hanya dapat ditambahkan pada akun internal aktif
                        dan terverifikasi. Pencabutan tetap tersedia untuk
                        proses offboarding.
                    </p>
                </div>
                <div class="relative w-full sm:max-w-xs">
                    <Label for="internal-user-search" class="sr-only">
                        Cari akun internal
                    </Label>
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        id="internal-user-search"
                        v-model="search"
                        type="search"
                        class="h-11 pl-9"
                        placeholder="Cari nama atau email..."
                        autocomplete="off"
                    />
                </div>
            </div>
        </CardHeader>

        <CardContent class="p-0">
            <InternalUserDirectory
                :users="users.data"
                :can-mutate="security.can_mutate"
                @edit="editUser"
            />
            <AuthorizationUserPagination :pagination="users" />
        </CardContent>
    </Card>

    <UserRoleAssignmentDialog
        v-model:open="assignmentOpen"
        :user="selectedUser"
        :roles="roles"
    />
</template>
