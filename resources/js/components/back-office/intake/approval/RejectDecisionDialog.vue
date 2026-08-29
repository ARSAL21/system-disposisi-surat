<script setup lang="ts">
import { CircleX, TriangleAlert } from '@lucide/vue';
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
import InputError from '@/components/InputError.vue';
import type { RejectApprovalPayload } from '@/types';

const props = defineProps<{
    open: boolean;
    processing?: boolean;
    noteError?: string;
}>();
const emit = defineEmits<{
    'update:open': [open: boolean];
    confirm: [payload: RejectApprovalPayload];
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
    emit('confirm', { outcome: 'REJECTED', note: note.value.trim() });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Tolak pengajuan surat?</DialogTitle>
                <DialogDescription class="leading-6">
                    Penolakan bersifat final. Alasan yang Anda tulis akan
                    ditampilkan kepada pemilik pengajuan.
                </DialogDescription>
            </DialogHeader>
            <div
                class="flex gap-3 rounded-2xl border border-rose-200 bg-rose-50/70 p-4 text-sm text-rose-900 dark:border-rose-900 dark:bg-rose-950/25 dark:text-rose-100"
            >
                <TriangleAlert
                    class="mt-0.5 size-5 shrink-0"
                    aria-hidden="true"
                />
                <p>
                    Gunakan alasan formal, jelas, dan tidak memuat informasi
                    internal yang bersifat rahasia.
                </p>
            </div>
            <div class="space-y-2">
                <Label for="reject-decision-note">Alasan penolakan</Label>
                <textarea
                    id="reject-decision-note"
                    v-model="note"
                    rows="5"
                    maxlength="2000"
                    autofocus
                    class="w-full resize-y rounded-xl border bg-background px-3 py-3 text-sm leading-6 outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                    placeholder="Tuliskan alasan penolakan yang dapat dipahami pengirim..."
                />
                <p class="text-xs text-muted-foreground">
                    Minimal 10 karakter.
                </p>
                <InputError :message="noteError" />
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
                    variant="destructive"
                    class="min-h-11"
                    :disabled="!canSubmit || processing"
                    @click="confirm"
                >
                    <CircleX class="size-4" aria-hidden="true" />
                    Ya, tolak surat
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
