<script setup lang="ts">
import { FileCheck2, FileClock, Files } from '@lucide/vue';
import DashboardStatCard from '@/components/public/dashboard/DashboardStatCard.vue';
import type { PublicDashboardSummary } from '@/types';

defineProps<{ summary: PublicDashboardSummary }>();
</script>

<template>
    <section aria-labelledby="submission-summary-heading">
        <h2 id="submission-summary-heading" class="sr-only">
            Ringkasan submission surat
        </h2>
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <DashboardStatCard
                label="Total surat"
                :value="summary.total"
                description="Seluruh submission yang pernah Anda buat."
                :icon="Files"
                tone="primary"
            />
            <DashboardStatCard
                label="Masih draft"
                :value="summary.draft"
                description="Masih dapat dilengkapi atau diperbarui."
                :icon="FileClock"
                tone="blue"
            />
            <DashboardStatCard
                label="Sudah dikirim"
                :value="summary.submitted"
                description="Telah masuk ke antrean pemeriksaan."
                :icon="FileCheck2"
                tone="purple"
            />
        </div>
    </section>
</template>
