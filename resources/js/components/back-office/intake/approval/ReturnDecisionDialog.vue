<script setup lang="ts">
import { RotateCcw } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import type { ReturnApprovalPayload } from '@/types';

const props = defineProps<{ open: boolean; processing?: boolean }>();
const emit = defineEmits<{
    'update:open': [open: boolean];
    confirm: [payload: ReturnApprovalPayload];
}>();

const note = ref('');
const canSubmit = computed(() => note.value.trim().length >= 10);

watch(
    () => props.open,
    (open) => {
        if (open) note.value = '';
    },
);

function confirm(): void {
    if (!canSubmit.value) return;
    emit('confirm', {
        outcome: 'INTERNAL_REVISION_REQUIRED',
        note: note.value.trim(),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Kembalikan kepada petugas?</DialogTitle>
                <DialogDescription class="leading-6">
                    Pengajuan keluar dari meja keputusan dan kembali ke petugas
                    Bagian Umum untuk diperbaiki secara internal.
                </DialogDescription>
            </DialogHeader>
            <div class="space-y-2">
                <Label for="return-decision-note">Catatan perbaikan</Label>
                <textarea
                    id="return-decision-note"
                    v-model="note"
                    rows="5"
                    maxlength="2000"
                    autofocus
                    class="w-full resize-y rounded-xl border bg-background px-3 py-3 text-sm leading-6 outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                    placeholder="Jelaskan bagian yang perlu diperbaiki oleh petugas..."
                />
                <p class="text-xs text-muted-foreground">
                    Minimal 10 karakter. Catatan hanya terlihat oleh petugas
                    internal terkait.
                </p>
            </div>
            <DialogFooter class="gap-2 sm:gap-0">
                <Button
                    type="button"
                    variant="outline"
                    class="min-h-11"
                    @click="emit('update:open', false)"
                >
                    Batal
                </Button>
                <Button
                    type="button"
                    class="min-h-11 bg-amber-600 text-white hover:bg-amber-700"
                    :disabled="!canSubmit || processing"
                    @click="confirm"
                >
                    <RotateCcw class="size-4" aria-hidden="true" />
                    Kembalikan ke petugas
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
