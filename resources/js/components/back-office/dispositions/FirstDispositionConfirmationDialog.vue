<script setup lang="ts">
import { Check, Info } from '@lucide/vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import type {
    DispositionInstructionLabelOption,
    DispositionPositionOption,
} from '@/types';

defineProps<{
    open: boolean;
    recipient: DispositionPositionOption | null;
    instructions: DispositionInstructionLabelOption[];
    note: string;
    processing?: boolean;
}>();
const emit = defineEmits<{
    'update:open': [open: boolean];
    confirm: [];
}>();
</script>

<template>
    <Dialog
        :open="open"
        @update:open="!processing ? emit('update:open', $event) : undefined"
    >
        <DialogContent class="max-h-[90dvh] overflow-y-auto sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>Kirim disposisi kepada Asisten?</DialogTitle>
                <DialogDescription class="leading-6">
                    Pastikan penerima dan instruksi sudah sesuai. Keputusan ini
                    akan menjadi bagian dari histori surat.
                </DialogDescription>
            </DialogHeader>

            <div v-if="recipient" class="grid gap-4">
                <div class="rounded-2xl border bg-muted/35 p-4">
                    <p class="text-xs font-medium text-muted-foreground">
                        Asisten penerima
                    </p>
                    <p class="mt-1 font-semibold">{{ recipient.name }}</p>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ recipient.holder_name }}
                    </p>
                </div>

                <div class="rounded-2xl border p-4">
                    <p class="text-xs font-medium text-muted-foreground">
                        Instruksi
                    </p>
                    <ul class="mt-2 flex flex-wrap gap-2">
                        <li
                            v-for="instruction in instructions"
                            :key="instruction.id"
                            class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-800 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-300"
                        >
                            {{ instruction.name }}
                        </li>
                    </ul>
                    <p
                        v-if="note.trim()"
                        class="mt-4 text-sm leading-6 whitespace-pre-wrap text-muted-foreground"
                    >
                        {{ note.trim() }}
                    </p>
                </div>
            </div>

            <Alert
                class="border-amber-200 bg-amber-50/75 dark:border-amber-900 dark:bg-amber-950/25"
            >
                <Info class="size-4" aria-hidden="true" />
                <AlertTitle>Tindakan tidak dapat dibatalkan</AlertTitle>
                <AlertDescription>
                    Penerima akan memperoleh cabang disposisi resmi pada inbox
                    jabatannya.
                </AlertDescription>
            </Alert>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button
                    type="button"
                    variant="outline"
                    class="min-h-11"
                    :disabled="processing"
                    @click="emit('update:open', false)"
                >
                    Periksa kembali
                </Button>
                <Button
                    type="button"
                    class="min-h-11 bg-blue-700 hover:bg-blue-800"
                    :disabled="processing || !recipient"
                    @click="emit('confirm')"
                >
                    <Spinner v-if="processing" />
                    <Check v-else class="size-4" aria-hidden="true" />
                    {{
                        processing
                            ? 'Mengirim disposisi...'
                            : 'Ya, kirim disposisi'
                    }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
