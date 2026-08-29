<script setup lang="ts">
import { Clock3, History, Search, X } from '@lucide/vue';
import type { LucideIcon } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { ApprovalFilters, ApprovalQueueTab } from '@/types';

defineProps<{ filters: ApprovalFilters }>();
const emit = defineEmits<{
    change: [filters: Partial<ApprovalFilters>];
    reset: [];
}>();

const tabs: Array<{
    value: ApprovalQueueTab;
    label: string;
    icon: LucideIcon;
}> = [
    { value: 'pending', label: 'Menunggu keputusan', icon: Clock3 },
    { value: 'history', label: 'Riwayat keputusan', icon: History },
];
</script>

<template>
    <section class="rounded-2xl border bg-card p-4 shadow-xs">
        <div
            class="grid gap-4 xl:grid-cols-[auto_minmax(15rem,1fr)_auto_auto_auto] xl:items-end"
        >
            <div>
                <span
                    class="mb-2 block text-xs font-semibold text-muted-foreground"
                >
                    Tampilan
                </span>
                <div
                    class="flex rounded-xl bg-muted p-1"
                    role="group"
                    aria-label="Pilih tampilan persetujuan"
                >
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        type="button"
                        :aria-pressed="filters.tab === tab.value"
                        :class="[
                            'flex min-h-9 items-center gap-2 rounded-lg px-3 text-xs font-semibold transition-colors',
                            filters.tab === tab.value
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground',
                        ]"
                        @click="emit('change', { tab: tab.value })"
                    >
                        <component
                            :is="tab.icon"
                            class="size-3.5"
                            aria-hidden="true"
                        />
                        {{ tab.label }}
                    </button>
                </div>
            </div>

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
                    placeholder="Perihal, instansi, atau nomor surat..."
                    @update:model-value="
                        emit('change', { search: String($event) })
                    "
                />
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
