<script setup lang="ts">
import { Check, Landmark, Send } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import type { DispositionInstructionLabelOption } from '@/types';

const props = defineProps<{
    instructionLabels: DispositionInstructionLabelOption[];
    canForward: boolean;
    processing?: boolean;
    errors?: Record<string, string>;
    blockedReason?: string;
}>();

const emit = defineEmits<{
    confirm: [
        payload: { instruction_label_ids: number[]; instruction_note: string },
    ];
}>();

const selectedIds = ref<number[]>([]);
const note = ref('');
const localError = computed(() =>
    selectedIds.value.length === 0
        ? 'Pilih sedikitnya satu instruksi disposisi.'
        : '',
);

function toggle(labelId: number, checked: boolean): void {
    selectedIds.value = checked
        ? [...new Set([...selectedIds.value, labelId])]
        : selectedIds.value.filter((id) => id !== labelId);
}

function submit(): void {
    if (!props.canForward || props.processing || localError.value) {
        return;
    }

    emit('confirm', {
        instruction_label_ids: selectedIds.value,
        instruction_note: note.value.trim(),
    });
}
</script>

<template>
    <section
        class="rounded-2xl border border-violet-200 bg-violet-50/45 p-5 dark:border-violet-900 dark:bg-violet-950/20"
    >
        <div class="flex gap-3">
            <span
                class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-violet-600 text-white"
                ><Landmark class="size-5"
            /></span>
            <div>
                <h2 class="font-semibold">Teruskan kepada Sekda</h2>
                <p class="mt-1 text-sm leading-6 text-muted-foreground">
                    Wali Kota memberikan arahan formal. Sekda akan menentukan
                    Asisten yang menangani surat.
                </p>
            </div>
        </div>

        <Alert v-if="!canForward" class="mt-5">
            <AlertTitle>{{ blockedReason ? 'Belum dapat diteruskan' : 'Arahan sudah tercatat' }}</AlertTitle>
            <AlertDescription>{{ blockedReason || 'Surat ini tidak lagi menunggu arahan Wali Kota.' }}</AlertDescription>
        </Alert>

        <div v-else class="mt-5 grid gap-4">
            <div class="grid gap-2">
                <Label>Instruksi <span aria-hidden="true">*</span></Label>
                <label
                    v-for="label in instructionLabels"
                    :key="label.id"
                    class="flex cursor-pointer items-start gap-3 rounded-xl border bg-background p-3"
                >
                    <Checkbox
                        :model-value="selectedIds.includes(label.id)"
                        @update:model-value="toggle(label.id, $event === true)"
                    />
                    <span
                        ><span class="block text-sm font-medium">{{
                            label.name
                        }}</span
                        ><span
                            v-if="label.description"
                            class="mt-1 block text-xs leading-5 text-muted-foreground"
                            >{{ label.description }}</span
                        ></span
                    >
                </label>
                <InputError
                    :message="errors?.instruction_label_ids || localError"
                />
            </div>

            <div class="grid gap-2">
                <Label for="mayor-instruction-note"
                    >Catatan arahan
                    <span class="text-muted-foreground">(opsional)</span></Label
                >
                <Textarea
                    id="mayor-instruction-note"
                    v-model="note"
                    :disabled="processing"
                    maxlength="2000"
                    placeholder="Tuliskan penekanan atau arahan untuk Sekda."
                />
                <InputError :message="errors?.instruction_note" />
            </div>

            <Button
                type="button"
                :disabled="processing || Boolean(localError)"
                @click="submit"
            >
                <Send v-if="!processing" class="size-4" /><Check
                    v-else
                    class="size-4 animate-pulse"
                />
                {{
                    processing ? 'Menyimpan arahan...' : 'Teruskan kepada Sekda'
                }}
            </Button>
        </div>
    </section>
</template>
