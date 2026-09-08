<script setup lang="ts">
import { BarChart3, ChevronDown, Download, Files, Network } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { PeriodicReportFilters, PeriodicReportScope } from '@/types';

defineProps<{
    mode: 'AGGREGATE' | 'LETTER';
    scope: PeriodicReportScope;
    filters: PeriodicReportFilters;
    agendaNumber?: string | null;
    canExport: boolean;
}>();

const emit = defineEmits<{
    openSummary: [];
    openLetters: [];
    export: [kind: 'summary' | 'letters'];
}>();
</script>

<template>
    <header
        class="flex flex-col gap-4 rounded-2xl border bg-background p-4 shadow-sm lg:flex-row lg:items-center lg:justify-between"
    >
        <div class="min-w-0">
            <div
                class="flex flex-wrap items-center gap-2 text-xs font-semibold text-indigo-600 dark:text-indigo-300"
            >
                <Network class="size-4" />
                <span>{{ scope.label }}</span>
                <span class="text-muted-foreground">·</span>
                <span class="text-muted-foreground tabular-nums">
                    {{ filters.date_from }} — {{ filters.date_to }}
                </span>
            </div>
            <h1 class="mt-1 text-xl font-semibold tracking-tight sm:text-2xl">
                {{
                    mode === 'AGGREGATE'
                        ? 'Peta penyelesaian surat'
                        : `Jejak keputusan ${agendaNumber ?? ''}`
                }}
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                {{
                    mode === 'AGGREGATE'
                        ? 'Lihat posisi yang aktif, tertahan, dan selesai dalam satu alur.'
                        : 'Ikuti perjalanan surat dari pimpinan sampai setiap cabang pelaksana.'
                }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <Button
                variant="outline"
                class="min-h-10"
                @click="emit('openSummary')"
            >
                <BarChart3 class="size-4" /> Ringkasan periode
            </Button>
            <Button class="min-h-10" @click="emit('openLetters')">
                <Files class="size-4" /> Telusuri surat
            </Button>
            <DropdownMenu v-if="canExport">
                <DropdownMenuTrigger as-child>
                    <Button
                        variant="outline"
                        size="icon"
                        class="size-10"
                        aria-label="Buka pilihan ekspor"
                    >
                        <Download class="size-4" />
                        <ChevronDown class="size-3" />
                    </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end" class="w-52">
                    <DropdownMenuItem @select="emit('export', 'summary')">
                        Ekspor ringkasan
                    </DropdownMenuItem>
                    <DropdownMenuItem @select="emit('export', 'letters')">
                        Ekspor daftar surat
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </div>
    </header>
</template>
