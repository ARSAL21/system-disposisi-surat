<script setup lang="ts">
import { Search, X } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { DispositionInboxFilters } from '@/types';

defineProps<{ filters: DispositionInboxFilters }>();
const emit = defineEmits<{
    change: [patch: Partial<DispositionInboxFilters>];
    reset: [];
}>();

function updateStatus(value: unknown): void {
    emit('change', {
        status: value === 'ALL' ? '' : (String(value) as DispositionInboxFilters['status']),
    });
}
</script>

<template>
    <section class="rounded-2xl border bg-card p-4 shadow-xs">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-[1.4fr_0.8fr_0.8fr_0.8fr_auto] xl:items-end">
            <div class="space-y-2">
                <Label for="disposition-search">Cari surat</Label>
                <div class="relative">
                    <Search
                        class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        aria-hidden="true"
                    />
                    <Input
                        id="disposition-search"
                        :model-value="filters.search"
                        class="min-h-11 pl-9"
                        placeholder="Nomor agenda, perihal, atau pengirim..."
                        @update:model-value="
                            emit('change', { search: String($event) })
                        "
                    />
                </div>
            </div>

            <div class="space-y-2">
                <Label for="disposition-status">Status cabang</Label>
                <Select
                    :model-value="filters.status || 'ALL'"
                    @update:model-value="updateStatus"
                >
                    <SelectTrigger id="disposition-status" class="min-h-11 w-full">
                        <SelectValue placeholder="Semua status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="ALL">Semua status</SelectItem>
                        <SelectItem value="PENDING">Menunggu</SelectItem>
                        <SelectItem value="IN_PROGRESS">Ditangani</SelectItem>
                        <SelectItem value="COMPLETED">Selesai</SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="space-y-2">
                <Label for="disposition-date-from">Diterima sejak</Label>
                <Input
                    id="disposition-date-from"
                    type="date"
                    :model-value="filters.date_from"
                    class="min-h-11"
                    @update:model-value="
                        emit('change', { date_from: String($event) })
                    "
                />
            </div>

            <div class="space-y-2">
                <Label for="disposition-date-to">Sampai tanggal</Label>
                <Input
                    id="disposition-date-to"
                    type="date"
                    :model-value="filters.date_to"
                    class="min-h-11"
                    @update:model-value="
                        emit('change', { date_to: String($event) })
                    "
                />
            </div>

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
