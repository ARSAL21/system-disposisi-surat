<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { FileUp, Gavel, RotateCcw, ShieldCheck } from '@lucide/vue';
import { computed, watch } from 'vue';
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
import { Spinner } from '@/components/ui/spinner';
import type { LetterResponseUiAction } from '@/types';

const props = defineProps<{
    open: boolean;
    action: LetterResponseUiAction | null;
    preview?: boolean;
}>();

const emit = defineEmits<{
    'update:open': [open: boolean];
    completed: [message: string];
}>();

const form = useForm<{
    document: File | null;
    revision_note: string;
    reason: string;
    source_version_public_id: string;
    source_version_public_ids: string[];
    signatory_position_code: string;
    subject: string;
}>({
    document: null,
    revision_note: '',
    reason: '',
    source_version_public_id: '',
    source_version_public_ids: [],
    signatory_position_code: '',
    subject: '',
});

const icon = computed(() => {
    if (props.action?.kind === 'return') {
        return RotateCcw;
    }

    if (props.action?.kind === 'mandate') {
        return Gavel;
    }

    if (props.action?.kind === 'finalize') {
        return ShieldCheck;
    }

    return FileUp;
});

watch(
    () => [props.open, props.action] as const,
    () => {
        if (!props.open || !props.action) {
            return;
        }

        form.reset();
        form.clearErrors();

        if (props.action.kind === 'upload') {
            form.source_version_public_ids =
                props.action.sourceVersionPublicIds ?? [];
        }

        if (props.action.kind === 'mandate') {
            form.source_version_public_id =
                props.action.sources[0]?.value ?? '';
            form.signatory_position_code =
                props.action.signatories[0]?.value ?? '';
        }
    },
);

function chooseFile(event: Event): void {
    const target = event.target as HTMLInputElement;
    form.document = target.files?.[0] ?? null;
}

function submit(): void {
    if (!props.action) {
        return;
    }

    if (props.preview) {
        emit(
            'completed',
            'Simulasi aksi berhasil. Data produksi tidak diubah.',
        );
        emit('update:open', false);

        return;
    }

    form.post(props.action.route, {
        forceFormData: props.action.kind === 'upload',
        preserveScroll: true,
        onSuccess: () => {
            emit('completed', 'Perubahan dossier berhasil disimpan.');
            emit('update:open', false);
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-h-[90dvh] overflow-y-auto sm:max-w-lg">
            <DialogHeader>
                <div
                    class="mb-2 grid size-10 place-items-center rounded-xl bg-indigo-500/10 text-indigo-600"
                >
                    <component :is="icon" class="size-5" />
                </div>
                <DialogTitle>{{ action?.title }}</DialogTitle>
                <DialogDescription class="leading-6">{{
                    action?.description
                }}</DialogDescription>
            </DialogHeader>

            <form v-if="action" class="space-y-4" @submit.prevent="submit">
                <template v-if="action.kind === 'upload'">
                    <div class="space-y-2">
                        <Label for="response-document">Dokumen PDF</Label
                        ><Input
                            id="response-document"
                            type="file"
                            accept="application/pdf,.pdf"
                            required
                            @change="chooseFile"
                        /><InputError :message="form.errors.document" />
                    </div>
                    <div class="space-y-2">
                        <Label for="response-revision-note"
                            >Catatan dokumen</Label
                        ><textarea
                            id="response-revision-note"
                            v-model="form.revision_note"
                            required
                            minlength="10"
                            maxlength="2000"
                            rows="4"
                            class="w-full resize-y rounded-xl border border-input bg-background px-3 py-2 text-sm leading-6 outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            placeholder="Jelaskan isi atau perubahan pada versi ini."
                        /><InputError :message="form.errors.revision_note" />
                    </div>
                </template>

                <template v-else-if="action.kind === 'return'">
                    <div class="space-y-2">
                        <Label for="response-return-reason"
                            >Alasan pengembalian</Label
                        ><textarea
                            id="response-return-reason"
                            v-model="form.reason"
                            required
                            minlength="10"
                            maxlength="2000"
                            rows="5"
                            class="w-full resize-y rounded-xl border border-input bg-background px-3 py-2 text-sm leading-6 outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            placeholder="Sebutkan bagian yang harus diperbaiki secara jelas."
                        /><InputError :message="form.errors.reason" />
                    </div>
                </template>

                <template v-else-if="action.kind === 'mandate'">
                    <div class="space-y-2">
                        <Label for="response-source">Dokumen sumber</Label
                        ><select
                            id="response-source"
                            v-model="form.source_version_public_id"
                            required
                            class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm"
                        >
                            <option
                                v-for="source in action.sources"
                                :key="source.value"
                                :value="source.value"
                            >
                                {{ source.label }}
                            </option></select
                        ><InputError
                            :message="form.errors.source_version_public_id"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="response-signatory"
                            >Penandatangan substantif</Label
                        ><select
                            id="response-signatory"
                            v-model="form.signatory_position_code"
                            required
                            class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm"
                        >
                            <option
                                v-for="signatory in action.signatories"
                                :key="signatory.value"
                                :value="signatory.value"
                            >
                                {{ signatory.label }}
                            </option></select
                        ><InputError
                            :message="form.errors.signatory_position_code"
                        />
                    </div>
                    <div class="space-y-2">
                        <Label for="response-subject"
                            >Perihal surat balasan</Label
                        ><Input
                            id="response-subject"
                            v-model="form.subject"
                            required
                            minlength="10"
                            maxlength="255"
                        /><InputError :message="form.errors.subject" />
                    </div>
                </template>

                <div
                    v-else
                    class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm leading-6 text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-100"
                >
                    Setelah difinalisasi, proposal, revisi, dan mandat baru
                    tidak dapat ditambahkan. Tahap berikutnya dilanjutkan pada
                    register surat keluar M8.3.
                </div>

                <DialogFooter class="gap-2 sm:gap-0"
                    ><Button
                        type="button"
                        variant="outline"
                        @click="emit('update:open', false)"
                        >Batal</Button
                    ><Button type="submit" :disabled="form.processing"
                        ><Spinner v-if="form.processing" class="mr-2" />{{
                            action.kind === 'finalize' ? 'Finalisasi' : 'Simpan'
                        }}</Button
                    ></DialogFooter
                >
            </form>
        </DialogContent>
    </Dialog>
</template>
