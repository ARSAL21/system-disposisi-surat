<script setup lang="ts">
import { ShieldCheck, UsersRound } from '@lucide/vue';
import { TabsContent, TabsList, TabsRoot, TabsTrigger } from 'reka-ui';
import { ref, watch } from 'vue';
import RoleWorkspace from '@/components/back-office/authorization/roles/RoleWorkspace.vue';
import UserRoleWorkspace from '@/components/back-office/authorization/users/UserRoleWorkspace.vue';
import type {
    AuthorizationFilters,
    AuthorizationMutationSecurity,
    AuthorizationPermission,
    AuthorizationRole,
    AuthorizationRoutes,
    PaginatedAuthorizationUsers,
} from '@/types';

const props = defineProps<{
    roles: AuthorizationRole[];
    permissions: AuthorizationPermission[];
    users: PaginatedAuthorizationUsers;
    filters: AuthorizationFilters;
    security: AuthorizationMutationSecurity;
    routes: AuthorizationRoutes;
}>();

const activeTab = ref<'roles' | 'users'>(props.filters.tab);

watch(activeTab, (tab) => {
    const url = new URL(window.location.href);
    url.searchParams.set('tab', tab);
    window.history.replaceState(window.history.state, '', url);
});
</script>

<template>
    <TabsRoot v-model="activeTab" class="space-y-5" activation-mode="manual">
        <TabsList
            class="grid w-full max-w-xl grid-cols-2 rounded-2xl border bg-muted/50 p-1.5"
            aria-label="Area pengelolaan otorisasi"
        >
            <TabsTrigger
                value="roles"
                class="flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-xl px-4 text-sm font-medium text-muted-foreground transition-colors duration-200 outline-none hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm"
            >
                <ShieldCheck class="size-4" aria-hidden="true" />
                Role & Permission
            </TabsTrigger>
            <TabsTrigger
                value="users"
                class="flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-xl px-4 text-sm font-medium text-muted-foreground transition-colors duration-200 outline-none hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring data-[state=active]:bg-background data-[state=active]:text-foreground data-[state=active]:shadow-sm"
            >
                <UsersRound class="size-4" aria-hidden="true" />
                Assignment Pengguna
            </TabsTrigger>
        </TabsList>

        <TabsContent value="roles" class="outline-none">
            <RoleWorkspace
                :roles="roles"
                :permissions="permissions"
                :security="security"
                :routes="routes"
            />
        </TabsContent>

        <TabsContent value="users" class="outline-none">
            <UserRoleWorkspace
                :users="users"
                :roles="roles"
                :filters="filters"
                :security="security"
                :index-url="routes.index"
            />
        </TabsContent>
    </TabsRoot>
</template>
