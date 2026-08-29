<script setup lang="ts">
import { BadgeCheck, CircleX, Info, RotateCcw } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

defineProps<{ canDecide: boolean; hasDocument: boolean }>();
defineEmits<{
    returnToStaff: [];
    reject: [];
    register: [];
}>();
</script>

<template>
    <Card class="border-blue-200 shadow-sm dark:border-blue-900">
        <CardHeader>
            <CardTitle>Keputusan administratif</CardTitle>
            <p class="mt-1 text-sm leading-6 text-muted-foreground">
                Pilih keputusan setelah membaca data, dokumen, dan hasil
                pemeriksaan petugas.
            </p>
        </CardHeader>
        <CardContent>
            <div class="grid gap-3 lg:grid-cols-3">
                <Button
                    type="button"
                    variant="outline"
                    class="min-h-12 border-amber-300 text-amber-800 hover:bg-amber-50 dark:border-amber-800 dark:text-amber-300 dark:hover:bg-amber-950/30"
                    :disabled="!canDecide"
                    @click="$emit('returnToStaff')"
                >
                    <RotateCcw class="size-4" aria-hidden="true" />
                    Kembalikan ke petugas
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    class="min-h-12 border-rose-300 text-rose-700 hover:bg-rose-50 dark:border-rose-900 dark:text-rose-300 dark:hover:bg-rose-950/30"
                    :disabled="!canDecide"
                    @click="$emit('reject')"
                >
                    <CircleX class="size-4" aria-hidden="true" />
                    Tolak surat
                </Button>
                <Button
                    type="button"
                    class="min-h-12 bg-gradient-to-r from-blue-700 to-violet-700 text-white shadow-sm hover:from-blue-800 hover:to-violet-800"
                    :disabled="!canDecide || !hasDocument"
                    @click="$emit('register')"
                >
                    <BadgeCheck class="size-4" aria-hidden="true" />
                    Registrasikan surat
                </Button>
            </div>
            <p class="mt-4 flex gap-2 text-xs leading-5 text-muted-foreground">
                <Info class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
                Penolakan bersifat final. Registrasi membuat surat masuk resmi
                dan menyimpan versi awal dokumen.
            </p>
        </CardContent>
    </Card>
</template>
