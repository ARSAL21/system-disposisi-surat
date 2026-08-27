<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import BackOfficeLoginForm from '@/components/back-office/auth/BackOfficeLoginForm.vue';

defineOptions({
    layout: {
        title: 'Masuk ke Back-office',
        description:
            'Gunakan akun internal aktif yang telah disediakan oleh administrator sistem.',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Login Back-office" />
    <BackOfficeLoginForm
        :status="status"
        :can-reset-password="canResetPassword"
    />
</template>
