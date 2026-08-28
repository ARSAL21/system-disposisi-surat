<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { History } from '@lucide/vue';
import BackOfficeModuleFoundation from '@/components/back-office/BackOfficeModuleFoundation.vue';
import { index } from '@/routes/back-office/privilege-audits';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Audit Perubahan Privilege',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Audit Perubahan Privilege" />

    <div class="flex flex-1 p-4 sm:p-6 lg:p-8">
        <BackOfficeModuleFoundation
            title="Audit Perubahan Privilege"
            description="Halaman ini menjadi landasan pembacaan audit role dan permission yang append-only. Query dan tampilan riwayat akan ditambahkan pada tahap berikutnya."
            :icon="History"
        />
    </div>
</template>
