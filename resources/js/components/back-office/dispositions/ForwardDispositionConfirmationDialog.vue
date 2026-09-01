<script setup lang="ts">
import {
    ArrowDownRight,
    Check,
    CircleAlert,
    Send,
    UsersRound,
} from '@lucide/vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
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
    recipients: DispositionPositionOption[];
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
        <DialogContent class="max-h-[90dvh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <div
                    class="mb-2 flex size-11 items-center justify-center rounded-2xl bg-gradient-to-br from-violet-700 to-blue-700 text-white shadow-sm"
                >
                    <Send class="size-5" aria-hidden="true" />
                </div>
                <DialogTitle>Periksa jalur disposisi</DialogTitle>
                <DialogDescription class="leading-6">
                    Satu tindakan ini akan membuat cabang kerja resmi untuk
                    setiap Kepala Bagian yang dipilih.
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4">
                <section
                    class="overflow-hidden rounded-2xl border border-violet-200/80 bg-violet-50/45 dark:border-violet-900 dark:bg-violet-950/20"
                    aria-labelledby="confirmation-recipients-title"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 border-b border-violet-100 px-4 py-3 dark:border-violet-900"
                    >
                        <div class="flex items-center gap-2">
                            <UsersRound
                                class="size-4 text-violet-700 dark:text-violet-300"
                                aria-hidden="true"
                            />
                            <p
                                id="confirmation-recipients-title"
                                class="text-sm font-semibold"
                            >
                                Kepala Bagian penerima
                            </p>
                        </div>
                        <Badge variant="secondary" class="tabular-nums">
                            {{ recipients.length }} penerima
                        </Badge>
                    </div>

                    <ol class="grid gap-2 p-3 sm:grid-cols-2">
                        <li
                            v-for="recipient in recipients"
                            :key="recipient.id"
                            class="flex items-start gap-3 rounded-xl border border-white/90 bg-background/85 p-3 shadow-xs dark:border-slate-800 dark:bg-slate-950/70"
                        >
                            <span
                                class="mt-0.5 flex size-6 shrink-0 items-center justify-center rounded-full bg-violet-100 text-xs font-bold text-violet-800 dark:bg-violet-950 dark:text-violet-200"
                            >
                                <ArrowDownRight
                                    class="size-3.5"
                                    aria-hidden="true"
                                />
                            </span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold">
                                    {{ recipient.name }}
                                </span>
                                <span
                                    class="mt-1 block text-xs leading-5 text-muted-foreground"
                                >
                                    {{ recipient.holder_name }}
                                    <template v-if="recipient.unit_name">
                                        · {{ recipient.unit_name }}
                                    </template>
                                </span>
                            </span>
                        </li>
                    </ol>
                </section>

                <section class="rounded-2xl border p-4">
                    <p class="text-xs font-medium text-muted-foreground">
                        Instruksi yang akan dikirim
                    </p>
                    <ul class="mt-3 flex flex-wrap gap-2">
                        <li
                            v-for="instruction in instructions"
                            :key="instruction.id"
                        >
                            <Badge
                                class="border border-blue-200 bg-blue-50 px-3 py-1.5 text-blue-800 hover:bg-blue-50 dark:border-blue-900 dark:bg-blue-950/40 dark:text-blue-200"
                            >
                                {{ instruction.name }}
                            </Badge>
                        </li>
                    </ul>
                    <p
                        v-if="note.trim()"
                        class="mt-4 rounded-xl bg-muted/60 p-3 text-sm leading-6 whitespace-pre-wrap text-muted-foreground"
                    >
                        {{ note.trim() }}
                    </p>
                </section>
            </div>

            <Alert
                class="border-amber-200 bg-amber-50/80 dark:border-amber-900 dark:bg-amber-950/25"
            >
                <CircleAlert class="size-4" aria-hidden="true" />
                <AlertTitle>Disposisi menjadi histori resmi</AlertTitle>
                <AlertDescription>
                    Setelah dikirim, branch Asisten selesai dan setiap penerima
                    memperoleh branch kerja sendiri.
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
                    Kembali periksa
                </Button>
                <Button
                    type="button"
                    class="min-h-11 bg-violet-700 hover:bg-violet-800"
                    :disabled="processing || recipients.length === 0"
                    @click="emit('confirm')"
                >
                    <Spinner v-if="processing" />
                    <Check v-else class="size-4" aria-hidden="true" />
                    {{
                        processing
                            ? 'Mengirim disposisi...'
                            : `Kirim ke ${recipients.length} Kabag`
                    }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
