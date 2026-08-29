<script setup lang="ts">
import { Search, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type {
    OrganizationOption,
    PositionAssignmentFilters,
    PositionLevel,
} from '@/types';

defineProps<{
    filters: PositionAssignmentFilters;
    levels: PositionLevel[];
    units: OrganizationOption[];
    processing: boolean;
}>();
const emit = defineEmits<{
    apply: [filters: Partial<PositionAssignmentFilters>];
    reset: [];
}>();
</script>

<template>
    <div
        class="grid gap-3 rounded-2xl border bg-card p-4 xl:grid-cols-[minmax(14rem,1fr)_auto_auto_auto_auto]"
    >
        <label class="relative"
            ><span class="sr-only">Cari jabatan atau pejabat</span
            ><Search
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                aria-hidden="true" /><Input
                :model-value="filters.search"
                class="min-h-11 pl-9"
                placeholder="Cari jabatan, kode, atau pejabat..."
                @update:model-value="emit('apply', { search: String($event) })"
        /></label>
        <select
            :value="filters.status"
            class="min-h-11 rounded-xl border bg-background px-3 text-sm"
            aria-label="Status keterisian"
            @change="
                emit('apply', {
                    status: ($event.target as HTMLSelectElement)
                        .value as PositionAssignmentFilters['status'],
                })
            "
        >
            <option value="all">Semua status</option>
            <option value="occupied">Terisi</option>
            <option value="vacant">Lowong</option>
            <option value="inactive">Jabatan nonaktif</option>
        </select>
        <select
            :value="filters.position_level_id ?? ''"
            class="min-h-11 rounded-xl border bg-background px-3 text-sm"
            aria-label="Level jabatan"
            @change="
                emit('apply', {
                    position_level_id:
                        Number(($event.target as HTMLSelectElement).value) ||
                        null,
                })
            "
        >
            <option value="">Semua level</option>
            <option v-for="level in levels" :key="level.id" :value="level.id">
                {{ level.name }}
            </option>
        </select>
        <select
            :value="filters.organizational_unit_id ?? ''"
            class="min-h-11 rounded-xl border bg-background px-3 text-sm"
            aria-label="Unit organisasi"
            @change="
                emit('apply', {
                    organizational_unit_id:
                        Number(($event.target as HTMLSelectElement).value) ||
                        null,
                })
            "
        >
            <option value="">Semua unit</option>
            <option v-for="unit in units" :key="unit.id" :value="unit.id">
                {{ unit.name }}
            </option>
        </select>
        <Button
            variant="ghost"
            class="min-h-11"
            :disabled="processing"
            @click="emit('reset')"
            ><X class="size-4" aria-hidden="true" /> Reset</Button
        >
    </div>
</template>
