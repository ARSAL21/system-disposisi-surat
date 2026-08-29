<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import BackOfficeConfirmPasswordForm from '@/components/back-office/auth/BackOfficeConfirmPasswordForm.vue';

defineOptions({
    layout: {
        title: 'Konfirmasi akses administratif',
        description:
            'Area ini memerlukan verifikasi ulang sebelum data administratif sensitif dapat diubah.',
    },
});

defineProps<{ confirmPasswordUrl: string }>();
</script>

<template>
    <Head title="Konfirmasi Akses Back-office" />
    <BackOfficeConfirmPasswordForm :confirm-password-url="confirmPasswordUrl" />
</template>
