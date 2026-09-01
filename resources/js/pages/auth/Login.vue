<script setup lang="ts">
import PublicAuthCard from '@/components/public/auth/PublicAuthCard.vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <PublicAuthCard
        initial-mode="login"
        :status="status"
        :can-reset-password="canResetPassword"
    />
</template>
