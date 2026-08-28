<script setup lang="ts">
import { UserRound } from '@lucide/vue';
import InternalUserCards from '@/components/back-office/authorization/users/InternalUserCards.vue';
import InternalUserTable from '@/components/back-office/authorization/users/InternalUserTable.vue';
import type { AuthorizationUser } from '@/types';

defineProps<{
    users: AuthorizationUser[];
    canMutate: boolean;
}>();

defineEmits<{ edit: [user: AuthorizationUser] }>();
</script>

<template>
    <div v-if="users.length">
        <InternalUserTable
            :users="users"
            :can-mutate="canMutate"
            @edit="$emit('edit', $event)"
        />
        <InternalUserCards
            :users="users"
            :can-mutate="canMutate"
            @edit="$emit('edit', $event)"
        />
    </div>

    <div v-else class="px-5 py-16 text-center">
        <UserRound
            class="mx-auto size-9 text-muted-foreground/60"
            aria-hidden="true"
        />
        <p class="mt-3 font-medium">Akun internal tidak ditemukan</p>
        <p class="mt-1 text-sm text-muted-foreground">
            Periksa kata kunci nama atau email yang digunakan.
        </p>
    </div>
</template>
