<script setup lang="ts">
import { Search, SlidersHorizontal, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    IncomingRegisterFilters,
    IncomingRegisterSource,
    IncomingRegisterStatus,
} from '@/types';

defineProps<{ filters: IncomingRegisterFilters }>();
const emit = defineEmits<{
    change: [filters: Partial<IncomingRegisterFilters>];
    reset: [];
}>();

function updateSource(value: unknown): void {
    emit('change', {
        source:
            value === 'all' ? '' : (String(value) as IncomingRegisterSource),
    });
}

function updateStatus(value: unknown): void {
    emit('change', {
        status:
            value === 'all' ? '' : (String(value) as IncomingRegisterStatus),
    });
}
</script>

<template>
    <section class="rounded-2xl border bg-card p-4 shadow-xs">
        <div class="mb-4 flex items-center gap-2 text-sm font-semibold">
            <SlidersHorizontal
                class="size-4 text-amber-700 dark:text-amber-300"
                aria-hidden="true"
            />
            Saring catatan agenda
        </div>
        <div
            class="grid gap-4 xl:grid-cols-[minmax(17rem,1fr)_10rem_12rem_7rem_11rem_11rem_auto] xl:items-end"
        >
            <label class="relative">
                <span
                    class="mb-2 block text-xs font-semibold text-muted-foreground"
                >
                    Cari surat
                </span>
                <Search
                    class="pointer-events-none absolute bottom-3.5 left-3 size-4 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    :model-value="filters.search"
                    class="min-h-11 pl-9"
                    placeholder="Agenda, nomor surat, instansi, atau perihal..."
                    @update:model-value="
                        emit('change', { search: String($event) })
                    "
                />
            </label>

            <div>
                <span
                    class="mb-2 block text-xs font-semibold text-muted-foreground"
                >
                    Sumber
                </span>
                <Select
                    :model-value="filters.source || 'all'"
                    @update:model-value="updateSource"
                >
                    <SelectTrigger class="min-h-11 w-full">
                        <SelectValue placeholder="Semua sumber" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua sumber</SelectItem>
                        <SelectItem value="ONLINE">Online</SelectItem>
                        <SelectItem value="MANUAL">Surat fisik</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div>
                <span
                    class="mb-2 block text-xs font-semibold text-muted-foreground"
                >
                    Status
                </span>
                <Select
                    :model-value="filters.status || 'all'"
                    @update:model-value="updateStatus"
                >
                    <SelectTrigger class="min-h-11 w-full">
                        <SelectValue placeholder="Semua status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua status</SelectItem>
                        <SelectItem value="REGISTERED">Teregistrasi</SelectItem>
                        <SelectItem value="ROUTED">Sudah diarahkan</SelectItem>
                        <SelectItem value="IN_PROGRESS"
                            >Dalam disposisi</SelectItem
                        >
                        <SelectItem value="COMPLETED">Selesai</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <label>
                <span
                    class="mb-2 block text-xs font-semibold text-muted-foreground"
                >
                    Tahun agenda
                </span>
                <Input
                    type="number"
                    inputmode="numeric"
                    min="2000"
                    max="2100"
                    :model-value="filters.year"
                    class="min-h-11"
                    placeholder="Semua"
                    @update:model-value="
                        emit('change', { year: String($event) })
                    "
                />
            </label>

            <label>
                <span
                    class="mb-2 block text-xs font-semibold text-muted-foreground"
                >
                    Diterima sejak
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

            <Button
                type="button"
                variant="outline"
                class="min-h-11 cursor-pointer"
                @click="emit('reset')"
            >
                <X class="size-4" aria-hidden="true" />
                Reset
            </Button>
        </div>
    </section>
</template>
