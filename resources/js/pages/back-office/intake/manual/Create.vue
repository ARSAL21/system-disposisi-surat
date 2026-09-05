<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import ManualIntakeForm from '@/components/back-office/intake/manual/ManualIntakeForm.vue';
import ManualIntakeHeader from '@/components/back-office/intake/manual/ManualIntakeHeader.vue';
import { manualIntakePreviewRoutes } from '@/lib/manualIntakePreview';
import type { ManualIntakePageProps } from '@/types';

const props = defineProps<ManualIntakePageProps>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard Internal', href: '/back-office/dashboard' },
            {
                title: 'Penerimaan Surat',
                href: '/back-office/intake/submissions',
            },
            {
                title: 'Catat Surat Manual',
                href: '/back-office/intake/manual/create',
            },
        ],
    },
});

const previewMode = computed(() => props.preview === true);
const routes = computed(() =>
    previewMode.value ? manualIntakePreviewRoutes : props.routes,
);
</script>

<template>
    <Head title="Catat Surat Masuk Manual" />

    <main class="flex flex-1 flex-col gap-5 p-4 sm:p-6 lg:p-8">
        <ManualIntakeHeader
            :register-url="
                routes?.incoming_register ?? '/back-office/incoming-letters'
            "
            :preview="previewMode"
            :mode="props.mode"
        />
        <ManualIntakeForm
            :routes="routes"
            :preview="previewMode"
            :mode="props.mode"
            :initial="props.initial"
        />
    </main>
</template>
