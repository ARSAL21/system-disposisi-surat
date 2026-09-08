<script setup lang="ts">
import { RotateCcw, Search } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { OutgoingLetterFilters } from '@/types';

defineProps<{ filters: OutgoingLetterFilters }>();
const emit = defineEmits<{
    change: [patch: Partial<OutgoingLetterFilters>];
    reset: [];
}>();
</script>

<template>
    <section class="rounded-2xl border bg-card p-4 shadow-xs" aria-label="Filter surat keluar">
        <div class="grid gap-3 md:grid-cols-[minmax(15rem,1fr)_repeat(3,minmax(9rem,0.42fr))_auto]">
            <label class="relative">
                <span class="sr-only">Cari surat keluar</span>
                <Search class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
                <Input :model-value="filters.search" class="h-11 pl-9" placeholder="Cari nomor, agenda, instansi, atau perihal..." @update:model-value="emit('change', { search: String($event) })" />
            </label>
            <label>
                <span class="sr-only">Filter status</span>
                <select :value="filters.status" class="h-11 w-full rounded-xl border border-input bg-background px-3 text-sm" @change="emit('change', { status: ($event.target as HTMLSelectElement).value as OutgoingLetterFilters['status'] })">
                    <option value="">Semua status</option>
                    <option value="AUTHORIZED">Menunggu nomor</option>
                    <option value="NUMBER_ASSIGNED">Menunggu tanda tangan</option>
                    <option value="SIGNED_DOCUMENT_UPLOADED">Menunggu verifikasi</option>
                    <option value="ADMIN_VERIFIED">Siap dikirim</option>
                    <option value="DELIVERED">Sudah dikirim</option>
                    <option value="WITHDRAWN">Ditarik</option>
                </select>
            </label>
            <label>
                <span class="sr-only">Filter sumber</span>
                <select :value="filters.source" class="h-11 w-full rounded-xl border border-input bg-background px-3 text-sm" @change="emit('change', { source: ($event.target as HTMLSelectElement).value as OutgoingLetterFilters['source'] })">
                    <option value="">Semua sumber</option>
                    <option value="ONLINE">Online</option>
                    <option value="MANUAL">Manual</option>
                </select>
            </label>
            <label>
                <span class="sr-only">Filter tahun</span>
                <Input :model-value="filters.year" inputmode="numeric" maxlength="4" class="h-11" placeholder="Tahun" @update:model-value="emit('change', { year: String($event) })" />
            </label>
            <Button type="button" variant="outline" class="h-11 rounded-xl" @click="emit('reset')">
                <RotateCcw class="size-4" />
                <span class="md:sr-only xl:not-sr-only">Reset</span>
            </Button>
        </div>
    </section>
</template>
