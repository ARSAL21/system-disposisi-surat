<script setup lang="ts">
import { Search, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { DocumentArchiveFilters, DocumentArchiveStatus } from '@/types';

defineProps<{ filters: DocumentArchiveFilters }>();
const emit = defineEmits<{
    change: [filters: Partial<DocumentArchiveFilters>];
    reset: [];
}>();

function updateStatus(value: unknown): void {
    emit('change', {
        status: value === 'all' ? '' : (String(value) as DocumentArchiveStatus),
    });
}
</script>

<template>
    <section class="rounded-2xl border bg-card p-4 shadow-xs">
        <div
            class="grid gap-4 xl:grid-cols-[minmax(16rem,1fr)_13rem_11rem_11rem_auto] xl:items-end"
        >
            <label class="relative">
                <span
                    class="mb-2 block text-xs font-semibold text-muted-foreground"
                >
                    Cari arsip
                </span>
                <Search
                    class="pointer-events-none absolute bottom-3.5 left-3 size-4 text-muted-foreground"
                    aria-hidden="true"
                />
                <Input
                    :model-value="filters.search"
                    class="min-h-11 pl-9"
                    placeholder="Nomor agenda, perihal, instansi, atau berkas..."
                    @update:model-value="
                        emit('change', { search: String($event) })
                    "
                />
            </label>

            <div>
                <span
                    class="mb-2 block text-xs font-semibold text-muted-foreground"
                >
                    Status surat
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
                        <SelectItem value="ROUTED">Diteruskan</SelectItem>
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
                class="min-h-11"
                @click="emit('reset')"
            >
                <X class="size-4" aria-hidden="true" />
                Atur ulang
            </Button>
        </div>
    </section>
</template>
