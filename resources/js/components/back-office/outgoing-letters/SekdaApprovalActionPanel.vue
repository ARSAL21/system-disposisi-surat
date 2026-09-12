<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    FileSignature,
    QrCode,
    RotateCcw,
    ShieldCheck,
    Upload,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import type { SekdaApprovalDetail } from '@/types';

type ApprovalAction =
    'qr' | 'manual' | 'upload_scan' | 'review_scan' | 'return' | null;

const props = withDefaults(
    defineProps<{ approval: SekdaApprovalDetail; preview?: boolean }>(),
    { preview: false },
);
const emit = defineEmits<{
    completed: [action: Exclude<ApprovalAction, null>];
}>();
const action = ref<ApprovalAction>(null);
const form = useForm({ manual_scan: null as File | null, note: '' });
const actionRoute = computed<string | null>(
    () =>
        ({
            qr: props.approval.routes.approve_qr,
            manual: props.approval.routes.choose_manual_signature,
            upload_scan: props.approval.routes.upload_manual_scan,
            review_scan: props.approval.routes.review_manual_scan,
            return: props.approval.routes.return_for_revision,
        })[action.value ?? 'qr'],
);
const title = computed(
    () =>
        ({
            qr: 'Sahkan dengan QR',
            manual: 'Pilih tanda tangan fisik',
            upload_scan: 'Unggah scan bertanda tangan',
            review_scan: 'Periksa scan tanda tangan',
            return: 'Kembalikan untuk perbaikan',
        })[action.value ?? 'qr'],
);
const description = computed(
    () =>
        ({
            qr: 'Sistem akan menambahkan QR verifikasi ke salinan PDF bernomor dan menyimpannya sebagai versi final immutable.',
            manual: 'Surat akan menunggu tanda tangan fisik Sekda. Tidak ada QR yang dibuat.',
            upload_scan:
                'Unggah PDF hasil scan yang telah ditandatangani dan distempel di luar aplikasi.',
            review_scan:
                'Nyatakan bahwa scan sesuai dengan nomor, isi surat, dan unit asal.',
            return: 'Jelaskan bagian yang harus diperbaiki. Nomor yang sudah diberikan tetap tercatat dan konsep kembali ke unit asal.',
        })[action.value ?? 'qr'],
);

watch(action, () => {
    form.reset();
    form.clearErrors();
});

function open(next: Exclude<ApprovalAction, null>): void {
    action.value = next;
}
function chooseFile(event: Event): void {
    form.manual_scan = (event.target as HTMLInputElement).files?.[0] ?? null;
}
function submit(): void {
    if (!action.value || !actionRoute.value) {
        return;
    }

    if (props.preview) {
        emit('completed', action.value);
        action.value = null;
        toast.success('Simulasi pengesahan berhasil diperbarui.');

        return;
    }

    form.post(actionRoute.value, {
        forceFormData: action.value === 'upload_scan',
        preserveScroll: true,
        onSuccess: () => {
            emit('completed', action.value!);
            action.value = null;
        },
    });
}
</script>

<template>
    <Card class="overflow-hidden"
        ><CardHeader class="border-b bg-muted/20 pb-4"
            ><CardTitle class="flex items-center gap-2 text-base"
                ><ShieldCheck class="size-4 text-indigo-600" /> Pilih tindakan
                berikutnya</CardTitle
            ></CardHeader
        ><CardContent class="space-y-4 p-5">
            <template v-if="approval.status === 'SEKDA_REVIEW'"
                ><div
                    class="rounded-2xl border border-indigo-200 bg-indigo-50/60 p-4 dark:border-indigo-900 dark:bg-indigo-950/25"
                >
                    <QrCode class="size-5 text-indigo-600" />
                    <h2 class="mt-3 font-semibold">
                        Pengesahan Elektronik Sekda
                    </h2>
                    <p class="mt-1 text-sm leading-6 text-muted-foreground">
                        QR mengarah hanya ke halaman cek keaslian. Isi PDF tetap
                        privat.
                    </p>
                    <Button
                        v-if="approval.capabilities.can_approve_qr"
                        type="button"
                        class="mt-4 w-full rounded-xl"
                        @click="open('qr')"
                        >Sahkan dengan QR</Button
                    >
                </div>
                <Button
                    v-if="approval.capabilities.can_choose_manual_signature"
                    type="button"
                    variant="outline"
                    class="w-full rounded-xl"
                    @click="open('manual')"
                    ><FileSignature class="size-4" />Gunakan tanda tangan
                    fisik</Button
                ><Button
                    v-if="approval.capabilities.can_return_for_revision"
                    type="button"
                    variant="ghost"
                    class="w-full rounded-xl text-destructive hover:text-destructive"
                    @click="open('return')"
                    ><RotateCcw class="size-4" />Kembalikan untuk
                    perbaikan</Button
                ></template
            >
            <template
                v-else-if="approval.status === 'AWAITING_MANUAL_SIGNATURE'"
                ><Alert
                    ><FileSignature class="size-4" /><AlertTitle
                        >Menunggu tanda tangan fisik Sekda</AlertTitle
                    ><AlertDescription
                        >Setelah ditandatangani di luar aplikasi, Staf atau
                        Kabag unit asal mengunggah hasil scan
                        PDF.</AlertDescription
                    ></Alert
                ><Button
                    v-if="approval.capabilities.can_upload_manual_scan"
                    type="button"
                    class="w-full rounded-xl"
                    @click="open('upload_scan')"
                    ><Upload class="size-4" />Unggah scan bertanda
                    tangan</Button
                ></template
            >
            <template v-else-if="approval.status === 'MANUAL_SCAN_REVIEW'"
                ><Alert
                    ><ShieldCheck class="size-4" /><AlertTitle
                        >Scan menunggu pemeriksaan</AlertTitle
                    ><AlertDescription
                        >Hanya Kabag dari unit asal yang dapat memastikan
                        kesesuaian scan tersebut.</AlertDescription
                    ></Alert
                ><Button
                    v-if="approval.capabilities.can_review_manual_scan"
                    type="button"
                    class="w-full rounded-xl"
                    @click="open('review_scan')"
                    >Nyatakan scan sesuai</Button
                ><Button
                    v-if="approval.capabilities.can_return_for_revision"
                    type="button"
                    variant="outline"
                    class="w-full rounded-xl"
                    @click="open('return')"
                    ><RotateCcw class="size-4" />Minta perbaikan scan</Button
                ></template
            >
            <Alert
                v-else-if="approval.status === 'READY_FOR_DELIVERY'"
                class="border-emerald-200 bg-emerald-50/60 dark:border-emerald-900 dark:bg-emerald-950/25"
                ><ShieldCheck class="size-4" /><AlertTitle
                    >Pengesahan selesai</AlertTitle
                ><AlertDescription
                    >Surat siap masuk tahap pengiriman oleh
                    Petugas.</AlertDescription
                ></Alert
            >
            <Alert v-else variant="destructive"
                ><RotateCcw class="size-4" /><AlertTitle
                    >Surat dikembalikan</AlertTitle
                ><AlertDescription
                    >Unit asal memperbaiki konsep sebelum mengajukan
                    ulang.</AlertDescription
                ></Alert
            >
        </CardContent></Card
    >

    <Dialog
        :open="action !== null"
        @update:open="!form.processing && !$event ? (action = null) : undefined"
        ><DialogContent class="sm:max-w-lg"
            ><DialogHeader
                ><DialogTitle>{{ title }}</DialogTitle
                ><DialogDescription>{{
                    description
                }}</DialogDescription></DialogHeader
            >
            <form class="space-y-4" @submit.prevent="submit">
                <template v-if="action === 'upload_scan'"
                    ><div class="space-y-2">
                        <Label for="manual-scan">PDF hasil scan</Label
                        ><Input
                            id="manual-scan"
                            required
                            type="file"
                            accept="application/pdf,.pdf"
                            @change="chooseFile"
                        />
                        <p class="text-xs text-muted-foreground">
                            PDF maksimal 20 MB. File lama tidak pernah ditimpa.
                        </p>
                        <InputError
                            :message="form.errors.manual_scan"
                        /></div></template
                ><template
                    v-if="action === 'review_scan' || action === 'return'"
                    ><div class="space-y-2">
                        <Label for="approval-note">{{
                            action === 'return'
                                ? 'Alasan perbaikan'
                                : 'Catatan pemeriksaan (opsional)'
                        }}</Label
                        ><textarea
                            id="approval-note"
                            v-model="form.note"
                            :required="action === 'return'"
                            :minlength="action === 'return' ? 10 : undefined"
                            maxlength="2000"
                            rows="5"
                            class="w-full resize-y rounded-xl border border-input bg-background px-3 py-2 text-sm leading-6 outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        /><InputError :message="form.errors.note" /></div
                ></template>
                <div
                    v-if="action === 'qr'"
                    class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 text-sm leading-6 text-indigo-950 dark:border-indigo-900 dark:bg-indigo-950/30 dark:text-indigo-100"
                >
                    Dengan melanjutkan, Anda mencatat pengesahan elektronik
                    sebagai Sekda. Ini bukan TTE tersertifikasi.
                </div>
                <DialogFooter
                    ><Button
                        type="button"
                        variant="outline"
                        :disabled="form.processing"
                        @click="action = null"
                        >Batal</Button
                    ><Button
                        type="submit"
                        :variant="
                            action === 'return' ? 'destructive' : 'default'
                        "
                        :disabled="form.processing"
                        ><Spinner v-if="form.processing" />{{
                            action === 'qr'
                                ? 'Sahkan PDF dengan QR'
                                : action === 'manual'
                                  ? 'Tetapkan tanda tangan fisik'
                                  : action === 'return'
                                    ? 'Kembalikan surat'
                                    : 'Simpan keputusan'
                        }}</Button
                    ></DialogFooter
                >
            </form></DialogContent
        ></Dialog
    >
</template>
