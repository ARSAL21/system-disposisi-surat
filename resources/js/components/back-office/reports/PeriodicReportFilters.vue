<script setup lang="ts">
import { CalendarRange, RotateCcw, SlidersHorizontal } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { PeriodicReportFilters, ReportEventBasis } from '@/types';

defineProps<{ filters: PeriodicReportFilters }>();

const emit = defineEmits<{
    change: [patch: Partial<PeriodicReportFilters>];
    reset: [];
}>();

const eventOptions: Array<{ value: ReportEventBasis; label: string }> = [
    { value: 'RECEIVED', label: 'Diterima' },
    { value: 'PROCESSING_STARTED', label: 'Mulai diproses' },
    { value: 'COMPLETED', label: 'Selesai' },
];
</script>

<template>
    <section
        class="rounded-2xl border bg-background p-3 shadow-sm"
        aria-label="Filter peta laporan"
    >
        <div class="flex flex-col gap-3 xl:flex-row xl:items-center">
            <div
                class="flex min-w-0 flex-col gap-2 sm:flex-row sm:items-center"
            >
                <span
                    class="inline-flex shrink-0 items-center gap-2 text-xs font-semibold text-muted-foreground"
                >
                    <CalendarRange class="size-4" /> Periode
                </span>
                <Input
                    type="date"
                    :model-value="filters.date_from"
                    class="min-h-10 sm:w-36"
                    aria-label="Tanggal awal laporan"
                    @update:model-value="
                        emit('change', { date_from: String($event) })
                    "
                />
                <span class="hidden text-muted-foreground sm:inline">—</span>
                <Input
                    type="date"
                    :model-value="filters.date_to"
                    class="min-h-10 sm:w-36"
                    aria-label="Tanggal akhir laporan"
                    @update:model-value="
                        emit('change', { date_to: String($event) })
                    "
                />
            </div>

            <div
                class="flex min-w-0 items-center gap-1 overflow-x-auto xl:ml-auto"
            >
                <SlidersHorizontal
                    class="mr-1 size-4 shrink-0 text-muted-foreground"
                />
                <button
                    v-for="option in eventOptions"
                    :key="option.value"
                    type="button"
                    class="min-h-10 shrink-0 rounded-lg px-3 text-xs font-semibold transition-colors focus-visible:ring-3 focus-visible:ring-ring/50 focus-visible:outline-none"
                    :class="
                        filters.event === option.value
                            ? 'bg-slate-950 text-white dark:bg-white dark:text-slate-950'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                    "
                    :aria-pressed="filters.event === option.value"
                    @click="emit('change', { event: option.value })"
                >
                    {{ option.label }}
                </button>
            </div>

            <div class="grid grid-cols-2 gap-2 sm:flex">
                <label class="sr-only" for="report-source">Sumber surat</label>
                <select
                    id="report-source"
                    :value="filters.source"
                    class="min-h-10 rounded-lg border bg-background px-3 text-xs outline-none focus-visible:ring-3 focus-visible:ring-ring/50"
                    @change="
                        emit('change', {
                            source: ($event.target as HTMLSelectElement)
                                .value as PeriodicReportFilters['source'],
                        })
                    "
                >
                    <option value="">Semua sumber</option>
                    <option value="ONLINE">Online</option>
                    <option value="MANUAL">Manual</option>
                </select>

                <label class="sr-only" for="report-status">Status surat</label>
                <select
                    id="report-status"
                    :value="filters.status"
                    class="min-h-10 rounded-lg border bg-background px-3 text-xs outline-none focus-visible:ring-3 focus-visible:ring-ring/50"
                    @change="
                        emit('change', {
                            status: ($event.target as HTMLSelectElement)
                                .value as PeriodicReportFilters['status'],
                        })
                    "
                >
                    <option value="">Semua status</option>
                    <option value="REGISTERED">Teregistrasi</option>
                    <option value="ROUTED">Diarahkan</option>
                    <option value="IN_PROGRESS">Diproses</option>
                    <option value="COMPLETED">Selesai</option>
                </select>
            </div>

            <Button
                variant="ghost"
                size="icon"
                class="size-10 shrink-0"
                aria-label="Atur ulang filter"
                @click="emit('reset')"
            >
                <RotateCcw class="size-4" />
            </Button>
        </div>
    </section>
</template>
