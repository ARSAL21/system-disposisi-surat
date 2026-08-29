<script setup lang="ts">
import { Search, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type {
    OrganizationalUnitOption,
    OrganizationStructureFilters,
    PositionLevel,
} from '@/types';

defineProps<{
    filters: OrganizationStructureFilters;
    levels: PositionLevel[];
    units: OrganizationalUnitOption[];
    processing: boolean;
}>();
const emit = defineEmits<{
    apply: [filters: Partial<OrganizationStructureFilters>];
    reset: [];
}>();
</script>

<template>
    <form
        class="grid gap-3 rounded-2xl border bg-card p-4 lg:grid-cols-[minmax(14rem,1fr)_auto_auto_auto]"
        @submit.prevent="emit('apply', {})"
    >
        <label class="relative">
            <span class="sr-only">Cari data organisasi</span>
            <Search
                class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                aria-hidden="true"
            />
            <Input
                :model-value="filters.search"
                class="min-h-11 pl-9"
                placeholder="Cari nama atau kode..."
                @update:model-value="emit('apply', { search: String($event) })"
            />
        </label>
        <select
            :value="filters.status"
            class="min-h-11 rounded-xl border bg-background px-3 text-sm"
            aria-label="Filter status"
            @change="
                emit('apply', {
                    status: ($event.target as HTMLSelectElement)
                        .value as OrganizationStructureFilters['status'],
                })
            "
        >
            <option value="all">Semua status</option>
            <option value="active">Aktif</option>
            <option value="inactive">Nonaktif</option>
        </select>
        <select
            v-if="filters.section === 'positions'"
            :value="filters.position_level_id ?? ''"
            class="min-h-11 rounded-xl border bg-background px-3 text-sm"
            aria-label="Filter level jabatan"
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
            v-if="filters.section === 'positions'"
            :value="filters.organizational_unit_id ?? ''"
            class="min-h-11 rounded-xl border bg-background px-3 text-sm"
            aria-label="Filter unit organisasi"
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
            type="button"
            variant="ghost"
            class="min-h-11"
            :disabled="processing"
            @click="emit('reset')"
        >
            <X class="size-4" aria-hidden="true" /> Reset
        </Button>
    </form>
</template>
