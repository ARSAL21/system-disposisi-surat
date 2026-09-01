<script setup lang="ts">
import { ListChecks, Pencil, Power } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { DispositionInstructionLabel } from '@/types';

defineProps<{
    labels: DispositionInstructionLabel[];
    canMutate: boolean;
}>();
defineEmits<{
    edit: [label: DispositionInstructionLabel];
    status: [label: DispositionInstructionLabel];
    reset: [];
}>();
</script>

<template>
    <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
        <div
            v-if="labels.length === 0"
            class="flex min-h-64 flex-col items-center justify-center px-5 py-12 text-center"
        >
            <span
                class="flex size-12 items-center justify-center rounded-2xl bg-muted text-muted-foreground"
            >
                <ListChecks class="size-5" aria-hidden="true" />
            </span>
            <h2 class="mt-4 font-semibold">Instruksi tidak ditemukan</h2>
            <p class="mt-2 max-w-md text-sm leading-6 text-muted-foreground">
                Ubah kata pencarian atau tampilkan kembali seluruh status.
            </p>
            <Button
                type="button"
                variant="outline"
                class="mt-5 min-h-11"
                @click="$emit('reset')"
            >
                Tampilkan semua instruksi
            </Button>
        </div>

        <div v-else class="grid gap-3 p-3 sm:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="label in labels"
                :key="label.id"
                class="flex min-h-56 flex-col rounded-2xl border p-4 shadow-xs"
                :class="!label.is_active ? 'bg-muted/35 opacity-80' : 'bg-card'"
            >
                <div class="flex items-start justify-between gap-3">
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-blue-500/10 text-blue-700 dark:text-blue-300"
                    >
                        <ListChecks class="size-5" aria-hidden="true" />
                    </span>
                    <Badge
                        variant="outline"
                        :class="
                            label.is_active
                                ? 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/35 dark:text-emerald-300'
                                : 'border-slate-300 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300'
                        "
                    >
                        {{ label.is_active ? 'Aktif' : 'Nonaktif' }}
                    </Badge>
                </div>

                <div class="mt-4 flex-1">
                    <h2 class="font-semibold">{{ label.name }}</h2>
                    <p class="mt-1 font-mono text-xs text-muted-foreground">
                        {{ label.code }} · Urutan {{ label.sort_order }}
                    </p>
                    <p class="mt-3 text-sm leading-6 text-muted-foreground">
                        {{ label.description || 'Tanpa deskripsi tambahan.' }}
                    </p>
                </div>

                <div class="mt-5 grid grid-cols-2 gap-2 border-t pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        class="min-h-11"
                        :disabled="!canMutate"
                        @click="$emit('edit', label)"
                    >
                        <Pencil class="size-4" aria-hidden="true" />
                        Ubah
                    </Button>
                    <Button
                        type="button"
                        variant="outline"
                        class="min-h-11"
                        :disabled="!canMutate"
                        @click="$emit('status', label)"
                    >
                        <Power class="size-4" aria-hidden="true" />
                        {{ label.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    </Button>
                </div>
            </article>
        </div>
    </section>
</template>
