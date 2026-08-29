<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Send, Trash2, TriangleAlert } from '@lucide/vue';
import { computed, ref } from 'vue';
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
import { publicSubmissionRoutes } from '@/lib/publicSubmissionRoutes';
import type { LetterSubmission } from '@/types';

const props = defineProps<{
    submission: LetterSubmission;
    showDelete?: boolean;
}>();

const submitOpen = ref(false);
const deleteOpen = ref(false);
const submitForm = useForm({});
const deleteForm = useForm({});
const submitDocumentError = computed(
    () => (submitForm.errors as Record<string, string | undefined>).document,
);

function submitSubmission(): void {
    submitForm.post(publicSubmissionRoutes.submit(props.submission.public_id), {
        preserveScroll: true,
        onSuccess: () => {
            submitOpen.value = false;
        },
    });
}

function deleteDraft(): void {
    deleteForm.delete(
        publicSubmissionRoutes.destroy(props.submission.public_id),
        {
            onSuccess: () => {
                deleteOpen.value = false;
            },
        },
    );
}
</script>

<template>
    <section
        v-if="
            submission.capabilities.can_submit ||
            submission.capabilities.can_delete
        "
        class="rounded-[1.75rem] border bg-card p-5 shadow-[0_20px_70px_-52px_rgba(16,58,52,0.55)] sm:p-7"
    >
        <div
            class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between"
        >
            <div>
                <h2 class="text-xl font-semibold tracking-tight">
                    {{
                        submission.status === 'REVISION_REQUIRED'
                            ? 'Kirim ulang ke Bagian Umum'
                            : 'Kirim ke Bagian Umum'
                    }}
                </h2>
                <p
                    class="mt-2 max-w-2xl text-sm leading-relaxed text-muted-foreground"
                >
                    Periksa data surat dan dokumen terlebih dahulu. Setelah
                    dikirim, pengajuan surat kembali dikunci selama
                    pemeriksaan.
                </p>
                <p
                    v-if="!submission.document"
                    class="mt-3 inline-flex items-center gap-2 text-sm font-medium text-warning-foreground"
                >
                    <TriangleAlert class="size-4" />
                    Unggah satu dokumen PDF untuk mengaktifkan pengiriman.
                </p>
                <InputError
                    :message="submitDocumentError"
                    class="mt-2"
                    role="alert"
                />
            </div>
            <div class="flex flex-col-reverse gap-3 sm:flex-row">
                <Button
                    v-if="showDelete && submission.capabilities.can_delete"
                    variant="ghost"
                    size="lg"
                    class="min-h-11 cursor-pointer rounded-xl text-destructive hover:bg-destructive/10 hover:text-destructive"
                    @click="deleteOpen = true"
                >
                    <Trash2 class="size-4" />
                    Hapus draft
                </Button>
                <Button
                    size="lg"
                    class="min-h-11 cursor-pointer rounded-xl px-6"
                    :disabled="
                        !submission.capabilities.can_submit ||
                        submitForm.processing
                    "
                    @click="submitOpen = true"
                >
                    <Send class="size-4" />
                    {{
                        submission.status === 'REVISION_REQUIRED'
                            ? 'Kirim ulang'
                            : 'Kirim pengajuan'
                    }}
                </Button>
            </div>
        </div>
    </section>

    <Dialog v-model:open="submitOpen">
        <DialogContent class="rounded-[1.5rem] sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{
                        submission.status === 'REVISION_REQUIRED'
                            ? 'Kirim ulang pengajuan surat?'
                            : 'Kirim pengajuan surat sekarang?'
                    }}
                </DialogTitle>
                <DialogDescription class="leading-relaxed">
                    Data surat dan dokumen akan dikunci. Pengajuan kemudian
                    masuk ke antrean pemeriksaan Bagian Umum.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2 sm:gap-0">
                <Button
                    variant="outline"
                    class="min-h-11 cursor-pointer rounded-xl"
                    @click="submitOpen = false"
                >
                    Periksa lagi
                </Button>
                <Button
                    class="min-h-11 cursor-pointer rounded-xl"
                    :disabled="submitForm.processing"
                    @click="submitSubmission"
                >
                    <Send class="size-4" />
                    {{ submitForm.processing ? 'Mengirim…' : 'Ya, kirim' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="deleteOpen">
        <DialogContent class="rounded-[1.5rem] sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Hapus draft ini?</DialogTitle>
                <DialogDescription class="leading-relaxed">
                    Data surat dan PDF yang tersimpan akan dihapus. Catatan
                    penghapusan tetap dipertahankan untuk keamanan sistem.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter class="gap-2 sm:gap-0">
                <Button
                    variant="outline"
                    class="min-h-11 cursor-pointer rounded-xl"
                    @click="deleteOpen = false"
                >
                    Batal
                </Button>
                <Button
                    variant="destructive"
                    class="min-h-11 cursor-pointer rounded-xl"
                    :disabled="deleteForm.processing"
                    @click="deleteDraft"
                >
                    <Trash2 class="size-4" />
                    {{ deleteForm.processing ? 'Menghapus…' : 'Hapus draft' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
