<script setup lang="ts">
import { ListChecks, Plus, ShieldCheck } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';

defineProps<{
    canMutate: boolean;
    preview?: boolean;
}>();
defineEmits<{ create: [] }>();
</script>

<template>
    <header class="rounded-3xl border bg-card p-5 shadow-sm sm:p-7">
        <div
            class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between"
        >
            <div class="max-w-3xl">
                <div class="flex flex-wrap items-center gap-2">
                    <span
                        class="flex size-11 items-center justify-center rounded-2xl bg-blue-700 text-white shadow-sm"
                    >
                        <ListChecks class="size-5" aria-hidden="true" />
                    </span>
                    <Badge variant="outline">M6 · Konfigurasi workflow</Badge>
                    <Badge v-if="preview" variant="secondary">
                        Pratinjau lokal
                    </Badge>
                </div>
                <p
                    class="mt-5 text-xs font-semibold tracking-[0.17em] text-blue-700 uppercase dark:text-blue-300"
                >
                    Tata kelola instruksi
                </p>
                <h1
                    class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl"
                >
                    Katalog instruksi disposisi
                </h1>
                <p
                    class="mt-3 max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base"
                >
                    Kelola label yang dapat dipilih pimpinan tanpa mengubah
                    rekaman instruksi pada disposisi yang sudah dibuat.
                </p>
            </div>

            <div class="grid max-w-md gap-3">
                <div
                    class="flex items-start gap-3 rounded-2xl border bg-muted/35 p-4 text-sm"
                >
                    <ShieldCheck
                        class="mt-0.5 size-5 shrink-0 text-emerald-700 dark:text-emerald-300"
                        aria-hidden="true"
                    />
                    <p class="leading-6 text-muted-foreground">
                        Label dapat dinonaktifkan, bukan dihapus, agar histori
                        disposisi tetap dapat dibaca secara utuh.
                    </p>
                </div>
                <Button
                    type="button"
                    class="min-h-11 bg-blue-700 hover:bg-blue-800"
                    :disabled="!canMutate"
                    @click="$emit('create')"
                >
                    <Plus class="size-4" aria-hidden="true" />
                    Tambah instruksi
                </Button>
            </div>
        </div>
    </header>
</template>
