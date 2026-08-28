<script setup lang="ts">
import { Plus } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import CreateRoleDialog from '@/components/back-office/authorization/roles/CreateRoleDialog.vue';
import DeleteRoleDialog from '@/components/back-office/authorization/roles/DeleteRoleDialog.vue';
import PermissionEditorDialog from '@/components/back-office/authorization/roles/PermissionEditorDialog.vue';
import RenameRoleDialog from '@/components/back-office/authorization/roles/RenameRoleDialog.vue';
import RoleDetailsPanel from '@/components/back-office/authorization/roles/RoleDetailsPanel.vue';
import RoleDirectory from '@/components/back-office/authorization/roles/RoleDirectory.vue';
import { Button } from '@/components/ui/button';
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

const search = ref('');
const selectedRoleId = ref<number | null>(props.roles[0]?.id ?? null);
const createOpen = ref(false);
const renameOpen = ref(false);
const deleteOpen = ref(false);
const permissionsOpen = ref(false);

const filteredRoles = computed(() => {
    const query = search.value.trim().toLocaleLowerCase('id-ID');

    return query
        ? props.roles.filter((role) => role.name.includes(query))
        : props.roles;
});
const selectedRole = computed(
    () => props.roles.find((role) => role.id === selectedRoleId.value) ?? null,
);

watch(
    () => props.roles,
    (roles) => {
        if (!roles.some((role) => role.id === selectedRoleId.value)) {
            selectedRoleId.value = roles[0]?.id ?? null;
        }
    },
);
</script>

<template>
    <section class="space-y-4" aria-label="Role dan permission">
        <div class="flex justify-end">
            <Button
                type="button"
                class="min-h-11 cursor-pointer"
                :disabled="!security.can_mutate"
                @click="createOpen = true"
            >
                <Plus class="size-4" aria-hidden="true" />
                Buat custom role
            </Button>
        </div>

        <div class="grid gap-5 lg:grid-cols-[20rem_minmax(0,1fr)]">
            <RoleDirectory
                :roles="filteredRoles"
                :selected-role-id="selectedRoleId"
                :search="search"
                @select="selectedRoleId = $event"
                @update:search="search = $event"
            />
            <RoleDetailsPanel
                :role="selectedRole"
                :permissions="permissions"
                :can-mutate="security.can_mutate"
                @rename="renameOpen = true"
                @delete="deleteOpen = true"
                @edit-permissions="permissionsOpen = true"
            />
        </div>

        <CreateRoleDialog v-model:open="createOpen" :store-url="routes.store" />
        <RenameRoleDialog v-model:open="renameOpen" :role="selectedRole" />
        <DeleteRoleDialog v-model:open="deleteOpen" :role="selectedRole" />
        <PermissionEditorDialog
            v-model:open="permissionsOpen"
            :role="selectedRole"
            :permissions="permissions"
        />
    </section>
</template>
