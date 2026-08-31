<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Power, ShieldAlert } from '@lucide/vue';
import { ref } from 'vue';
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
import type { DispositionInstructionLabel } from '@/types';

const props = defineProps<{
    open: boolean;
    label: DispositionInstructionLabel;
    canChangeStatus: boolean;
    preview?: boolean;
}>();
const emit = defineEmits<{
    'update:open': [open: boolean];
    'preview:confirm': [label: DispositionInstructionLabel];
}>();

const processing = ref(false);

function confirmStatus(): void {
    if (!props.canChangeStatus) {
        return;
    }

    if (props.preview) {
        emit('preview:confirm', props.label);
        emit('update:open', false);

        return;
    }

    router.patch(
        props.label.links.status,
        { is_active: !props.label.is_active },
        {
            preserveScroll: true,
            onStart: () => {
                processing.value = true;
            },
            onSuccess: () => emit('update:open', false),
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <Dialog
        :open="open"
        @update:open="!processing ? emit('update:open', $event) : undefined"
    >
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{ label.is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                    instruksi?
                </DialogTitle>
                <DialogDescription class="leading-6">
                    {{ label.name }}
                </DialogDescription>
            </DialogHeader>

            <div
                class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50/75 p-4 text-sm dark:border-amber-900 dark:bg-amber-950/25"
            >
                <ShieldAlert
                    class="mt-0.5 size-5 shrink-0 text-amber-800 dark:text-amber-300"
                    aria-hidden="true"
                />
                <p class="leading-6 text-muted-foreground">
                    <template v-if="!canChangeStatus">
                        Sedikitnya satu instruksi harus tetap aktif. Aktifkan
                        label lain sebelum menonaktifkan instruksi ini.
                    </template>
                    <template v-else-if="label.is_active">
                        Label tidak lagi tersedia untuk disposisi baru, tetapi
                        tetap muncul pada histori yang sudah tercatat.
                    </template>
                    <template v-else>
                        Label kembali tersedia pada form disposisi setelah
                        perubahan disimpan.
                    </template>
                </p>
            </div>

            <DialogFooter class="gap-2 sm:gap-0">
                <Button
                    type="button"
                    variant="outline"
                    class="min-h-11"
                    :disabled="processing"
                    @click="emit('update:open', false)"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    class="min-h-11"
                    :variant="label.is_active ? 'destructive' : 'default'"
                    :disabled="processing || !canChangeStatus"
                    @click="confirmStatus"
                >
                    <Spinner v-if="processing" />
                    <Power v-else class="size-4" aria-hidden="true" />
                    {{
                        processing
                            ? 'Menyimpan...'
                            : label.is_active
                              ? 'Ya, nonaktifkan'
                              : 'Ya, aktifkan'
                    }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
