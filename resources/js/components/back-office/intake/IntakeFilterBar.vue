<script setup lang="ts">
import { Search, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { IntakeFilters, IntakeSubmissionStatus } from '@/types';

defineProps<{ filters: IntakeFilters }>();
const emit = defineEmits<{
    change: [filters: Partial<IntakeFilters>];
    reset: [];
}>();

const statuses: Array<{ value: IntakeSubmissionStatus; label: string }> = [
    { value: 'SUBMITTED', label: 'Menunggu pemeriksaan awal' },
    { value: 'REVISION_REQUIRED', label: 'Perlu perbaikan pengirim' },
    { value: 'READY_FOR_APPROVAL', label: 'Menunggu Kabag Umum' },
    {
        value: 'INTERNAL_REVISION_REQUIRED',
        label: 'Dikembalikan ke petugas',
    },
    { value: 'REGISTERED', label: 'Terdaftar resmi' },
    { value: 'REJECTED', label: 'Ditolak administratif' },
];
</script>

<template>
    <form
        class="grid gap-4 rounded-2xl border bg-card p-4 shadow-xs lg:grid-cols-[minmax(16rem,1fr)_auto_auto_auto_auto_auto]"
        @submit.prevent
    >
        <label class="relative lg:self-end">
            <span
                class="mb-2 block text-xs font-semibold text-muted-foreground"
            >
                Cari pengajuan surat
            </span>
            <Search
                class="pointer-events-none absolute bottom-3.5 left-3 size-4 text-muted-foreground"
                aria-hidden="true"
            />
            <Input
                :model-value="filters.search"
                class="min-h-11 pl-9"
                placeholder="Perihal, pengirim, atau nomor surat..."
                @update:model-value="emit('change', { search: String($event) })"
            />
        </label>

        <label>
            <span
                class="mb-2 block text-xs font-semibold text-muted-foreground"
            >
                Sumber
            </span>
            <select
                :value="filters.source"
                class="min-h-11 w-full rounded-xl border bg-background px-3 text-sm"
                @change="
                    emit('change', {
                        source: ($event.target as HTMLSelectElement)
                            .value as IntakeFilters['source'],
                    })
                "
            >
                <option value="all">Semua sumber</option>
                <option value="ONLINE">Portal publik</option>
                <option value="MANUAL">Dicatat petugas</option>
            </select>
        </label>

        <label>
            <span
                class="mb-2 block text-xs font-semibold text-muted-foreground"
            >
                Status
            </span>
            <select
                :value="filters.status"
                class="min-h-11 w-full rounded-xl border bg-background px-3 text-sm"
                @change="
                    emit('change', {
                        status: ($event.target as HTMLSelectElement)
                            .value as IntakeFilters['status'],
                    })
                "
            >
                <option value="action_required">Perlu tindakan petugas</option>
                <option value="all">Semua status</option>
                <option
                    v-for="status in statuses"
                    :key="status.value"
                    :value="status.value"
                >
                    {{ status.label }}
                </option>
            </select>
        </label>

        <label>
            <span
                class="mb-2 block text-xs font-semibold text-muted-foreground"
            >
                Dari tanggal
            </span>
            <Input
                type="date"
                :model-value="filters.date_from"
                class="min-h-11"
                @update:model-value="
                    emit('change', { date_from: String($event) })
                "
            />
        </label>

        <label>
            <span
                class="mb-2 block text-xs font-semibold text-muted-foreground"
            >
                Sampai tanggal
            </span>
            <Input
                type="date"
                :model-value="filters.date_to"
                class="min-h-11"
                @update:model-value="
                    emit('change', { date_to: String($event) })
                "
            />
        </label>

        <div class="flex items-end">
            <Button
                type="button"
                variant="outline"
                class="min-h-11 w-full"
                aria-label="Atur ulang penyaring antrean"
                @click="emit('reset')"
            >
                <X class="size-4" aria-hidden="true" />
                Atur ulang
            </Button>
        </div>
    </form>
</template>
