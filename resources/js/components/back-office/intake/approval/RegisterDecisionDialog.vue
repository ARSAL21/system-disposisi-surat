<script setup lang="ts">
import { BadgeCheck, ShieldCheck } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import SenderOrganizationPicker from '@/components/back-office/intake/approval/SenderOrganizationPicker.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type {
    RegisterApprovalPayload,
    SenderOrganizationOption,
} from '@/types';

const props = defineProps<{
    open: boolean;
    senderName: string;
    organizations: SenderOrganizationOption[];
    processing?: boolean;
    errors?: Record<string, string>;
}>();
const emit = defineEmits<{
    'update:open': [open: boolean];
    confirm: [payload: RegisterApprovalPayload];
}>();

const agendaNumber = ref('');
const note = ref('');
const senderOrganization = ref<RegisterApprovalPayload['sender_organization']>({
    mode: 'existing',
    id: 0,
});

const senderIsValid = computed(() =>
    senderOrganization.value.mode === 'existing'
        ? senderOrganization.value.id > 0
        : senderOrganization.value.name.trim().length > 0,
);
const canSubmit = computed(
    () => agendaNumber.value.trim().length > 0 && senderIsValid.value,
);

watch(
    () => props.open,
    (open) => {
        if (!open) {
            return;
        }

        agendaNumber.value = '';
        note.value = '';
        const match = props.organizations.find(
            ({ name }) => name === props.senderName,
        );
        senderOrganization.value = match
            ? { mode: 'existing', id: match.id }
            : {
                  mode: 'new',
                  name: props.senderName,
                  address: null,
                  contact: null,
              };
    },
);

function confirm(): void {
    if (!canSubmit.value) {
        return;
    }

    emit('confirm', {
        outcome: 'REGISTERED',
        agenda_number: agendaNumber.value.trim(),
        note: note.value.trim() || null,
        sender_organization: senderOrganization.value,
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>Registrasikan sebagai surat masuk?</DialogTitle>
                <DialogDescription class="leading-6">
                    Sistem akan membuat surat masuk resmi dan mengaitkan PDF
                    sebagai dokumen versi pertama.
                </DialogDescription>
            </DialogHeader>

            <div
                class="flex gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 text-sm text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950/25 dark:text-emerald-100"
            >
                <ShieldCheck
                    class="mt-0.5 size-5 shrink-0"
                    aria-hidden="true"
                />
                <p>
                    Nomor agenda akan diperiksa keunikannya. Tahun agenda dan
                    waktu registrasi ditentukan oleh sistem.
                </p>
            </div>

            <div class="space-y-2">
                <Label for="agenda-number">Nomor agenda</Label>
                <Input
                    id="agenda-number"
                    v-model="agendaNumber"
                    maxlength="50"
                    autofocus
                    placeholder="Contoh: AG-0188"
                    class="font-mono"
                />
                <InputError :message="errors?.agenda_number" />
            </div>

            <SenderOrganizationPicker
                v-model="senderOrganization"
                :organizations="organizations"
                :suggested-name="senderName"
                :errors="errors"
            />

            <label class="block space-y-2">
                <span class="text-sm font-medium">Catatan (opsional)</span>
                <textarea
                    v-model="note"
                    rows="3"
                    maxlength="2000"
                    class="w-full resize-y rounded-xl border bg-background px-3 py-3 text-sm leading-6 outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50"
                    placeholder="Tambahkan catatan registrasi bila diperlukan..."
                />
                <InputError :message="errors?.note" />
            </label>

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
                    class="min-h-11 bg-gradient-to-r from-blue-700 to-violet-700 text-white hover:from-blue-800 hover:to-violet-800"
                    :disabled="!canSubmit || processing"
                    @click="confirm"
                >
                    <BadgeCheck class="size-4" aria-hidden="true" />
                    Registrasikan surat
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
