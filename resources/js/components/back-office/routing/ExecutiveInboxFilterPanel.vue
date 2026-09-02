<script setup lang="ts">
import {
    Calendar,
    LayoutGrid,
    List,
    RotateCcw,
    Search,
    X,
} from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { ExecutiveInboxFilters } from '@/types';

defineProps<{
    filters: ExecutiveInboxFilters;
    viewMode?: 'cards' | 'table';
}>();

const emit = defineEmits<{
    change: [filters: Partial<ExecutiveInboxFilters>];
    reset: [];
    'toggle-view': [mode: 'cards' | 'table'];
}>();

const phasePills = [
    { value: '', label: 'Semua Fase' },
    { value: 'AWAITING_DECISION', label: 'Menunggu Disposisi' },
    { value: 'AWAITING_FORWARDING', label: 'Menunggu Penerusan' },
    { value: 'IN_PROGRESS', label: 'Sedang Ditangani' },
    { value: 'COMPLETED', label: 'Selesai' },
] as const;
</script>

<template>
    <section class="rounded-3xl border border-border/80 bg-card p-5 shadow-sm dark:border-border/60 dark:bg-slate-900/80">
        <!-- Main Filter Toolbar Grid -->
        <div class="flex flex-col gap-4">
            <!-- Top Row: Search Input, Quick Phase Tabs, and View Toggle -->
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <!-- Search Bar -->
                <div class="relative flex-1">
                    <Search
                        class="pointer-events-none absolute left-3.5 top-1/2 size-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        :model-value="filters.search"
                        class="h-11 rounded-2xl border-border/70 bg-background/80 pl-10 pr-9 text-xs placeholder:text-muted-foreground focus:border-indigo-600 focus:ring-1 focus:ring-indigo-600"
                        placeholder="Cari nomor agenda, perihal surat, nomor surat dinas, atau instansi pengirim..."
                        @update:model-value="emit('change', { search: String($event) })"
                    />
                    <button
                        v-if="filters.search"
                        type="button"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                        @click="emit('change', { search: '' })"
                    >
                        <X class="size-3.5" />
                    </button>
                </div>

                <!-- View Mode Switcher -->
                <div class="flex items-center gap-1.5 self-end rounded-2xl border border-border/70 bg-muted/50 p-1 lg:self-auto">
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition-all"
                        :class="[
                            viewMode === 'cards' || !viewMode
                                ? 'bg-background text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground',
                        ]"
                        @click="emit('toggle-view', 'cards')"
                    >
                        <LayoutGrid class="size-3.5" />
                        <span>Kartu Eksekutif</span>
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition-all"
                        :class="[
                            viewMode === 'table'
                                ? 'bg-background text-foreground shadow-xs'
                                : 'text-muted-foreground hover:text-foreground',
                        ]"
                        @click="emit('toggle-view', 'table')"
                    >
                        <List class="size-3.5" />
                        <span>Tabel Data</span>
                    </button>
                </div>
            </div>

            <!-- Bottom Row: Phase Filter Pills & Date Pickers -->
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-border/60 pt-3.5 dark:border-border/40">
                <!-- Phase Pills -->
                <div class="flex flex-wrap items-center gap-1.5">
                    <button
                        v-for="pill in phasePills"
                        :key="pill.value"
                        type="button"
                        class="rounded-xl px-3 py-1.5 font-mono text-[11px] font-bold transition-all"
                        :class="[
                            (filters.progress || '') === pill.value
                                ? 'bg-indigo-600 text-white shadow-xs'
                                : 'bg-muted/60 text-muted-foreground hover:bg-muted hover:text-foreground',
                        ]"
                        @click="emit('change', { progress: pill.value as ExecutiveInboxFilters['progress'] })"
                    >
                        {{ pill.label }}
                    </button>
                </div>

                <!-- Date Range & Reset Actions -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="flex items-center gap-1.5 rounded-2xl border border-border/70 bg-background/80 px-2.5 py-1 text-xs">
                        <Calendar class="size-3.5 text-muted-foreground" />
                        <span class="font-mono text-[10px] text-muted-foreground uppercase">Dari:</span>
                        <input
                            type="date"
                            :value="filters.date_from"
                            class="bg-transparent text-xs text-foreground focus:outline-none"
                            @input="emit('change', { date_from: ($event.target as HTMLInputElement).value })"
                        />
                    </div>

                    <div class="flex items-center gap-1.5 rounded-2xl border border-border/70 bg-background/80 px-2.5 py-1 text-xs">
                        <Calendar class="size-3.5 text-muted-foreground" />
                        <span class="font-mono text-[10px] text-muted-foreground uppercase">Sampai:</span>
                        <input
                            type="date"
                            :value="filters.date_to"
                            class="bg-transparent text-xs text-foreground focus:outline-none"
                            @input="emit('change', { date_to: ($event.target as HTMLInputElement).value })"
                        />
                    </div>

                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        class="h-8 rounded-xl px-2.5 text-xs font-semibold text-muted-foreground hover:text-foreground"
                        @click="emit('reset')"
                    >
                        <RotateCcw class="mr-1 size-3" />
                        <span>Reset</span>
                    </Button>
                </div>
            </div>
        </div>
    </section>
</template>
