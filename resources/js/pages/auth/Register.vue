<script setup lang="ts">
import PublicAuthCard from '@/components/public/auth/PublicAuthCard.vue';

defineProps<{
    passwordRules: string;
}>();
</script>

<template>
    <PublicAuthCard
        initial-mode="register"
        :password-rules="passwordRules"
    />
</template>
