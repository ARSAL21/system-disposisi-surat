<script setup lang="ts">
import { ArrowUpRight, Info, RotateCcw } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

defineProps<{
    note: string;
    checklistComplete: boolean;
    canScreen: boolean;
    processing: boolean;
    internalRevision?: boolean;
    noteError?: string;
    checklistError?: string;
}>();
defineEmits<{
    'update:note': [value: string];
    requestRevision: [];
    markReady: [];
}>();
</script>

<template>
    <Card class="shadow-sm">
        <CardHeader>
            <CardTitle>Hasil pemeriksaan awal</CardTitle>
        </CardHeader>
        <CardContent>
            <label for="screening-note" class="text-sm font-semibold"
                >Catatan petugas</label
            >
            <textarea
                id="screening-note"
                :value="note"
                rows="4"
                class="mt-2 w-full resize-y rounded-xl border bg-background px-3 py-3 text-sm leading-6 transition-shadow outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                placeholder="Jelaskan kekurangan atau catatan pengantar untuk Kabag Umum..."
                @input="
                    $emit(
                        'update:note',
                        ($event.target as HTMLTextAreaElement).value,
                    )
                "
            />
            <InputError :message="noteError" class="mt-2" />
            <InputError :message="checklistError" class="mt-2" />

            <div
                :class="[
                    'mt-4 grid gap-2',
                    internalRevision ? '' : 'sm:grid-cols-2',
                ]"
            >
                <Button
                    v-if="!internalRevision"
                    type="button"
                    variant="outline"
                    class="min-h-11"
                    :disabled="
                        !canScreen || processing || note.trim().length === 0
                    "
                    @click="$emit('requestRevision')"
                >
                    <RotateCcw class="size-4" aria-hidden="true" />
                    Minta pengirim memperbaiki
                </Button>
                <Button
                    type="button"
                    class="min-h-11"
                    :disabled="!canScreen || processing || !checklistComplete"
                    @click="$emit('markReady')"
                >
                    {{
                        internalRevision
                            ? 'Ajukan kembali ke Kabag Umum'
                            : 'Ajukan ke Kabag Umum'
                    }}
                    <ArrowUpRight class="size-4" aria-hidden="true" />
                </Button>
            </div>
            <p
                class="mt-3 flex gap-2 text-xs leading-5 text-muted-foreground"
                aria-live="polite"
            >
                <Info class="mt-0.5 size-3.5 shrink-0" aria-hidden="true" />
                <span v-if="checklistComplete">
                    Seluruh pemeriksaan sudah lengkap dan siap diajukan kepada
                    Kepala Bagian Umum.
                </span>
                <span v-else>
                    Lengkapi daftar pemeriksaan sebelum pengajuan surat dapat
                    diajukan kepada Kabag Umum.
                </span>
            </p>
        </CardContent>
    </Card>
</template>
