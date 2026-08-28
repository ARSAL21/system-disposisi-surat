<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthorizationIndexHeader from '@/components/back-office/authorization/AuthorizationIndexHeader.vue';
import AuthorizationWorkspaceTabs from '@/components/back-office/authorization/AuthorizationWorkspaceTabs.vue';
import MutationSecurityBanner from '@/components/back-office/authorization/MutationSecurityBanner.vue';
import { index } from '@/routes/back-office/authorization';
import type {
    AuthorizationFilters,
    AuthorizationMutationSecurity,
    AuthorizationPermission,
    AuthorizationRole,
    AuthorizationRoutes,
    AuthorizationSummary,
    PaginatedAuthorizationUsers,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Manage Role',
                href: index(),
            },
        ],
    },
});

defineProps<{
    roles: AuthorizationRole[];
    permissions: AuthorizationPermission[];
    users: PaginatedAuthorizationUsers;
    filters: AuthorizationFilters;
    summary: AuthorizationSummary;
    mutationSecurity: AuthorizationMutationSecurity;
    routes: AuthorizationRoutes;
}>();
</script>

<template>
    <Head title="Manage Role" />

    <main
        class="flex flex-1 flex-col gap-6 p-4 sm:p-6 lg:p-8"
        aria-labelledby="authorization-title"
    >
        <AuthorizationIndexHeader :summary="summary" />
        <MutationSecurityBanner :security="mutationSecurity" />
        <AuthorizationWorkspaceTabs
            :roles="roles"
            :permissions="permissions"
            :users="users"
            :filters="filters"
            :security="mutationSecurity"
            :routes="routes"
        />
    </main>
</template>
