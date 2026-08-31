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
import type { LetterRoutingFilters, LetterRoutingStatus } from '@/types';

defineProps<{ filters: LetterRoutingFilters }>();
const emit = defineEmits<{
    change: [filters: Partial<LetterRoutingFilters>];
    reset: [];
}>();

function updateStatus(value: unknown): void {
    emit('change', {
        status: value === 'all' ? '' : (String(value) as LetterRoutingStatus),
    });
}
</script>

<template>
    <section class="rounded-2xl border bg-card p-4 shadow-xs">
        <div
            class="grid gap-4 lg:grid-cols-[minmax(16rem,1fr)_15rem_auto] lg:items-end"
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
                    placeholder="Nomor agenda, perihal, atau instansi..."
                    @update:model-value="
                        emit('change', { search: String($event) })
                    "
                />
            </label>

            <div>
                <span
                    class="mb-2 block text-xs font-semibold text-muted-foreground"
                >
                    Tahap routing
                </span>
                <Select
                    :model-value="filters.status || 'all'"
                    @update:model-value="updateStatus"
                >
                    <SelectTrigger class="min-h-11 w-full">
                        <SelectValue placeholder="Semua tahap" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">Semua tahap</SelectItem>
                        <SelectItem value="REGISTERED">
                            Menunggu routing
                        </SelectItem>
                        <SelectItem value="ROUTED">
                            Sudah diarahkan
                        </SelectItem>
                    </SelectContent>
                </Select>
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
