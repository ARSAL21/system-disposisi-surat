<script setup lang="ts">
import { Building2, MoreHorizontal, Pencil, Plus, Power } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { OrganizationalUnit, Paginated } from '@/types';

defineProps<{ units: Paginated<OrganizationalUnit>; canMutate: boolean }>();
const emit = defineEmits<{
    create: [];
    edit: [unit: OrganizationalUnit];
    status: [unit: OrganizationalUnit];
}>();
</script>

<template>
    <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
        <header
            class="flex flex-col gap-3 border-b p-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div>
                <h2 class="font-semibold">Direktori unit organisasi</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Relasi induk dan status operasional setiap unit.
                </p>
            </div>
            <Button
                class="min-h-11 bg-blue-700 hover:bg-blue-800"
                :disabled="!canMutate"
                @click="emit('create')"
            >
                <Plus class="size-4" aria-hidden="true" /> Tambah unit
            </Button>
        </header>
        <div v-if="units.data.length" class="divide-y">
            <article
                v-for="unit in units.data"
                :key="unit.id"
                class="grid gap-4 p-4 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center"
            >
                <div class="flex min-w-0 gap-3">
                    <span
                        class="grid size-11 shrink-0 place-items-center rounded-xl bg-blue-50 text-blue-700 dark:bg-blue-950/50 dark:text-blue-300"
                        ><Building2 class="size-5" aria-hidden="true"
                    /></span>
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="truncate font-medium">
                                {{ unit.name }}
                            </h3>
                            <Badge
                                :variant="
                                    unit.is_active ? 'default' : 'secondary'
                                "
                                >{{
                                    unit.is_active ? 'Aktif' : 'Nonaktif'
                                }}</Badge
                            >
                        </div>
                        <p class="mt-1 truncate text-sm text-muted-foreground">
                            {{ unit.code || 'Tanpa kode' }} · Induk:
                            {{ unit.parent?.name || 'Root' }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ unit.children_count }} unit turunan ·
                            {{ unit.positions_count }} jabatan
                        </p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        class="min-h-10"
                        :disabled="!canMutate || !unit.capabilities.update"
                        @click="emit('edit', unit)"
                        ><Pencil class="size-4" aria-hidden="true" />
                        Ubah</Button
                    >
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-10"
                        :disabled="
                            !canMutate || !unit.capabilities.change_status
                        "
                        :aria-label="`${unit.is_active ? 'Nonaktifkan' : 'Aktifkan'} ${unit.name}`"
                        @click="emit('status', unit)"
                    >
                        <Power
                            class="size-4"
                            aria-hidden="true"
                        /><MoreHorizontal class="sr-only" />
                    </Button>
                </div>
            </article>
        </div>
        <div v-else class="grid min-h-56 place-items-center p-8 text-center">
            <div>
                <Building2
                    class="mx-auto size-8 text-muted-foreground"
                    aria-hidden="true"
                />
                <p class="mt-3 font-medium">Belum ada unit yang cocok</p>
                <p class="mt-1 text-sm text-muted-foreground">
                    Sesuaikan filter atau buat unit baru.
                </p>
            </div>
        </div>
        <slot name="pagination" />
    </section>
</template>
